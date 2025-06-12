<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MofhApiSetting;
use App\Models\HostingAccount;
use App\Models\SiteProSetting;
use App\Models\HostingSubdomain;
use App\Models\IconCaptchaSetting;
use App\Models\HostingDatabase;
use App\Services\DatabaseSyncService;
use App\Services\MofhService;
use App\Services\NotificationService;
use App\Libraries\VistapanelApi;
use App\Models\AllowedDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\Hosting\AccountCreatedMail;
use App\Mail\Hosting\AccountDeactivatedMail;
use App\Mail\Hosting\AccountReactivatedMail;
use App\Mail\Hosting\PasswordChangedMail;
use Illuminate\Support\Facades\Log;

class HostingController extends Controller
{
    protected $mofhService;
    protected $notificationService;

    public function __construct(MofhService $mofhService, NotificationService $notificationService)
    {
        $this->mofhService = $mofhService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display list of user's hosting accounts
     */
    public function index()
    {
        $accounts = HostingAccount::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('hosting.index', compact('accounts'));
    }

    /**
     * Show create hosting form
     */
    public function create()
    {
        if ($this->getActiveAccountsCount() >= 3) {
            return redirect()->route('hosting.index')
                ->with('error', 'You have reached the maximum number of allowed hosting accounts (3).');
        }

        $allowedDomains = AllowedDomain::pluck('domain_name');
        return view('hosting.create', compact('allowedDomains'));
    }

    /**
     * Check domain availability
     */
    public function checkDomain(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|max:255',
            'ext' => 'required|string|max:255'
        ]);

        $domain = $request->domain . $request->ext;
        
        $result = $this->mofhService->checkDomain($domain);

        if ($result === true) {
            Session::put('domain', $domain);
            return back()->with('success', 'Domain is available and has been selected.');
        }
        
        if (is_string($result)) {
            return back()->withErrors(['domain' => $result])->withInput();
        }

        return back()->withErrors(['domain' => 'Domain is not available.'])->withInput();
    }

    /**
     * Store new hosting account
     */
    public function store(Request $request)
    {
        try {
            if ($this->getActiveAccountsCount() >= 3) {
                return redirect()->route('hosting.index')
                    ->with('error', 'You have reached the maximum number of allowed hosting accounts (3).');
            }

            if (!Session::has('domain')) {
                return redirect()->route('hosting.create')
                    ->withErrors(['error' => 'Please check domain availability first.']);
            }

            // Verify captcha if enabled
            if (IconCaptchaSetting::isEnabled('enabled', true)) {
                if ($request->input('ic-hp') !== '1') {
                    return back()->withErrors(['error' => 'Please complete the CAPTCHA verification first.'])
                                ->withInput($request->except(['password']));
                }
            }

            $request->validate([
                'label' => 'required|string|max:255'
            ]);

            DB::beginTransaction();

            $result = $this->mofhService->createAccount(
                $request->label,
                Session::get('domain'),
                auth()->user()->email
            );

            if ($result['success']) {
                try {
                    Mail::to(auth()->user()->email)
                        ->send(new AccountCreatedMail($result['account']));

                    \Log::info('Hosting account creation email sent', [
                        'account_id' => $result['account']->id,
                        'user_email' => auth()->user()->email
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to send hosting creation email', [
                        'error' => $e->getMessage()
                    ]);
                }

                $this->notificationService->createHostingNotification(
                    auth()->user(), 
                    'created', 
                    [
                        'domain' => $result['account']->domain,
                        'username' => $result['account']->username
                    ]
                );

                Session::forget('domain');
                DB::commit();
                
                return redirect()->route('hosting.view', $result['account']->username)
                    ->with('success', $result['message']);
            }

            DB::rollback();
            return back()
                ->withErrors(['error' => $result['message']])
                ->withInput();

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating hosting account: ' . $e->getMessage());
            return back()
                ->withErrors(['error' => 'Error creating account. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show account details
     */
    public function view($username)
    {
        try {
            $account = HostingAccount::where('user_id', auth()->id())
                ->where('username', $username)
                ->first();

            if (!$account) {
                return redirect()->route('hosting.index')
                    ->withErrors(['error' => 'Hosting account not found.']);
            }

            // Update status if pending
            if ($account->status === 'pending') {
                $this->mofhService->updateAccountStatus($account);
                $account->refresh();
            }

            // Get domains if active
            $domains = $account->status === 'active' ?
                $this->mofhService->getDomains($account->username) : [];

            // Get Server IP
            try {
                $domain = $account->domain;
                $ip = gethostbyname($domain);
                $serverIp = ($ip !== $domain) ? $ip : false;
            } catch (\Exception $e) {
                \Log::error('Error getting server IP', [
                    'account_id' => $account->id,
                    'domain' => $account->domain,
                    'error' => $e->getMessage()
                ]);
                $serverIp = false;
            }

            return view('hosting.view', compact('account', 'domains', 'serverIp'));

        } catch (\Exception $e) {
            \Log::error('Error in view method', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('hosting.index')
                ->withErrors(['error' => 'Error loading account details.']);
        }
    }

    /**
     * Get current account statistics - Core Stats Only (Disk, Bandwidth, Inodes)
     */
    public function getStats($username)
    {
        try {
            $account = HostingAccount::where('user_id', auth()->id())
                ->where('username', $username)
                ->firstOrFail();

            if ($account->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stats are only available for active accounts.'
                ]);
            }

            // Get MOFH settings
            $settings = MofhApiSetting::first();
            if (!$settings) {
                throw new \Exception('MOFH API settings not configured.');
            }

            // Get stats from Vistapanel API
            $api = new VistapanelApi();
            $api->setCpanelUrl($settings->cpanel_url);

            if (!$api->login($account->username, $account->password)) {
                throw new \Exception('Failed to login to cPanel');
            }

            // Get formatted stats (disk, bandwidth, inodes only)
            $result = $api->getFormattedStats();
            $api->logout();

            $stats = $result['stats'];
            $accountDetails = $result['account_details'];

            // Update account with SQL server if available
            if (!empty($accountDetails['MySQL hostname'])) {
                if (preg_match('/^(sql\d+)\./', $accountDetails['MySQL hostname'], $matches)) {
                    $sqlServer = $matches[1];
                    if ($account->sql_server !== $sqlServer) {
                        $account->sql_server = $sqlServer;
                        $account->save();
                        
                        \Log::info('Updated SQL server from stats', [
                            'username' => $account->username,
                            'sql_server' => $sqlServer
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
                'account_details' => $accountDetails
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting stats: ' . $e->getMessage(), [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get chart statistics - Core Stats Only
     */
    public function getChartStats($username)
    {
        try {
            $account = HostingAccount::where('user_id', auth()->id())
                ->where('username', $username)
                ->firstOrFail();

            if ($account->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stats are only available for active accounts.'
                ]);
            }

            $settings = MofhApiSetting::first();
            if (!$settings) {
                throw new \Exception('MOFH API settings not configured.');
            }

            $api = new VistapanelApi();
            $api->setCpanelUrl($settings->cpanel_url);

            if (!$api->login($account->username, $account->password)) {
                throw new \Exception('Failed to login to cPanel');
            }

            // Get usage statistics for charts (30 days)
            $stats = $api->get_usage_stats($account->username, $account->password, 30);
            $api->logout();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting chart stats: ' . $e->getMessage(), [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch chart data'
            ]);
        }
    }

    /**
     * Get all stats in one call - Optimized
     */
    public function getAllStats($username)
    {
        try {
            $account = HostingAccount::where('user_id', auth()->id())
                ->where('username', $username)
                ->firstOrFail();

            if ($account->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stats are only available for active accounts.'
                ]);
            }

            $settings = MofhApiSetting::first();
            if (!$settings) {
                throw new \Exception('MOFH API settings not configured.');
            }

            $api = new VistapanelApi();
            $api->setCpanelUrl($settings->cpanel_url);

            if (!$api->login($account->username, $account->password)) {
                throw new \Exception('Failed to login to cPanel');
            }

            // Get all data in one go
            $formattedData = $api->getFormattedStats();
            $chartData = $api->get_usage_stats($account->username, $account->password, 30);
            
            $api->logout();

            // Update SQL server if available
            if (!empty($formattedData['account_details']['MySQL hostname'])) {
                if (preg_match('/^(sql\d+)\./', $formattedData['account_details']['MySQL hostname'], $matches)) {
                    $sqlServer = $matches[1];
                    if ($account->sql_server !== $sqlServer) {
                        $account->sql_server = $sqlServer;
                        $account->save();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'stats' => $formattedData['stats'],
                'account_details' => $formattedData['account_details'],
                'chart_data' => $chartData,
                'core_limits' => [
                    'disk_quota' => $formattedData['stats']['disk']['total'],
                    'bandwidth' => $formattedData['stats']['bandwidth']['total'],
                    'inodes' => $formattedData['stats']['inodes']['total']
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting all stats: ' . $e->getMessage(), [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show settings page
     */
    public function settings($username)
    {
        try {
            $account = HostingAccount::where('user_id', auth()->id())
                ->where('username', $username)
                ->first();

            if (!$account) {
                return redirect()->route('hosting.index')
                    ->withErrors(['error' => 'Hosting account not found.']);
            }

            if ($account->status !== 'active' && $account->status !== 'deactivated') {
                return redirect()->route('hosting.view', $username)
                    ->with('error', 'You can only modify active or deactivated accounts.');
            }

            return view('hosting.settings', compact('account'));
        } catch (\Exception $e) {
            \Log::error('Error loading settings page', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('hosting.index')
                ->withErrors(['error' => 'Error loading account settings.']);
        }
    }

    /**
     * Update account settings
     */  
    public function updateSettings(Request $request, $identifier)
    {
        try {
            // Simple captcha validation
            if (IconCaptchaSetting::isEnabled('enabled', true)) {
                if ($request->input('ic-hp') !== '1') {
                    return back()->withErrors(['error' => 'Please complete the CAPTCHA verification first.'])
                                ->withInput($request->except(['password', 'old_password']));
                }
            }
            
            // Find account by either username or ID
            $account = HostingAccount::where('user_id', auth()->id())
                ->where(function($q) use ($identifier) {
                    $q->where('username', $identifier)
                    ->orWhere('id', $identifier);
                })->first();
                
            if (!$account) {
                return back()->withErrors(['error' => 'Hosting account not found.']);
            }

            DB::beginTransaction();

            $action = $request->input('action');
            
            if (!$action) {
                DB::rollback();
                return back()->withErrors(['error' => 'No action specified. Please try again.'])
                            ->withInput($request->except(['password', 'old_password']));
            }

            // Handle label update
            if ($action === 'update_label') {
                $request->validate([
                    'label' => 'required|string|max:255'
                ]);

                $account->label = $request->label;
                $account->save();
                
                $this->notificationService->createHostingNotification(
                    auth()->user(), 
                    'label_changed', 
                    [
                        'domain' => $account->domain,
                        'username' => $account->username,
                        'label' => $request->label
                    ]
                );
                
                DB::commit();
                return back()->with('success', 'Account label updated successfully.');
            }
            
            // Handle password update
            if ($action === 'update_password') {
                $request->validate([
                    'password' => 'required|string|min:8',
                    'old_password' => 'required|string'
                ]);

                $result = $this->mofhService->changePassword(
                    $account->username,
                    $request->old_password,
                    $request->password
                );
                
                if (!isset($result['success']) || !$result['success']) {
                    DB::rollback();
                    return back()->withErrors(['error' => $result['message'] ?? 'Failed to change password. Please try again.']);
                }
                
                $account->password = $request->password;
                $account->save();
                
                $this->notificationService->createHostingNotification(
                    auth()->user(), 
                    'password_changed', 
                    [
                        'domain' => $account->domain,
                        'username' => $account->username
                    ]
                );
                
                try {
                    Mail::to(auth()->user()->email)
                            ->send(new PasswordChangedMail($account, $request->password));
                } catch (\Exception $e) {
                    \Log::error('Failed to send password change email', [
                        'account_id' => $account->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                DB::commit();
                return back()->with('success', 'Password changed successfully.');
            }

            // Handle deactivation
            if ($action === 'deactivate') {
                $request->validate([
                    'reason' => 'required|string|min:20'
                ]);

                if ($account->status !== 'active') {
                    DB::rollback();
                    return back()->withErrors(['error' => 'Only active accounts can be deactivated.']);
                }

                $result = $this->mofhService->suspendAccount(
                    $account->username,
                    $request->reason
                );

                if (!isset($result['success']) || !$result['success']) {
                    DB::rollback();
                    return back()->withErrors(['error' => $result['message'] ?? 'Failed to deactivate account. Please try again.']);
                }

                $account->status = 'deactivating';
                $account->save();

                $this->notificationService->createHostingNotification(
                    auth()->user(), 
                    'suspended', 
                    [
                        'domain' => $account->domain,
                        'username' => $account->username
                    ]
                );

                try {
                    Mail::to(auth()->user()->email)
                        ->send(new AccountDeactivatedMail($account, $request->reason));
                } catch (\Exception $e) {
                    \Log::error('Failed to send deactivation email', [
                        'account_id' => $account->id,
                        'error' => $e->getMessage()
                    ]);
                }

                DB::commit();
                return redirect()->route('hosting.view', ['username' => $account->username])
                    ->with('success', 'Account deactivation initiated. Please wait for confirmation.');
            }

            // Unknown action
            DB::rollback();
            return back()->withErrors(['error' => 'Invalid action type.']);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error in updateSettings', [
                'identifier' => $identifier,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Error updating settings. Please try again.']);
        }
    }

    /**
     * Reactivate deactivated account
     */
    public function reactivate($username)
    {
        try {
            $account = HostingAccount::where('user_id', auth()->id())
                ->where('username', $username)
                ->firstOrFail();

            if ($account->status !== 'deactivated') {
                return redirect()->back()
                    ->withErrors(['error' => 'Only deactivated accounts can be reactivated.']);
            }

            // Check if admin deactivated
            if ($account->admin_deactivated) {
                $existingTicket = DB::table('tickets')
                    ->where('user_id', auth()->id())
                    ->where('service_type', 'hosting')
                    ->where('service_id', $account->id)
                    ->whereIn('status', ['open', 'answered', 'customer-reply', 'pending'])
                    ->orderBy('created_at', 'desc')
                    ->first();
                    
                if ($existingTicket) {
                    return redirect()->route('user.tickets.show', $existingTicket->id)
                        ->with('info', "This account was suspended by an administrator. You already have an open support ticket for this account.");
                }
                
                session()->flash('admin_deactivated_account', [
                    'username' => $account->username,
                    'domain' => $account->domain,
                    'deactivation_reason' => $account->admin_deactivation_reason,
                    'deactivated_at' => $account->admin_deactivated_at
                ]);
                
                return redirect()->route('user.tickets.create')
                    ->with('info', "This account was suspended by an administrator. Please submit a support ticket to request reactivation.");
            }

            // Handle normal reactivation
            $activeCount = HostingAccount::where('user_id', auth()->id())
                ->whereIn('status', ['active', 'reactivating'])
                ->count();

            if ($activeCount >= 3) {
                return redirect()->back()
                    ->withErrors(['error' => 'You have reached the maximum number of active accounts (3).']);
            }

            DB::beginTransaction();

            $result = $this->mofhService->reactivateAccount($account->username);

            if (!$result) {
                DB::rollback();
                return redirect()->back()
                    ->withErrors(['error' => 'Failed to reactivate account. Please try again.']);
            }

            $account->status = 'reactivating';
            $account->save();

            $this->notificationService->createHostingNotification(
                auth()->user(), 
                'reactivated', 
                [
                    'domain' => $account->domain,
                    'username' => $account->username
                ]
            );

            try {
                Mail::to(auth()->user()->email)
                    ->send(new AccountReactivatedMail($account));
            } catch (\Exception $e) {
                \Log::error('Failed to send reactivation email', [
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();
            return redirect()->route('hosting.view', $username)
                ->with('success', 'Account reactivation initiated. Please wait for confirmation.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error reactivating account: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error reactivating account. Please try again.']);
        }
    }

    /**
     * Show cPanel login page
     */
    public function cpanel($username)
    {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return back()->with('error', 'Account must be active to access cPanel.');
        }

        $settings = MofhApiSetting::first();
        if (!$settings) {
            return back()->with('error', 'MOFH API settings not configured.');
        }

        $cpanel_url = str_replace(['https://', 'http://'], '', $settings->cpanel_url);

        return view('hosting.cpanel', [
            'username' => $account->username,
            'password' => $account->password,
            'cpanel_url' => $cpanel_url
        ]);
    }

    /**
     * Show file manager login page
     */
    public function fileManager($username)
    {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return back()->with('error', 'Account must be active to access file manager.');
        }

        return view('hosting.filemanager', [
            'username' => $account->username,
            'password' => $account->password,
            'dir' => '/htdocs/'
        ]);
    }

    /**
     * Verify cPanel access
     */
    public function verifyCpanel($username)
    {
        try {
            $account = HostingAccount::where('user_id', auth()->id())
                ->where('username', $username)
                ->firstOrFail();

            if ($account->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account must be active to verify cPanel.'
                ]);
            }

            $account->cpanel_verified = true;
            $account->cpanel_verified_at = now();
            $account->save();

            return response()->json([
                'success' => true,
                'message' => 'cPanel access verified successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('cPanel verification failed', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Access Softaculous installer
     */
    public function softaculous($username)
    {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return back()->with('error', 'Account must be active to access Softaculous.');
        }

        try {
            $settings = MofhApiSetting::first();
            if (!$settings) {
                throw new Exception('MOFH API settings not configured.');
            }

            $api = new VistapanelApi();
            $api->setCpanelUrl($settings->cpanel_url);
            
            if($api->login($account->username, $account->password)) {
                $url = $api->getSoftaculousLink();
                $api->logout();
                
                if(!$url) {
                    throw new Exception('Could not get Softaculous link');
                }
                return redirect()->away($url);
            }

            throw new Exception('Could not login to cPanel');

        } catch (\Exception $e) {
            \Log::error('Softaculous access failed', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to access Softaculous: ' . $e->getMessage());
        }
    }


    
    /**
     * Site builder access
     */
    public function builder($username, $domain)
    {
        try {
            $account = HostingAccount::where('user_id', auth()->id())
                ->where('username', $username)
                ->firstOrFail();

            if ($account->status !== 'active') {
                return redirect()->back()
                    ->with('error', 'Account must be active to use Site.pro builder');
            }

            if (!$account->cpanel_verified) {
                return redirect()->back()
                    ->with('error', 'Please verify your cPanel access first');
            }

            $settings = SiteProSetting::first();
            if (!$settings || !$settings->isActive()) {
                return redirect()->back()
                    ->with('error', 'Site.pro builder is not available');
            }

            $result = $settings->loadBuilderUrl(
                $account->username,
                $account->password, 
                $domain,
                '/htdocs/'
            );

            if (!$result['success']) {
                return redirect()->back()
                    ->with('error', $result['message']);
            }

            return redirect()->away($result['url']);

        } catch (\Exception $e) {
            \Log::error('Error loading Site.pro builder', [
                'username' => $username,
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Failed to load website builder');
        }
    }

    /**
     * Get active accounts count
     */
    protected function getActiveAccountsCount()
    {
        return HostingAccount::where('user_id', auth()->id())
            ->whereIn('status', ['active', 'reactivating'])
            ->count();
    }

    /**
     * Clear domain session and redirect back
     */
    public function cancel()
    {
        Session::forget('domain');
        return redirect()->route('hosting.create');
    }
    
  



/**
 * Get database statistics 
 */
public function getDatabaseStats($username)
{
    try {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        Log::debug('Getting database statistics from local storage', [
            'username' => $username,
            'user_id' => auth()->id()
        ]);

        $stats = $account->database_stats;
        $databases = $account->formatted_databases;

        // Add MySQL host info
        $mysqlHost = $account->databases()->value('mysql_host') ?? 'sql111.fhost.click';

        return response()->json([
            'success' => true,
            'databases' => $databases,
            'current_usage' => $stats['current_usage'],
            'max_databases' => $stats['max_databases'],
            'available' => $stats['available'],
            'usage_percent' => $stats['usage_percent'],
            'mysql_host' => $mysqlHost,
            'source' => 'local_database',
            'last_updated' => $account->databases()->max('updated_at')
        ]);

    } catch (Exception $e) {
        Log::error('Error getting database statistics', [
            'username' => $username,
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to get database statistics: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get databases 
 */
public function databases($username)
{
    try {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->with('databases') // Eager load databases
            ->firstOrFail();

        Log::info('Loading databases from local storage', [
            'username' => $username,
            'user_id' => auth()->id()
        ]);

        $databases = $account->formatted_databases;
        $stats = $account->database_stats;

        return response()->json([
            'success' => true,
            'databases' => $databases,
            'stats' => $stats,
            'source' => 'local_database',
            'last_sync' => $account->databases()->max('updated_at'),
            'note' => 'phpMyAdmin links are generated on-demand for security'
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Hosting account not found.'
        ], 404);
        
    } catch (Exception $e) {
        Log::error('Error loading databases from storage', [
            'username' => $username,
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load databases: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Create database 
 */
public function createDatabase(Request $request, $username)
{
    $request->validate([
        'database_name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
    ]);

    try {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account must be active to create databases.'
            ], 403);
        }

        if (!$account->cpanel_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your cPanel access first.'
            ], 403);
        }

        $databaseName = $request->database_name;
        
        if ($account->hasDatabaseNamed($databaseName)) {
            return response()->json([
                'success' => false,
                'message' => "Database '{$databaseName}' already exists."
            ], 409);
        }

        // Check limits
        $stats = $account->database_stats;
        if (!$stats['can_create_more']) {
            return response()->json([
                'success' => false,
                'message' => "Database limit reached ({$stats['current_usage']}/{$stats['max_databases']})."
            ], 403);
        }

        $settings = MofhApiSetting::first();
        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'MOFH API settings not configured.'
            ], 500);
        }

        $api = new VistapanelApi();
        $api->setCpanelUrl($settings->cpanel_url);

        if (!$api->login($account->username, $account->password)) {
            throw new Exception('Failed to login to cPanel');
        }

        try {
            Log::info('Creating database via API', [
                'username' => $username,
                'database_name' => $databaseName,
                'user_id' => auth()->id()
            ]);

            $api->createDatabase($databaseName);

            $database = HostingDatabase::createForAccount($account, $databaseName, [
                'mysql_host' => $api->getMysqlHost()
            ]);

            Log::info('Database created successfully', [
                'username' => $username,
                'database_name' => $databaseName,
                'full_name' => $database->full_name,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Database '{$databaseName}' created successfully.",
                'database' => $database->formatted_info,
                'stats' => $account->fresh()->database_stats // Refresh stats
            ]);

        } finally {
            $api->logout();
        }

    } catch (Exception $e) {
        Log::error('Database creation failed', [
            'username' => $username,
            'database_name' => $request->database_name,
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create database: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Delete database 
 */
public function deleteDatabase(Request $request, $username)
{
    $request->validate([
        'database_name' => 'required|string',
    ]);

    try {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account must be active to delete databases.'
            ], 403);
        }

        if (!$account->cpanel_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your cPanel access first.'
            ], 403);
        }

        $databaseName = $request->database_name;
        
        // Extract clean database name if full name is provided
        $cleanDbName = HostingDatabase::extractDatabaseName($databaseName, $account->username);
        
        $localDatabase = $account->getDatabaseByName($cleanDbName);
        if (!$localDatabase) {
            return response()->json([
                'success' => false,
                'message' => "Database '{$cleanDbName}' not found."
            ], 404);
        }

        $settings = MofhApiSetting::first();
        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'MOFH API settings not configured.'
            ], 500);
        }

        $api = new VistapanelApi();
        $api->setCpanelUrl($settings->cpanel_url);

        if (!$api->login($account->username, $account->password)) {
            throw new Exception('Failed to login to cPanel');
        }

        try {
            Log::info('Deleting database via API', [
                'username' => $username,
                'database_name' => $cleanDbName,
                'full_name' => $localDatabase->full_name,
                'user_id' => auth()->id()
            ]);

            $api->deleteDatabase($cleanDbName);

            $localDatabase->delete();

            Log::info('Database deleted successfully', [
                'username' => $username,
                'database_name' => $cleanDbName,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Database '{$cleanDbName}' deleted successfully.",
                'stats' => $account->fresh()->database_stats // Refresh stats
            ]);

        } finally {
            $api->logout();
        }

    } catch (Exception $e) {
        Log::error('Database deletion failed', [
            'username' => $username,
            'database_name' => $request->database_name,
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete database: ' . $e->getMessage()
        ], 500);
    }
}

    
/**
 *  phpMyAdmin 
 */
public function phpMyAdmin($username)
{
    try {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return back()->with('error', 'Account must be active to access phpMyAdmin.');
        }

        if (!$account->cpanel_verified) {
            return back()->with('error', 'Please verify your cPanel access first.');
        }

        $settings = MofhApiSetting::first();
        if (!$settings) {
            return back()->with('error', 'MOFH API settings not configured.');
        }

        Log::info('General phpMyAdmin access requested', [
            'username' => $username,
            'user_id' => auth()->id()
        ]);

        $api = new VistapanelApi();
        $api->setCpanelUrl($settings->cpanel_url);

        if (!$api->login($account->username, $account->password)) {
            throw new Exception('Failed to login to cPanel');
        }

        try {
            $phpMyAdminUrl = $api->getGeneralPhpMyAdminLink();
            
            Log::info('General phpMyAdmin link obtained from API', [
                'username' => $account->username,
                'url_length' => strlen($phpMyAdminUrl)
            ]);
            
            return redirect()->away($phpMyAdminUrl);
            
        } finally {
            $api->logout();
        }

    } catch (\Exception $e) {
        Log::error('Error accessing phpMyAdmin', [
            'username' => $username,
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return back()->with('error', 'Failed to access phpMyAdmin: ' . $e->getMessage());
    }
}


/**
 * Get phpMyAdmin 
 */
public function getPhpMyAdminLink(Request $request, $username)
{
    try {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active' || !$account->cpanel_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Account must be active and verified to access phpMyAdmin.'
            ], 403);
        }

        $databaseName = $request->input('database');
        if (empty($databaseName)) {
            return response()->json([
                'success' => false,
                'message' => 'Database name is required.'
            ], 400);
        }

        // Extract clean database name
        $cleanDbName = HostingDatabase::extractDatabaseName($databaseName, $account->username);
        
        $localDatabase = $account->getDatabaseByName($cleanDbName);
        if (!$localDatabase) {
            return response()->json([
                'success' => false,
                'message' => "Database '{$cleanDbName}' not found."
            ], 404);
        }

        $settings = MofhApiSetting::first();
        if (!$settings) {
            throw new Exception('MOFH API settings not configured.');
        }

        Log::info('Fetching fresh phpMyAdmin link from cPanel', [
            'username' => $username,
            'database' => $cleanDbName,
            'user_id' => auth()->id()
        ]);

        $api = new VistapanelApi();
        $api->setCpanelUrl($settings->cpanel_url);

        if (!$api->login($account->username, $account->password)) {
            throw new Exception('Failed to login to cPanel');
        }

        try {
            $phpMyAdminUrl = $api->getPhpmyadminLink($cleanDbName);
            
            Log::info('Fresh phpMyAdmin link obtained successfully', [
                'username' => $username,
                'database' => $cleanDbName,
                'url_length' => strlen($phpMyAdminUrl)
            ]);
            
            return response()->json([
                'success' => true,
                'url' => $phpMyAdminUrl,
                'database' => $databaseName,
                'source' => 'fresh_from_cpanel'
            ]);
            
        } finally {
            $api->logout();
        }

    } catch (Exception $e) {
        Log::error('Error getting phpMyAdmin link', [
            'username' => $username,
            'database' => $request->input('database'),
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to get phpMyAdmin link: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Sync databases with cPanel (manual sync)
 */
public function syncDatabases($username)
{
    try {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active' || !$account->cpanel_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Account must be active and verified to sync databases.'
            ], 403);
        }

        Log::info('Manual database sync initiated', [
            'username' => $username,
            'user_id' => auth()->id()
        ]);

        $syncService = new DatabaseSyncService();
        $results = $syncService->syncDatabasesForAccount($account);

        return response()->json([
            'success' => true,
            'message' => 'Database sync completed successfully.',
            'results' => $results,
            'stats' => $account->fresh()->database_stats
        ]);

    } catch (Exception $e) {
        Log::error('Database sync failed', [
            'username' => $username,
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Database sync failed: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Auto-sync databases when account is loaded (background process)
 */
public function autoSyncDatabases($username)
{
    try {
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        // Only auto-sync if last sync was more than 1 hour ago
        $lastSync = $account->databases()->max('updated_at');
        if ($lastSync && now()->diffInHours($lastSync) < 1) {
            return response()->json([
                'success' => true,
                'message' => 'Sync not needed, data is fresh.',
                'last_sync' => $lastSync
            ]);
        }

        if ($account->status !== 'active' || !$account->cpanel_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Account not ready for sync.'
            ], 403);
        }

        // Use quick sync (only add missing, don't remove)
        $syncService = new DatabaseSyncService();
        $results = $syncService->quickSyncForAccount($account);

        return response()->json([
            'success' => true,
            'message' => 'Auto-sync completed.',
            'results' => $results
        ]);

    } catch (Exception $e) {
        Log::warning('Auto-sync failed (non-critical)', [
            'username' => $username,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Auto-sync failed: ' . $e->getMessage()
        ], 500);
    }
}


// =============================================================================
// SUBDOMAIN MANAGEMENT METHODS
// =============================================================================

/**
 * Get all subdomains for the specified hosting account.
 *
 * @param string $username The hosting account username
 * @return \Illuminate\Http\JsonResponse
 */
public function getSubdomains($username)
{
    try {
        // Find and validate the hosting account
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Subdomains are only available for active hosting accounts.'
            ]);
        }

        // Get subdomains from database with proper ordering
        $subdomains = $account->subdomains()
            ->orderBy('created_at', 'desc')
            ->get();

        // Prepare statistics
        $stats = HostingSubdomain::getUsageStatsForAccount($account);

        \Log::info('Subdomains retrieved successfully', [
            'account_id' => $account->id,
            'username' => $username,
            'count' => $subdomains->count()
        ]);

        return response()->json([
            'success' => true,
            'data' => $subdomains->map->formatted_info,
            'stats' => $stats,
            'message' => 'Subdomains loaded successfully'
        ]);

    } catch (\Exception $e) {
        \Log::error('Error retrieving subdomains', [
            'username' => $username,
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch subdomains: ' . $e->getMessage()
        ]);
    }
}

/**
 * Create a new subdomain for the specified hosting account.
 *
 * @param \Illuminate\Http\Request $request
 * @param string $username The hosting account username
 * @return \Illuminate\Http\JsonResponse
 */
public function createSubdomain(Request $request, $username)
{
    try {
        // Find and validate the hosting account
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account must be active to create subdomains.'
            ]);
        }

        if (!$account->cpanel_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your cPanel access before creating subdomains.'
            ]);
        }

        // Validate request data
        $request->validate([
            'subdomain_name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]$/'
            ],
            'domain_extension' => [
                'required',
                'string',
                'exists:allowed_domains,domain_name'
            ]
        ], [
            'subdomain_name.regex' => 'Subdomain name must start and end with letters or numbers, and can only contain letters, numbers, and hyphens.',
            'domain_extension.exists' => 'Selected domain extension is not available.'
        ]);

        $subdomainName = strtolower(trim($request->subdomain_name));
        $domainExtension = $request->domain_extension;

        // Validate subdomain using model validation
        $validationErrors = HostingSubdomain::validateForCreation($account, $subdomainName);
        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $validationErrors)
            ]);
        }

        // Get MOFH API settings
        $settings = MofhApiSetting::first();
        if (!$settings) {
            throw new \Exception('MOFH API settings are not configured. Please contact support.');
        }

        // Initialize cPanel API
        $api = new VistapanelApi();
        $api->setCpanelUrl($settings->cpanel_url);

        if (!$api->login($account->username, $account->password)) {
            throw new \Exception('Failed to authenticate with cPanel. Please try again.');
        }

        try {
            // Generate full domain name
            $fullDomain = HostingSubdomain::generateFullDomain(
                $subdomainName, 
                $account->domain, 
                $domainExtension
            );
            
            \Log::info('Creating subdomain via cPanel API', [
                'account_id' => $account->id,
                'subdomain_name' => $subdomainName,
                'full_domain' => $fullDomain
            ]);

            // Create subdomain in cPanel
            $success = $api->createSubdomain(
                $subdomainName, 
                $account->domain . $domainExtension,
                "/htdocs/{$subdomainName}"
            );
            
            if (!$success) {
                throw new \Exception('Failed to create subdomain in cPanel.');
            }
            
            // Logout from cPanel API
            $api->logout();

            // Create record in local database
            $subdomain = HostingSubdomain::createForAccount(
                $account, 
                $subdomainName, 
                $domainExtension
            );

            \Log::info('Subdomain created successfully', [
                'account_id' => $account->id,
                'subdomain_id' => $subdomain->id,
                'full_domain' => $fullDomain
            ]);

            return response()->json([
                'success' => true,
                'message' => "Subdomain '{$subdomainName}' created successfully!",
                'data' => $subdomain->formatted_info
            ]);

        } catch (\Exception $e) {
            // Ensure API logout on error
            $api->logout();
            throw $e;
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed: ' . implode(' ', $e->validator->errors()->all())
        ]);

    } catch (\Exception $e) {
        \Log::error('Error creating subdomain', [
            'username' => $username,
            'subdomain_name' => $request->subdomain_name ?? 'unknown',
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create subdomain: ' . $e->getMessage()
        ]);
    }
}

/**
 * Delete a subdomain from the hosting account.
 *
 * @param string $username The hosting account username
 * @param int $subdomainId The subdomain ID to delete
 * @return \Illuminate\Http\JsonResponse
 */
public function deleteSubdomain($username, $subdomainId)
{
    try {
        // Find and validate the hosting account
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        // Find the subdomain
        $subdomain = HostingSubdomain::where('hosting_account_id', $account->id)
            ->where('id', $subdomainId)
            ->firstOrFail();

        $subdomainName = $subdomain->subdomain_name;
        $fullDomain = $subdomain->full_domain;

        // Check if subdomain can be deleted
        $deletionCheck = $subdomain->canBeDeleted();
        if (!$deletionCheck['can_delete']) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete subdomain: ' . implode(', ', $deletionCheck['reasons'])
            ]);
        }

        \Log::info('Starting subdomain deletion process', [
            'account_id' => $account->id,
            'subdomain_id' => $subdomain->id,
            'full_domain' => $fullDomain
        ]);

        // Try to delete from cPanel first
        $cpanelDeletionSuccess = false;
        $settings = MofhApiSetting::first();
        
        if ($settings) {
            try {
                $api = new VistapanelApi();
                $api->setCpanelUrl($settings->cpanel_url);

                if ($api->login($account->username, $account->password)) {
                    $api->deleteSubdomain($fullDomain);
                    $api->logout();
                    
                    $cpanelDeletionSuccess = true;
                    \Log::info('Subdomain deleted from cPanel successfully', [
                        'subdomain' => $fullDomain
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to delete subdomain from cPanel, continuing with local deletion', [
                    'subdomain' => $fullDomain,
                    'error' => $e->getMessage()
                ]);
                // Continue with local deletion even if cPanel deletion fails
            }
        }

        // Delete from local database
        $subdomain->delete();

        \Log::info('Subdomain deleted successfully', [
            'account_id' => $account->id,
            'subdomain' => $fullDomain,
            'cpanel_deleted' => $cpanelDeletionSuccess
        ]);

        return response()->json([
            'success' => true,
            'message' => "Subdomain '{$subdomainName}' has been deleted successfully!",
            'data' => [
                'deleted_subdomain' => $subdomainName,
                'cpanel_deleted' => $cpanelDeletionSuccess
            ]
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Subdomain not found or you do not have permission to delete it.'
        ]);

    } catch (\Exception $e) {
        \Log::error('Error deleting subdomain', [
            'username' => $username,
            'subdomain_id' => $subdomainId,
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete subdomain: ' . $e->getMessage()
        ]);
    }
}

/**
 * Toggle the active status of a subdomain.
 *
 * @param string $username The hosting account username
 * @param int $subdomainId The subdomain ID to toggle
 * @return \Illuminate\Http\JsonResponse
 */
public function toggleSubdomain($username, $subdomainId)
{
    try {
        // Find and validate the hosting account
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        // Find the subdomain
        $subdomain = HostingSubdomain::where('hosting_account_id', $account->id)
            ->where('id', $subdomainId)
            ->firstOrFail();

        $previousStatus = $subdomain->is_active;
        
        // Toggle the status
        $subdomain->toggleStatus();

        \Log::info('Subdomain status toggled', [
            'account_id' => $account->id,
            'subdomain_id' => $subdomain->id,
            'subdomain' => $subdomain->full_domain,
            'previous_status' => $previousStatus,
            'new_status' => $subdomain->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subdomain status updated successfully!',
            'data' => $subdomain->fresh()->formatted_info
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Subdomain not found or you do not have permission to modify it.'
        ]);

    } catch (\Exception $e) {
        \Log::error('Error toggling subdomain status', [
            'username' => $username,
            'subdomain_id' => $subdomainId,
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update subdomain status: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get available domain extensions for subdomain creation.
 *
 * @param string $username The hosting account username
 * @return \Illuminate\Http\JsonResponse
 */
public function getSubdomainExtensions($username)
{
    try {
        // Validate the hosting account exists and belongs to user
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        // Get available extensions from AllowedDomain settings
        $extensions = HostingSubdomain::getAvailableExtensions();

        if (empty($extensions)) {
            return response()->json([
                'success' => false,
                'message' => 'No domain extensions are currently available. Please contact support.'
            ]);
        }

        \Log::debug('Domain extensions retrieved', [
            'account_id' => $account->id,
            'extensions_count' => count($extensions)
        ]);

        return response()->json([
            'success' => true,
            'data' => $extensions,
            'message' => 'Domain extensions loaded successfully'
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Hosting account not found or you do not have permission to access it.'
        ]);

    } catch (\Exception $e) {
        \Log::error('Error retrieving domain extensions', [
            'username' => $username,
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load domain extensions: ' . $e->getMessage()
        ]);
    }
}

/**
 * Synchronize subdomains with cPanel.
 *
 * @param string $username The hosting account username
 * @return \Illuminate\Http\JsonResponse
 */
public function syncSubdomains($username)
{
    try {
        // Find and validate the hosting account
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account must be active to synchronize subdomains.'
            ]);
        }

        \Log::info('Starting subdomain synchronization', [
            'account_id' => $account->id,
            'username' => $username
        ]);

        // Get MOFH API settings
        $settings = MofhApiSetting::first();
        if (!$settings) {
            throw new \Exception('MOFH API settings are not configured.');
        }

        // Initialize cPanel API
        $api = new VistapanelApi();
        $api->setCpanelUrl($settings->cpanel_url);

        if (!$api->login($account->username, $account->password)) {
            throw new \Exception('Failed to authenticate with cPanel for synchronization.');
        }

        try {
            // Get subdomains from cPanel
            $remoteSubdomains = $api->getFormattedSubdomains();
            $api->logout();

            $syncedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $extensions = HostingSubdomain::getAvailableExtensions();

            // Process each remote subdomain
            foreach ($remoteSubdomains as $remoteSub) {
                $domainName = $remoteSub['name'] ?? '';
                if (empty($domainName)) {
                    $skippedCount++;
                    continue;
                }

                try {
                    // Extract subdomain name and extension
                    $mainDomain = $account->domain;
                    $subdomainCreated = false;
                    
                    foreach ($extensions as $extension) {
                        $fullMainDomain = $mainDomain . $extension;
                        
                        if (str_ends_with($domainName, $fullMainDomain)) {
                            $subdomainName = str_replace('.' . $fullMainDomain, '', $domainName);
                            
                            // Validate subdomain name
                            if (empty($subdomainName) || !HostingSubdomain::isValidSubdomainName($subdomainName)) {
                                $skippedCount++;
                                break;
                            }
                            
                            // Check if already exists locally
                            $exists = HostingSubdomain::where('hosting_account_id', $account->id)
                                ->where('subdomain_name', $subdomainName)
                                ->where('domain_extension', $extension)
                                ->exists();
                            
                            if (!$exists) {
                                HostingSubdomain::createForAccount($account, $subdomainName, $extension);
                                $syncedCount++;
                                $subdomainCreated = true;
                            } else {
                                $skippedCount++;
                            }
                            break;
                        }
                    }
                    
                    if (!$subdomainCreated && $skippedCount === 0) {
                        $skippedCount++;
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    \Log::warning('Error processing subdomain during sync', [
                        'subdomain' => $domainName,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            \Log::info('Subdomain synchronization completed', [
                'account_id' => $account->id,
                'remote_count' => count($remoteSubdomains),
                'synced_count' => $syncedCount,
                'skipped_count' => $skippedCount,
                'error_count' => $errorCount
            ]);

            $message = "Synchronization completed! ";
            if ($syncedCount > 0) {
                $message .= "{$syncedCount} new subdomain(s) added. ";
            }
            if ($skippedCount > 0) {
                $message .= "{$skippedCount} subdomain(s) already exist or were skipped. ";
            }
            if ($errorCount > 0) {
                $message .= "{$errorCount} subdomain(s) had errors during processing.";
            }

            return response()->json([
                'success' => true,
                'message' => trim($message),
                'data' => [
                    'remote_count' => count($remoteSubdomains),
                    'synced_count' => $syncedCount,
                    'skipped_count' => $skippedCount,
                    'error_count' => $errorCount
                ]
            ]);

        } catch (\Exception $e) {
            $api->logout();
            throw $e;
        }

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Hosting account not found or you do not have permission to access it.'
        ]);

    } catch (\Exception $e) {
        \Log::error('Error synchronizing subdomains', [
            'username' => $username,
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to synchronize subdomains: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get detailed subdomain usage statistics.
 *
 * @param string $username The hosting account username
 * @return \Illuminate\Http\JsonResponse
 */
public function getSubdomainUsage($username)
{
    try {
        // Find and validate the hosting account
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Usage statistics are only available for active accounts.'
            ]);
        }

        // Get local statistics
        $localStats = HostingSubdomain::getUsageStatsForAccount($account);

        // Try to get remote statistics from cPanel
        $remoteStats = null;
        try {
            $settings = MofhApiSetting::first();
            if ($settings) {
                $api = new VistapanelApi();
                $api->setCpanelUrl($settings->cpanel_url);

                if ($api->login($account->username, $account->password)) {
                    $remoteUsage = $api->getSubdomainUsage();
                    $api->logout();
                    
                    $remoteStats = [
                        'remote_usage' => $remoteUsage['current_usage'] ?? 0,
                        'remote_limit' => $remoteUsage['max_subdomains'] ?? 10,
                        'remote_available' => $remoteUsage['available'] ?? 10,
                        'remote_unlimited' => $remoteUsage['is_unlimited'] ?? false
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to retrieve remote subdomain usage statistics', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
        }

        // Combine local and remote statistics
        $combinedStats = array_merge($localStats, $remoteStats ?? []);

        return response()->json([
            'success' => true,
            'data' => $combinedStats,
            'message' => 'Usage statistics retrieved successfully'
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Hosting account not found or you do not have permission to access it.'
        ]);

    } catch (\Exception $e) {
        \Log::error('Error retrieving subdomain usage statistics', [
            'username' => $username,
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve usage statistics: ' . $e->getMessage()
        ]);
    }
}

/**
 * Validate subdomain name via API call.
 *
 * @param \Illuminate\Http\Request $request
 * @param string $username The hosting account username
 * @return \Illuminate\Http\JsonResponse
 */
public function validateSubdomainName(Request $request, $username)
{
    try {
        // Validate request
        $request->validate([
            'subdomain_name' => 'required|string|max:50'
        ]);

        // Find and validate the hosting account
        $account = HostingAccount::where('user_id', auth()->id())
            ->where('username', $username)
            ->firstOrFail();

        $subdomainName = strtolower(trim($request->subdomain_name));

        // Perform validation
        $validationErrors = HostingSubdomain::validateForCreation($account, $subdomainName);

        return response()->json([
            'success' => empty($validationErrors),
            'valid' => empty($validationErrors),
            'errors' => $validationErrors,
            'message' => empty($validationErrors) ? 'Subdomain name is valid' : 'Validation failed'
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'valid' => false,
            'errors' => $e->validator->errors()->all(),
            'message' => 'Invalid request data'
        ]);

    } catch (\Exception $e) {
        \Log::error('Error validating subdomain name', [
            'username' => $username,
            'subdomain_name' => $request->subdomain_name ?? 'unknown',
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'valid' => false,
            'errors' => ['An error occurred during validation'],
            'message' => 'Validation failed due to server error'
        ]);
    }
}
}