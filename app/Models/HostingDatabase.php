<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class HostingDatabase extends Model
{
    use HasFactory;

    protected $fillable = [
        'hosting_account_id',
        'database_name',
        'full_name',
        'mysql_host'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =============================================================================
    // RELATIONSHIPS
    // =============================================================================

    /**
     * Get the hosting account that owns this database
     */
    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    // =============================================================================
    // ATTRIBUTE ACCESSORS
    // =============================================================================

    /**
     * Get formatted database info for frontend (NO phpMyAdmin URL cached)
     */
    public function getFormattedInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'short_name' => $this->database_name,
            'user' => $this->hostingAccount->username,
            'password' => '(Your cPanel Password)',
            'host' => $this->mysql_host ?? 'sql111.fhost.click',
            'created_at' => $this->created_at->format('M j, Y g:i A'),
            'created_ago' => $this->created_at->diffForHumans(),
            'size' => 'N/A', // Can be updated later if size tracking is needed
            'connection_string' => $this->getConnectionString()
        ];
    }

    /**
     * Get database connection string
     */
    public function getConnectionStringAttribute(): string
    {
        $host = $this->mysql_host ?? 'sql111.fhost.click';
        return "mysql://{$this->hostingAccount->username}:PASSWORD@{$host}:3306/{$this->full_name}";
    }

    // =============================================================================
    // STATIC UTILITY METHODS
    // =============================================================================

    /**
     * Generate full database name from username and database name
     */
    public static function generateFullName(string $username, string $databaseName): string
    {
        return $username . '_' . $databaseName;
    }

    /**
     * Extract database name from full name
     */
    public static function extractDatabaseName(string $fullName, string $username): string
    {
        $prefix = $username . '_';
        if (str_starts_with($fullName, $prefix)) {
            return substr($fullName, strlen($prefix));
        }
        return $fullName;
    }

    /**
     * Create database record with automatic full name generation
     */
    public static function createForAccount(HostingAccount $account, string $databaseName, array $additionalData = []): self
    {
        return self::create(array_merge([
            'hosting_account_id' => $account->id,
            'database_name' => $databaseName,
            'full_name' => self::generateFullName($account->username, $databaseName),
        ], $additionalData));
    }

    // =============================================================================
    // QUERY SCOPES
    // =============================================================================

    /**
     * Scope to get databases for a specific hosting account
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('hosting_account_id', $accountId);
    }

    /**
     * Scope to get databases by username
     */
    public function scopeForUsername($query, string $username)
    {
        return $query->whereHas('hostingAccount', function ($q) use ($username) {
            $q->where('username', $username);
        });
    }

    /**
     * Scope to get recent databases
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // =============================================================================
    // INSTANCE METHODS
    // =============================================================================

    /**
     * Get database age in days
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if database is recent (created within last 7 days)
     */
    public function isRecent(): bool
    {
        return $this->getAgeInDays() <= 7;
    }

    /**
     * Get connection string for specific database type
     */
    public function getConnectionString(string $type = 'mysql'): string
    {
        $host = $this->mysql_host ?? 'sql111.fhost.click';
        $username = $this->hostingAccount->username;
        $database = $this->full_name;
        
        return match($type) {
            'mysql' => "mysql://{$username}:PASSWORD@{$host}:3306/{$database}",
            'mysqli' => "mysqli://{$username}:PASSWORD@{$host}:3306/{$database}",
            'pdo' => "mysql:host={$host};port=3306;dbname={$database}",
            'dsn' => "mysql:host={$host};dbname={$database};charset=utf8mb4",
            default => "mysql://{$username}:PASSWORD@{$host}:3306/{$database}"
        };
    }
}