<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HostingAccount extends Model
{
    protected $fillable = [
        'label',
        'username',  
        'password',
        'domain',
        'status', // pending, active, suspended, deactivated, reactivating, deactivating
        'key',    // Account key from MOFH
        'main_domain', // Main cPanel domain
        'sql_server', // SQL server name (e.g. sql123)
        'user_id', // Owner
        'cpanel_verified',
        'cpanel_verified_at',
        'admin_deactivated',
        'admin_deactivation_reason',
        'admin_deactivated_at'
    ];

    protected $casts = [
        'cpanel_verified' => 'boolean',
        'cpanel_verified_at' => 'datetime',
        'admin_deactivated' => 'boolean',
        'admin_deactivated_at' => 'datetime'
    ];

    // =============================================================================
    // RELATIONSHIPS
    // =============================================================================

    /**
     * Get account owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all databases for this hosting account
     */
    public function databases(): HasMany
    {
        return $this->hasMany(HostingDatabase::class)->orderBy('created_at', 'desc');
    }

    // =============================================================================
    // ACCOUNT STATUS & VERIFICATION METHODS
    // =============================================================================

    /**
     * Check if cPanel is verified
     */
    public function isCpanelVerified(): bool
    {
        return $this->cpanel_verified;
    }

    /**
     * Check if account was deactivated by admin
     */
    public function isAdminDeactivated(): bool
    {
        return $this->admin_deactivated;
    }

    /**
     * Check if account is active and ready for operations
     */
    public function isActiveAndVerified(): bool
    {
        return $this->status === 'active' && $this->cpanel_verified && !$this->admin_deactivated;
    }

    /**
     * Get account status with detailed info
     */
    public function getDetailedStatusAttribute(): array
    {
        return [
            'status' => $this->status,
            'is_active' => $this->status === 'active',
            'is_verified' => $this->cpanel_verified,
            'is_admin_deactivated' => $this->admin_deactivated,
            'can_manage' => $this->isActiveAndVerified(),
            'verified_at' => $this->cpanel_verified_at?->format('M j, Y g:i A'),
            'admin_deactivation_reason' => $this->admin_deactivation_reason
        ];
    }

    // =============================================================================
    // DATABASE MANAGEMENT METHODS
    // =============================================================================

    /**
     * Get databases count
     */
    public function getDatabasesCountAttribute(): int
    {
        return $this->databases()->count();
    }

    /**
     * Get formatted databases for frontend (NO phpMyAdmin URLs cached)
     */
    public function getFormattedDatabasesAttribute(): array
    {
        return $this->databases->map(function ($db) {
            return $db->formatted_info;
        })->toArray();
    }

    /**
     * Check if database exists locally
     */
    public function hasDatabaseNamed(string $databaseName): bool
    {
        return $this->databases()->where('database_name', $databaseName)->exists();
    }

    /**
     * Get database by name
     */
    public function getDatabaseByName(string $databaseName): ?HostingDatabase
    {
        return $this->databases()->where('database_name', $databaseName)->first();
    }

  /**
     * Get database usage statistics with real-time limits from VistaPanel
     * Enhanced with better caching and error handling
     */
    public function getDatabaseStatsAttribute(): array
    {
        $count = $this->databases_count;
        $defaultStats = [
            'current_usage' => $count,
            'max_databases' => 1,
            'available' => max(0, 1 - $count),
            'usage_percent' => $count >= 1 ? 100 : 0,
            'can_create_more' => $count < 1,
            'last_updated' => $this->databases()->max('updated_at'),
            'is_unlimited' => false,
            'source' => 'fallback'
        ];

        // Return fallback if account is not ready
        if ($this->status !== 'active' || !$this->cpanel_verified) {
            return $defaultStats;
        }

        // Check if we have fresh cached data (within 10 minutes)
        $cacheExpiry = 10; // minutes
        if ($this->cached_db_limits_updated_at && 
            $this->cached_db_limits_updated_at->gt(now()->subMinutes($cacheExpiry)) &&
            !empty($this->cached_db_max_limit)) {
            
            $maxDatabases = $this->cached_db_max_limit;
            
            Log::debug('Using cached database limits', [
                'account_id' => $this->id,
                'username' => $this->username,
                'cached_max' => $maxDatabases,
                'cached_at' => $this->cached_db_limits_updated_at
            ]);
            
            return [
                'current_usage' => $count,
                'max_databases' => $maxDatabases,
                'available' => $maxDatabases === 'Unlimited' ? 'Unlimited' : max(0, $maxDatabases - $count),
                'usage_percent' => $maxDatabases === 'Unlimited' ? 0 : 
                    ($maxDatabases > 0 ? round(($count / $maxDatabases) * 100, 1) : 0),
                'can_create_more' => $maxDatabases === 'Unlimited' || $count < $maxDatabases,
                'last_updated' => $this->databases()->max('updated_at'),
                'is_unlimited' => $maxDatabases === 'Unlimited',
                'source' => 'cached_api'
            ];
        }

        // Try to get fresh data from VistaPanel API
        try {
            $settings = \App\Models\MofhApiSetting::first();
            if (!$settings) {
                Log::warning('MOFH API settings not found, using fallback', [
                    'account_id' => $this->id
                ]);
                return $defaultStats;
            }

            $api = new \App\Libraries\VistapanelApi();
            $api->setCpanelUrl($settings->cpanel_url);
            
            if ($api->login($this->username, $this->password)) {
                try {
                    $limits = $api->getDatabaseLimits();
                    $api->logout();
                    
                    // Cache the results
                    $this->update([
                        'cached_db_max_limit' => $limits['max_databases'],
                        'cached_db_limits_updated_at' => now()
                    ]);
                    
                    Log::info('Fresh database limits obtained from API', [
                        'account_id' => $this->id,
                        'username' => $this->username,
                        'api_current' => $limits['current_usage'],
                        'api_max' => $limits['max_databases'],
                        'local_count' => $count
                    ]);
                    
                    // Use API current usage if it's different from local count
                    $actualUsage = $limits['current_usage'] ?? $count;
                    
                    return [
                        'current_usage' => $actualUsage,
                        'max_databases' => $limits['max_databases'],
                        'available' => $limits['available'],
                        'usage_percent' => $limits['usage_percent'],
                        'can_create_more' => $limits['is_unlimited'] || $actualUsage < $limits['max_databases'],
                        'last_updated' => now(),
                        'is_unlimited' => $limits['is_unlimited'],
                        'source' => 'fresh_api',
                        'api_sync_diff' => $actualUsage !== $count ? [
                            'api_count' => $actualUsage,
                            'local_count' => $count
                        ] : null
                    ];
                    
                } catch (\Exception $apiError) {
                    $api->logout();
                    throw $apiError;
                }
            } else {
                throw new \Exception('Failed to login to cPanel');
            }
            
        } catch (\Exception $e) {
            Log::warning('Could not get real database limits from VistaPanel, using fallback', [
                'account_id' => $this->id,
                'username' => $this->username,
                'error' => $e->getMessage()
            ]);
            
            // Use cached data if available, even if expired
            if (!empty($this->cached_db_max_limit)) {
                $maxDatabases = $this->cached_db_max_limit;
                
                return [
                    'current_usage' => $count,
                    'max_databases' => $maxDatabases,
                    'available' => $maxDatabases === 'Unlimited' ? 'Unlimited' : max(0, $maxDatabases - $count),
                    'usage_percent' => $maxDatabases === 'Unlimited' ? 0 : 
                        ($maxDatabases > 0 ? round(($count / $maxDatabases) * 100, 1) : 0),
                    'can_create_more' => $maxDatabases === 'Unlimited' || $count < $maxDatabases,
                    'last_updated' => $this->databases()->max('updated_at'),
                    'is_unlimited' => $maxDatabases === 'Unlimited',
                    'source' => 'expired_cache',
                    'error' => 'API unavailable, using cached data'
                ];
            }
            
            return array_merge($defaultStats, [
                'error' => 'API unavailable, using fallback'
            ]);
        }
    }

    /**
     * Force refresh database limits from API
     * 
     * @return array Fresh database stats
     */
    public function refreshDatabaseStats(): array
    {
        // Clear cache
        $this->update([
            'cached_db_max_limit' => null,
            'cached_db_limits_updated_at' => null
        ]);

        // Get fresh stats
        return $this->getDatabaseStatsAttribute();
    }

    /**
     * Validate if a new database can be created
     * 
     * @param string $databaseName Optional database name to check
     * @return array Validation result
     */
    public function canCreateDatabase($databaseName = null): array
    {
        $stats = $this->database_stats;
        
        // Check account status
        if ($this->status !== 'active') {
            return [
                'can_create' => false,
                'reason' => 'Account must be active to create databases',
                'stats' => $stats
            ];
        }

        if (!$this->cpanel_verified) {
            return [
                'can_create' => false,
                'reason' => 'Please verify your cPanel access first',
                'stats' => $stats
            ];
        }

        // Check limits
        if (!$stats['can_create_more']) {
            return [
                'can_create' => false,
                'reason' => "Database limit reached ({$stats['current_usage']}/{$stats['max_databases']})",
                'stats' => $stats
            ];
        }

        // Check name conflict if provided
        if ($databaseName && $this->hasDatabaseNamed($databaseName)) {
            return [
                'can_create' => false,
                'reason' => "Database '{$databaseName}' already exists",
                'stats' => $stats
            ];
        }

        return [
            'can_create' => true,
            'reason' => 'Database can be created',
            'stats' => $stats
        ];
    }

    /**
     * Get database limit summary for display
     * 
     * @return string Human-readable limit summary
     */
    public function getDatabaseLimitSummary(): string
    {
        $stats = $this->database_stats;
        
        if ($stats['is_unlimited']) {
            return "Using {$stats['current_usage']} databases (Unlimited)";
        }
        
        return "Using {$stats['current_usage']} of {$stats['max_databases']} databases ({$stats['usage_percent']}%)";
    }

    /**
     * Check if database limits should be refreshed
     * 
     * @return bool
     */
    public function shouldRefreshDatabaseLimits(): bool
    {
        if (!$this->cached_db_limits_updated_at) {
            return true;
        }
        
        // Refresh if cache is older than 10 minutes
        return $this->cached_db_limits_updated_at->lt(now()->subMinutes(10));
    }

    // =============================================================================
    // CONNECTION INFO METHODS
    // =============================================================================

    /**
     * Get MySQL host
     */
    public function getMysqlHostAttribute(): string
    {
        Log::channel('hosting')->info('Getting MySQL host', [
            'account_id' => $this->id,
            'status' => $this->status,
            'sql_server' => $this->sql_server
        ]);

        if ($this->status !== 'active') {
            return 'Loading...';
        }

        if (empty($this->sql_server)) {
            return 'Loading...'; 
        }

        // Get domain extension for MySQL host
        $domain = AllowedDomain::first();
        if (!$domain) {
            Log::channel('hosting')->error('No domain extension found for MySQL host');
            return 'sql111.fhost.click'; // Fallback
        }

        // Combine SQL server with domain extension 
        $host = $this->sql_server . $domain->domain_name;

        Log::channel('hosting')->info('MySQL host generated', [
            'sql_server' => $this->sql_server,
            'domain' => $domain->domain_name,
            'full_host' => $host
        ]);

        return $host;
    }

    /**
     * Get FTP hostname
     */
    public function getFtpHostAttribute(): string
    {
        return 'ftpupload.net';
    }

    /**
     * Get connection details for frontend
     */
    public function getConnectionDetailsAttribute(): array
    {
        return [
            'ftp' => [
                'hostname' => $this->ftp_host,
                'username' => $this->username,
                'password' => '(Your cPanel Password)',
                'port' => 21
            ],
            'mysql' => [
                'hostname' => $this->mysql_host,
                'username' => $this->username,
                'password' => '(Your cPanel Password)',
                'port' => 3306
            ],
            'cpanel' => [
                'url' => $this->main_domain,
                'username' => $this->username,
                'password' => '(Your cPanel Password)'
            ]
        ];
    }

    // =============================================================================
    // URL GENERATION METHODS
    // =============================================================================

    /**
     * Get File Manager URL with proper credentials
     */
    public function getFileManagerUrl($domain = null): string
    {
        if ($this->status !== 'active') {
            return '#';
        }

        $domain = $domain ?? $this->domain;
        
        $params = [
            'host' => $this->ftp_host,
            'port' => 21,
            'user' => $this->username,
            'password' => $this->password,
            'dir' => $domain === $this->domain ? "/htdocs/" : "/{$domain}/htdocs/"
        ];

        return "https://filemanager.ai/new/?" . http_build_query($params);
    }

    /**
     * Get cPanel URL
     */
    public function getCpanelUrl(): string
    {
        return $this->status === 'active' ? $this->main_domain : '#';
    }

    /**
     * Get Softaculous installer URL
     */
    public function getSoftaculousUrl(): string
    {
        return $this->status === 'active' ? $this->main_domain . '/softaculous' : '#';
    }

    /**
     * Get all account URLs
     */
    public function getAccountUrlsAttribute(): array
    {
        return [
            'cpanel' => $this->getCpanelUrl(),
            'file_manager' => $this->getFileManagerUrl(),
            'softaculous' => $this->getSoftaculousUrl(),
            'main_domain' => 'https://' . $this->domain
        ];
    }

    // =============================================================================
    // STATISTICS & SUMMARY METHODS
    // =============================================================================

    /**
     * Get account summary for dashboard
     */
    public function getAccountSummaryAttribute(): array
    {
        return [
            'basic_info' => [
                'label' => $this->label,
                'username' => $this->username,
                'domain' => $this->domain,
                'status' => $this->status,
                'created_at' => $this->created_at->format('M j, Y'),
                'created_ago' => $this->created_at->diffForHumans()
            ],
            'verification' => $this->detailed_status,
            'usage' => [
                'databases' => $this->database_stats,
                'domains' => [
                    'addon_domains' => $this->addon_domains_count ?? 0,
                    'parked_domains' => $this->parked_domains_count ?? 0,
                    'total' => $this->total_domains_count ?? 1
                ]
            ],
            'last_activity' => [
                'database_sync' => $this->databases()->max('updated_at')
            ]
        ];
    }

    // =============================================================================
    // DOMAIN METHODS (Main domains only)
    // =============================================================================

    /**
     * Get all domains associated with this account (main domains only).
     *
     * @param mixed $mofhService The MOFH service instance
     * @return array Array of all domains with their information
     */
    public function getDomains($mofhService): array
    {
        if ($this->status !== 'active') {
            return [];
        }

        try {
            $allDomains = collect();

            // Get main domains from MOFH service
            try {
                $mofhDomains = $mofhService->getDomains($this->username);
                if ($mofhDomains) {
                    foreach ($mofhDomains as $domain) {
                        $allDomains->push([
                            'domain' => $domain,
                            'type' => 'main',
                            'source' => 'mofh',
                            'file_manager_url' => $this->getFileManagerUrl($domain),
                            'sitepro_url' => route('hosting.builder', [
                                'username' => $this->username,
                                'domain' => $domain
                            ]),
                            'is_active' => true,
                            'created_at' => 'Unknown'
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to get main domains from MOFH service', [
                    'username' => $this->username,
                    'error' => $e->getMessage()
                ]);
            }

            \Log::debug('Retrieved all domains for account', [
                'username' => $this->username,
                'main_domains' => $allDomains->where('type', 'main')->count(),
                'total' => $allDomains->count()
            ]);

            return $allDomains->toArray();

        } catch (\Exception $e) {
            \Log::error('Error getting domains for account', [
                'username' => $this->username,
                'account_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get total count of all domains.
     *
     * @return int
     */
    public function getTotalDomainsCountAttribute(): int
    {
        // For main domains only, default to 1 (main domain)
        return 1;
    }

    /**
     * Get comprehensive domain statistics for this account.
     *
     * @return array
     */
    public function getDomainStatsAttribute(): array
    {
        return [
            'main_domains' => [
                'count' => 1, // Main domain
                'last_updated' => 'Unknown'
            ],
            'total_estimated' => 1,
            'last_activity' => $this->updated_at
        ];
    }

    // =============================================================================
    // STATIC METHODS
    // =============================================================================

    /**
     * Get active accounts count for a user
     */
    public static function getActiveCount($userId): int
    {
        return self::where('user_id', $userId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get accounts with their usage stats
     */
    public static function withUsageStats()
    {
        return self::with(['databases']);
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for verified accounts
     */
    public function scopeVerified($query)
    {
        return $query->where('cpanel_verified', true);
    }

    /**
     * Scope for ready accounts (active + verified + not admin deactivated)
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'active')
                    ->where('cpanel_verified', true)
                    ->where('admin_deactivated', false);
    }
}