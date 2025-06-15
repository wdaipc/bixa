@extends('layouts.master')

@section('title') @lang('translation.Account_Details') @endsection

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17/dist/sweetalert2.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js"></script>

<style>
:root {
    --minia-primary: #4B38B3;
    --minia-secondary: #74788D;
    --minia-success: #45CB85;
    --minia-info: #68E1FD;
    --minia-warning: #FFB902;
    --minia-danger: #F06548;
    --minia-light: #F3F6F9;
    --minia-dark: #2D3646;
}

body {
    background-color: #f8f8fb;
    font-family: "Poppins", sans-serif;
}

.card {
    box-shadow: 0 1px 2px rgba(56,65,74,0.15);
    border: none;
    margin-bottom: 24px;
}

.card-header {
    background: #fff;
    border-bottom: 1px solid #e9e9ef;
    padding: 1rem 1.25rem;
}

.stats-card {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #fff 0%, rgba(248,250,252,0.5) 100%);
    border: 1px solid rgba(233,233,239,0.8);
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.3s ease;
}

.stats-card:not(.locked):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(75,56,179,0.1);
}

.stats-card.locked {
    opacity: 0.7;
    cursor: not-allowed;
}

.stats-card.locked .icon-box {
    background: #6c757d !important;
}

.stats-card.locked:hover {
    transform: none !important;
    box-shadow: 0 1px 2px rgba(56,65,74,0.15) !important;
}

.stats-card::before {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    width: 6rem;
    height: 6rem;
    background: linear-gradient(135deg, var(--minia-primary), rgba(75,56,179,0.1));
    border-radius: 0 0 0 100%;
    opacity: 0.1;
}

.stats-card .icon-box {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--minia-primary), #5a4cd6);
    color: white;
    margin-bottom: 1rem;
}

.stats-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--minia-dark);
    margin-bottom: 0.25rem;
}

.stats-label {
    color: var(--minia-secondary);
    font-size: 0.875rem;
    font-weight: 500;
}

.verification-banner {
    background: linear-gradient(135deg, rgba(255,185,0,0.1), rgba(255,185,0,0.05));
    border-left: 4px solid var(--minia-warning);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.verification-banner.show {
    opacity: 1;
    transform: translateY(0);
}

.quick-action-card {
    background: linear-gradient(135deg, #fff 0%, rgba(248,250,252,0.5) 100%);
    border: 1px solid rgba(233,233,239,0.8);
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: block;
}

.quick-action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(75,56,179,0.1);
    border-color: var(--minia-primary);
    color: inherit;
}

.quick-action-card .icon-box {
    width: 48px;
    height: 48px;
    margin: 0 auto 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--minia-primary), #5a4cd6);
    color: white;
    transition: all 0.3s ease;
}

.quick-action-card:hover .icon-box {
    transform: scale(1.1);
}

/* Feature Locking Styles */
.feature-locked .feature-btn {
    position: relative;
    opacity: 0.6;
    cursor: not-allowed !important;
    pointer-events: none;
}

.feature-locked .feature-btn::before {
    content: "Verify cPanel first";
    position: absolute;
    top: -35px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s ease;
    z-index: 10;
    pointer-events: auto;
}

.feature-locked .feature-btn:hover::before {
    opacity: 1;
}

.disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
    text-decoration: none !important;
}

.disabled:hover {
    opacity: 0.6 !important;
    transform: none !important;
    box-shadow: none !important;
}

.quick-action-card.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
    background-color: #f8f9fa !important;
    border-color: #e9ecef !important;
}

.quick-action-card.disabled .icon-box {
    background: #6c757d !important;
}

.quick-action-card.disabled:hover {
    transform: none !important;
    box-shadow: none !important;
}

.database-section-locked, .statistics-section-locked, .account-section-locked, .ftp-section-locked {
    position: relative;
    opacity: 0.5;
    pointer-events: none;
}

.database-section-locked::before, .statistics-section-locked::before, .account-section-locked::before, .ftp-section-locked::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 10;
}

.database-section-locked::after {
    content: "🔒 Verify cPanel to unlock database management";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    font-weight: 600;
    color: #856404;
    z-index: 11;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.statistics-section-locked::after {
    content: "📊 Verify cPanel to unlock usage statistics";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    font-weight: 600;
    color: #856404;
    z-index: 11;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.account-section-locked::after {
    content: "👤 Verify cPanel to unlock account details";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    font-weight: 600;
    color: #856404;
    z-index: 11;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.ftp-section-locked::after {
    content: "📁 Verify cPanel to unlock FTP details";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    font-weight: 600;
    color: #856404;
    z-index: 11;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.copy-field {
    position: relative;
}

.copy-input {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.875rem;
}

.copy-input:hover {
    background-color: #e9ecef;
    border-color: var(--minia-primary);
}

.copy-input.copied {
    background-color: #d1f2eb;
    border-color: var(--minia-success);
    color: var(--minia-success);
}

.position-relative .btn-link {
    background: none !important;
    border: none !important;
    box-shadow: none !important;
    transition: color 0.2s ease;
}

.position-relative .btn-link:hover {
    color: var(--minia-primary) !important;
}

.position-relative .btn-link:focus {
    box-shadow: none !important;
}

.alert {
    border: none;
    border-radius: 8px;
    padding: 12px 16px;
}

.alert-info {
    background-color: rgba(13, 110, 253, 0.1);
    color: #084298;
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #664d03;
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #721c24;
}

.alert i {
    font-size: 1.1em;
}

.password-toggle {
    cursor: pointer;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-left: none;
    padding: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.password-toggle:hover {
    background-color: #e9ecef;
}

.progress-custom {
    height: 8px;
    background-color: rgba(75,56,179,0.1);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-custom {
    height: 100%;
    background: linear-gradient(90deg, var(--minia-primary), #5a4cd6);
    border-radius: 4px;
    transition: width 0.6s ease;
}

.db-stats-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}

.db-stats-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--minia-dark);
    margin-bottom: 0.25rem;
}

.db-stats-label {
    font-size: 0.75rem;
    color: var(--minia-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.connection-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.form-prefix {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-right: none;
    padding: 0.75rem;
    display: flex;
    align-items: center;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.875rem;
    color: var(--minia-secondary);
    border-radius: 6px 0 0 6px;
}

.chart-container {
    height: 280px;
    width: 100%;
}

.toast-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    background: white;
    color: #2d3646;
    border: 1px solid #e9ecef;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
}

.toast-notification.show {
    transform: translateY(0);
    opacity: 1;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-active {
    background: rgba(69,203,133,0.1);
    color: var(--minia-success);
}

.status-suspended {
    background: rgba(240,101,72,0.1);
    color: var(--minia-danger);
}

.status-pending {
    background: rgba(255,185,2,0.1);
    color: var(--minia-warning);
}

.btn-soft-primary {
    color: var(--minia-primary);
    background-color: rgba(75,56,179,0.1);
    border: 1px solid rgba(75,56,179,0.2);
}

.btn-soft-primary:hover {
    background-color: var(--minia-primary);
    color: white;
}

.table > :not(caption) > * > * {
    padding: 0.75rem 1rem;
    border-bottom-color: #e9ecef;
}

.timeline {
    position: relative;
}

.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 14px;
    top: 30px;
    width: 2px;
    height: calc(100% + 12px);
    background: linear-gradient(to bottom, var(--minia-primary), rgba(75,56,179,0.2));
}

.timeline-marker {
    font-size: 0.75rem;
    box-shadow: 0 2px 4px rgba(75,56,179,0.2);
}

.modal-lg {
    max-width: 800px;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(75,56,179,0.1);
    color: var(--minia-primary);
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(75,56,179,0.25);
}

@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 1rem;
    }
    
    .quick-action-card {
        margin-bottom: 0.75rem;
    }
    
    .chart-container {
        height: 220px;
    }
    
    .feature-locked .feature-btn::before {
        content: "🔒 Verify cPanel";
        top: -30px;
        font-size: 11px;
        padding: 3px 6px;
    }
    
    .database-section-locked::after, .statistics-section-locked::after, .account-section-locked::after, .ftp-section-locked::after {
        font-size: 0.875rem;
        padding: 0.75rem 1rem;
    }
}
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.Hosting') @endslot
        @slot('title') @lang('translation.Account_Details') @endslot
    @endcomponent

    {{-- 🔐 Verification Banner --}}
    @if($account->status === 'active' && !$account->cpanel_verified)
    <div id="verificationBanner" class="verification-banner">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="ri-shield-keyhole-line text-warning" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h5 class="mb-1 fw-semibold">@lang('translation.Verify_Your_cPanel_Access')</h5>
                    <p class="mb-0 text-muted">@lang('translation.cpanel_verify_messs')</p>
                </div>
            </div>
            <button type="button" 
                    onclick="handleCpanelLogin()" 
                    class="btn btn-warning fw-medium">
                <i class="ri-login-circle-line me-1"></i>
                Login Now
            </button>
        </div>
    </div>
    @endif

    {{-- Alert Messages --}}
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-circle-line me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-close-circle-line me-2"></i>
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Admin Deactivation Notice --}}
    @if($account->admin_deactivated)
    <div class="alert alert-danger">
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="ri-alert-octagon-line" style="font-size: 1.5rem;"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mt-0 mb-1">@lang('translation.admin_deactivated')</h5>
                <p class="mb-0">@lang('translation.can_reactivated')</p>
                <p class="mt-2 mb-0"><strong>@lang('translation.Reason'):</strong> {{ $account->admin_deactivation_reason }}</p>
                <p class="mt-2 mb-0">@lang('translation.If_Questions') <a href="{{ route('user.tickets.create') }}" class="text-decoration-underline">{{ __('Open') . ' ' . __('Tickets') }}</a>.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- 📊 Stats Grid --}}
    <div class="row mb-4">
        {{-- Account Status with Verification Status --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="stats-label mb-2">Account Status</p>
                        @if($account->status === 'active')
                            @if($account->cpanel_verified)
                                <span class="status-badge status-active">
                                    <i class="ri-check-circle-line me-1"></i>
                                    Active & Verified
                                </span>
                            @else
                                <span class="status-badge status-pending">
                                    <i class="ri-shield-keyhole-line me-1"></i>
                                    Active (Unverified)
                                </span>
                            @endif
                        @elseif($account->status === 'suspended')
                            <span class="status-badge status-suspended">
                                <i class="ri-close-circle-line me-1"></i>
                                Suspended
                            </span>
                        @else
                            <span class="status-badge status-pending">
                                <i class="ri-time-line me-1"></i>
                                {{ ucfirst($account->status) }}
                            </span>
                        @endif
                    </div>
                    <div class="icon-box">
                        <i class="ri-server-line"></i>
                    </div>
                </div>
                <div class="mt-auto">
                    <small class="text-muted d-block">Created: {{ $account->created_at->format('M j, Y') }}</small>
                    <small class="text-muted">Last updated: {{ $account->updated_at->format('M j, Y') }}</small>
                </div>
            </div>
        </div>

        {{-- Dynamic Stats Cards --}}
        @foreach(['disk', 'bandwidth', 'inodes'] as $index => $stat)
        <div class="col-xl-3 col-md-6">
            <div class="stats-card" data-stat="{{ $stat }}">
                @if($account->status === 'active' && !$account->cpanel_verified)
                    {{-- Locked State --}}
                    <div class="text-center py-3">
                        <div class="icon-box mb-3" style="background: #6c757d;">
                            <i class="ri-lock-line"></i>
                        </div>
                        <h6 class="text-muted mb-2">{{ ucfirst($stat) }}</h6>
                        <p class="text-muted small mb-2">🔒 Locked</p>
                        <small class="text-muted">Verify cPanel to view</small>
                    </div>
                @else
                    {{-- Loading State --}}
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0 text-muted">Loading...</p>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- 🚀 Quick Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-apps-2-line me-2 text-primary"></i>
                            Quick Actions
                        </h5>
                        @if(!$account->cpanel_verified && $account->status === 'active')
                            <small class="text-warning">
                                <i class="ri-lock-line me-1"></i>
                                Login to cPanel to unlock all features
                            </small>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Control Panel - Always available if account is active --}}
                        <div class="col-lg-3 col-md-6">
                            <button type="button" 
                                    onclick="handleCpanelLogin()" 
                                    class="quick-action-card w-100 border-0 bg-transparent {{ $account->status !== 'active' ? 'disabled' : '' }}">
                                <div class="icon-box">
                                    <i class="ri-computer-line"></i>
                                </div>
                                <h6 class="fw-medium mb-1">Control Panel</h6>
                                <p class="text-muted mb-0 small">
                                    @if($account->cpanel_verified)
                                        Access cPanel
                                    @else
                                        Login & Verify
                                    @endif
                                </p>
                            </button>
                        </div>

                        {{-- File Manager - Locked until verified --}}
                        <div class="col-lg-3 col-md-6 {{ (!$account->cpanel_verified && $account->status === 'active') ? 'feature-locked' : '' }}">
                            @if(App\Models\WebFtpSetting::isEnabled())
                                <a href="{{ route('webftp.index', $account->username) }}"
                                   target="_blank"
                                   class="quick-action-card feature-btn {{ (!$account->cpanel_verified || $account->status !== 'active') ? 'disabled' : '' }}"
                                   onclick="return checkVerification(event, 'File Manager')">
                                    <div class="icon-box">
                                        <i class="ri-folder-line"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">File Manager</h6>
                                    <p class="text-muted mb-0 small">Manage files</p>
                                </a>
                            @else
                                <a href="{{ route('hosting.filemanager', $account->username) }}"
                                   target="_blank"
                                   class="quick-action-card feature-btn {{ (!$account->cpanel_verified || $account->status !== 'active') ? 'disabled' : '' }}"
                                   onclick="return checkVerification(event, 'File Manager')">
                                    <div class="icon-box">
                                        <i class="ri-folder-line"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">File Manager</h6>
                                    <p class="text-muted mb-0 small">Manage files</p>
                                </a>
                            @endif
                        </div>

                        {{-- Softaculous - Locked until verified --}}
                        <div class="col-lg-3 col-md-6 {{ (!$account->cpanel_verified && $account->status === 'active') ? 'feature-locked' : '' }}">
                            <a href="{{ route('hosting.softaculous', $account->username) }}" 
                               target="_blank"
                               class="quick-action-card feature-btn {{ (!$account->cpanel_verified || $account->status !== 'active') ? 'disabled' : '' }}"
                               onclick="return checkVerification(event, 'Softaculous')">
                                <div class="icon-box">
                                    <i class="ri-archive-line"></i>
                                </div>
                                <h6 class="fw-medium mb-1">Softaculous</h6>
                                <p class="text-muted mb-0 small">Install apps</p>
                            </a>
                        </div>

                        {{-- Settings/Actions - Conditional based on account status --}}
                        <div class="col-lg-3 col-md-6 {{ (!$account->cpanel_verified && $account->status === 'active') ? 'feature-locked' : '' }}">
                            @if($account->status === 'deactivated')
                                <a href="{{ route('hosting.reactivate', $account->username) }}"
                                   class="quick-action-card">
                                    <div class="icon-box" style="background: linear-gradient(135deg, var(--minia-success), #45cb85);">
                                        <i class="ri-refresh-line"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">Reactivate</h6>
                                    <p class="text-muted mb-0 small">Restore account</p>
                                </a>
                            @elseif($account->status === 'suspended')
                                <a href="{{ route('user.tickets.create') }}"
                                   class="quick-action-card">
                                    <div class="icon-box" style="background: linear-gradient(135deg, var(--minia-warning), #ffb902);">
                                        <i class="ri-customer-service-line"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">Open Ticket</h6>
                                    <p class="text-muted mb-0 small">Get support</p>
                                </a>
                            @else
                                <a href="{{ route('hosting.settings', $account->username) }}"
                                   class="quick-action-card feature-btn {{ (!$account->cpanel_verified || $account->status !== 'active') ? 'disabled' : '' }}"
                                   onclick="return checkVerification(event, 'Account Settings')">
                                    <div class="icon-box">
                                        <i class="ri-settings-line"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">Settings</h6>
                                    <p class="text-muted mb-0 small">Account settings</p>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 📋 Account Details & FTP Info --}}
    <div class="row mb-4">
        {{-- Account Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-user-line me-2 text-primary"></i>
                            Account Details
                        </h5>
                        @if(!$account->cpanel_verified && $account->status === 'active')
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-lock-line me-1"></i>Locked
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body {{ (!$account->cpanel_verified && $account->status === 'active') ? 'account-section-locked' : '' }}">
                    @if(!$account->cpanel_verified && $account->status === 'active')
                        {{-- Verification Required Notice --}}
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="ri-user-settings-line text-warning" style="font-size: 4rem; opacity: 0.5;"></i>
                            </div>
                            <h5 class="text-warning mb-3">Account Details Locked</h5>
                            <p class="text-muted mb-4">
                                Your account credentials and connection details are protected.<br>
                                Verify your cPanel access to view sensitive information.
                            </p>
                            <button type="button" 
                                    onclick="handleCpanelLogin()" 
                                    class="btn btn-warning">
                                <i class="ri-login-circle-line me-1"></i>
                                Verify cPanel Access
                            </button>
                        </div>
                    @else
                        {{-- Account Details Content --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Username</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="{{ $account->status === 'active' ? $account->username : 'Loading...' }}" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('{{ $account->username }}', this)">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="position-relative">
                                    <input type="password" 
                                           value="{{ $account->status === 'active' ? $account->password : '••••••••••••••' }}" 
                                           id="password-field" 
                                           class="form-control pe-5" 
                                           readonly
                                           onclick="copyToClipboard('{{ $account->password }}', this)"
                                           style="cursor: pointer;">
                                    <button type="button" 
                                            class="btn btn-link position-absolute end-0 top-50 translate-middle-y border-0 text-muted"
                                            onclick="togglePassword('password-field', this)"
                                            style="z-index: 10; padding: 0.375rem 0.75rem;">
                                        <i class="ri-eye-off-line"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Domain</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="{{ $account->domain }}" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('{{ $account->domain }}', this)">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Server IP</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="{{ $serverIp ?? 'N/A' }}" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('{{ $serverIp ?? '' }}', this)">
                                </div>
                            </div>
                        </div>

                        {{-- Domain Actions --}}
                        @if($account->status === 'active' && $account->cpanel_verified)
                        <div class="mt-4">
                            <label class="form-label fw-semibold">Quick Actions</label>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('hosting.builder', ['username' => $account->username, 'domain' => $account->domain]) }}"
                                   target="_blank"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-layout-line me-1"></i> SitePro
                                </a>
                                
                                @if(App\Models\WebFtpSetting::isEnabled())
                                    <a href="{{ route('webftp.index', $account->username) }}"
                                       target="_blank"
                                       class="btn btn-info btn-sm">
                                        <i class="ri-folder-line me-1"></i> Files
                                    </a>
                                @else
                                    <a href="{{ route('hosting.filemanager', $account->username) }}"
                                       target="_blank"
                                       class="btn btn-info btn-sm">
                                        <i class="ri-folder-line me-1"></i> Files
                                    </a>
                                @endif
                                
                                <a href="https://{{ $account->domain }}" 
                                   target="_blank"
                                   class="btn btn-success btn-sm">
                                    <i class="ri-external-link-line me-1"></i> Visit Website
                                </a>
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- FTP Details --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-folder-transfer-line me-2 text-primary"></i>
                            FTP Details
                        </h5>
                        @if(!$account->cpanel_verified && $account->status === 'active')
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-lock-line me-1"></i>Locked
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body {{ (!$account->cpanel_verified && $account->status === 'active') ? 'ftp-section-locked' : '' }}">
                    @if(!$account->cpanel_verified && $account->status === 'active')
                        {{-- Verification Required Notice --}}
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="ri-folder-lock-line text-warning" style="font-size: 3rem; opacity: 0.5;"></i>
                            </div>
                            <h6 class="text-warning mb-3">FTP Access Locked</h6>
                            <p class="text-muted mb-4 small">
                                FTP credentials are protected until verification.
                            </p>
                            <button type="button" 
                                    onclick="handleCpanelLogin()" 
                                    class="btn btn-warning btn-sm">
                                <i class="ri-login-circle-line me-1"></i>
                                Verify Access
                            </button>
                        </div>
                    @else
                        {{-- FTP Details Content --}}
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Hostname</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="ftpupload.net" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('ftpupload.net', this)">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Username</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="{{ $account->username }}" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('{{ $account->username }}', this)">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="position-relative">
                                    <input type="password" 
                                           value="{{ $account->password }}" 
                                           id="ftp-password-field" 
                                           class="form-control pe-5" 
                                           readonly
                                           onclick="copyToClipboard('{{ $account->password }}', this)"
                                           style="cursor: pointer;">
                                    <button type="button" 
                                            class="btn btn-link position-absolute end-0 top-50 translate-middle-y border-0 text-muted"
                                            onclick="togglePassword('ftp-password-field', this)"
                                            style="z-index: 10; padding: 0.375rem 0.75rem;">
                                        <i class="ri-eye-off-line"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Port</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="21" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('21', this)">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="https://filezilla-project.org/download.php?type=client" 
                                   target="_blank"
                                   class="btn btn-primary flex-fill">
                                    <i class="ri-download-cloud-line me-1"></i> Download FileZilla
                                </a>
                                <button type="button"
                                        class="btn btn-outline-secondary flex-fill"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#ftpGuideModal">
                                    <i class="ri-question-line me-1"></i> Guide
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 🗄️ Database Management --}}
    @if($account->status === 'active')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-database-line me-2 text-primary"></i>
                            Database Management
                            @if(!$account->cpanel_verified)
                                <span class="badge bg-warning-subtle text-warning ms-2">
                                    <i class="ri-lock-line me-1"></i>Locked
                                </span>
                            @endif
                        </h5>
                        @if($account->cpanel_verified)
                            <button type="button" 
                                    onclick="syncDatabases()" 
                                    class="btn btn-soft-primary btn-sm"
                                    id="sync-databases-btn">
                                <i class="ri-refresh-line me-1"></i> Sync
                            </button>
                        @endif
                    </div>
                </div>
                
                <div class="card-body {{ !$account->cpanel_verified ? 'database-section-locked' : '' }}">
                    @if(!$account->cpanel_verified)
                        {{-- Verification Required Notice --}}
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="ri-lock-line text-warning" style="font-size: 4rem; opacity: 0.5;"></i>
                            </div>
                            <h5 class="text-warning mb-3">Database Management Locked</h5>
                            <p class="text-muted mb-4">
                                You need to verify your cPanel access before you can manage databases.<br>
                                Click the <strong>"Control Panel"</strong> button above to get started.
                            </p>
                            <button type="button" 
                                    onclick="handleCpanelLogin()" 
                                    class="btn btn-warning">
                                <i class="ri-login-circle-line me-1"></i>
                                Verify cPanel Access
                            </button>
                        </div>
                    @else
                        {{-- Database Stats --}}
                        <div id="databaseStatsContainer">
                            <div class="row g-3 mb-4">
                                <div class="col-lg-3 col-md-6">
                                    <div class="db-stats-card">
                                        <div class="db-stats-value" id="db-used-count">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <div class="db-stats-label">Used</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="db-stats-card">
                                        <div class="db-stats-value" id="db-max-count">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <div class="db-stats-label">Maximum</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="db-stats-card">
                                        <div class="db-stats-value" id="db-available-count">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <div class="db-stats-label">Available</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="db-stats-card">
                                        <div class="db-stats-value" id="db-usage-percent">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <div class="db-stats-label">Usage</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Create Database Form --}}
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">Create New Database</h6>
                            
                            {{-- Warning Alert for Limits --}}
                            <div id="database-limit-alert" class="alert alert-warning d-none">
                                <i class="ri-alert-triangle-line me-2"></i>
                                <span id="limit-message"></span>
                            </div>
                            
                            <form id="createDatabaseForm">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-10">
                                        <label class="form-label small">Database Name</label>
                                        <div class="input-group">
                                            <span class="form-prefix">{{ $account->username }}_</span>
                                            <input type="text" 
                                                   id="databaseNameInput"
                                                   name="database_name"
                                                   class="form-control" 
                                                   placeholder="Enter database name"
                                                   maxlength="54"
                                                   pattern="[a-zA-Z0-9_]+"
                                                   autocomplete="off">
                                        </div>
                                        <div class="form-text">Only letters, numbers, and underscores allowed</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-transparent">Action</label>
                                        <button type="submit" class="btn btn-primary w-100" id="create-db-btn">
                                            <span class="btn-text">
                                                <i class="ri-add-line me-1"></i> Create Database
                                            </span>
                                            <span class="btn-loading d-none">
                                                <div class="spinner-border spinner-border-sm me-1" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                Creating...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Database List --}}
                        <div id="databaseList">
                            <h6 class="fw-semibold mb-3">Current Databases</h6>
                            <div class="table-responsive">
                                <table class="table table-nowrap table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Database Name</th>
                                            <th>Created</th>
                                            <th>Size</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="databaseTableBody">
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 mb-0 text-muted">Loading databases...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 📊 Usage Statistics Charts --}}
    @if($account->status === 'active')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-line-chart-line me-2 text-primary"></i>
                            Usage Statistics (Last 30 days)
                            @if(!$account->cpanel_verified)
                                <span class="badge bg-warning-subtle text-warning ms-2">
                                    <i class="ri-lock-line me-1"></i>Locked
                                </span>
                            @endif
                        </h5>
                    </div>
                </div>
                <div class="card-body {{ !$account->cpanel_verified ? 'statistics-section-locked' : '' }}">
                    @if(!$account->cpanel_verified)
                        {{-- Verification Required Notice --}}
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="ri-bar-chart-line text-warning" style="font-size: 4rem; opacity: 0.5;"></i>
                            </div>
                            <h5 class="text-warning mb-3">Usage Statistics Locked</h5>
                            <p class="text-muted mb-4">
                                Detailed usage statistics and charts are available after cPanel verification.<br>
                                Click the <strong>"Control Panel"</strong> button above to verify your access.
                            </p>
                            <button type="button" 
                                    onclick="handleCpanelLogin()" 
                                    class="btn btn-warning">
                                <i class="ri-login-circle-line me-1"></i>
                                Verify cPanel Access
                            </button>
                        </div>
                    @else
                        {{-- Charts Content --}}
                        <div class="row g-4">
                            <div class="col-lg-4">
                                <div class="chart-container" id="diskChart"></div>
                            </div>
                            <div class="col-lg-4">
                                <div class="chart-container" id="bandwidthChart"></div>
                            </div>
                            <div class="col-lg-4">
                                <div class="chart-container" id="inodesChart"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Hidden Data --}}
    <input type="hidden" id="accountUsername" value="{{ $account->username }}">
    <input type="hidden" id="accountStatus" value="{{ $account->status }}">
    <input type="hidden" id="cpanelVerified" value="{{ $account->cpanel_verified ? '1' : '0' }}">

    {{-- Toast Notification --}}
    <div id="toast" class="toast-notification">
        <div class="d-flex align-items-center">
            <i class="ri-check-circle-line text-success me-2" id="toast-icon"></i>
            <span id="toast-message">Copied to clipboard!</span>
        </div>
    </div>

    {{-- FTP Connection Guide Modal --}}
    <div class="modal fade" id="ftpGuideModal" tabindex="-1" aria-labelledby="ftpGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ftpGuideModalLabel">
                        <i class="ri-folder-upload-line me-2 text-primary"></i>
                        FTP Connection Guide
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- FileZilla Setup --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="ri-download-2-line me-2 text-success"></i>
                            Step 1: Download & Install FileZilla
                        </h6>
                        <p class="text-muted mb-3">FileZilla is a free, reliable FTP client for managing your website files.</p>
                        <a href="https://filezilla-project.org/download.php?type=client" 
                           target="_blank" 
                           class="btn btn-success btn-sm">
                            <i class="ri-external-link-line me-1"></i>
                            Download FileZilla Client
                        </a>
                    </div>

                    {{-- Connection Settings --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="ri-settings-3-line me-2 text-primary"></i>
                            Step 2: Connection Settings
                        </h6>
                        <div class="bg-light rounded p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Host / Server</label>
                                    <input type="text" 
                                           class="copy-input form-control form-control-sm" 
                                           value="ftpupload.net" 
                                           readonly
                                           onclick="copyToClipboard('ftpupload.net', this)"
                                           style="cursor: pointer;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Port</label>
                                    <input type="text" 
                                           class="copy-input form-control form-control-sm" 
                                           value="21" 
                                           readonly
                                           onclick="copyToClipboard('21', this)"
                                           style="cursor: pointer;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Username</label>
                                    <input type="text" 
                                           class="copy-input form-control form-control-sm" 
                                           value="{{ $account->username }}" 
                                           readonly
                                           onclick="copyToClipboard('{{ $account->username }}', this)"
                                           style="cursor: pointer;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Password</label>
                                    <div class="position-relative">
                                        <input type="password" 
                                               class="copy-input form-control form-control-sm pe-5" 
                                               value="{{ $account->password }}" 
                                               readonly 
                                               id="modal-password"
                                               onclick="copyToClipboard('{{ $account->password }}', this)"
                                               style="cursor: pointer;">
                                        <button type="button" 
                                                class="btn btn-link position-absolute end-0 top-50 translate-middle-y border-0 text-muted"
                                                onclick="togglePassword('modal-password', this)"
                                                style="z-index: 10; padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <i class="ri-eye-off-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Step by Step Instructions --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="ri-list-ordered me-2 text-info"></i>
                            Step 3: Connect to Your Server
                        </h6>
                        <div class="timeline">
                            <div class="timeline-item d-flex mb-3">
                                <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; min-width: 30px;">
                                    <span class="fw-bold small">1</span>
                                </div>
                                <div>
                                    <p class="mb-1 fw-medium">Open FileZilla</p>
                                    <p class="text-muted small mb-0">Launch the FileZilla application on your computer.</p>
                                </div>
                            </div>
                            <div class="timeline-item d-flex mb-3">
                                <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; min-width: 30px;">
                                    <span class="fw-bold small">2</span>
                                </div>
                                <div>
                                    <p class="mb-1 fw-medium">Enter Connection Details</p>
                                    <p class="text-muted small mb-0">Fill in the Host, Username, Password, and Port (21) in the Quick Connect bar at the top.</p>
                                </div>
                            </div>
                            <div class="timeline-item d-flex mb-3">
                                <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; min-width: 30px;">
                                    <span class="fw-bold small">3</span>
                                </div>
                                <div>
                                    <p class="mb-1 fw-medium">Click "Quickconnect"</p>
                                    <p class="text-muted small mb-0">Press the Quickconnect button to establish the connection.</p>
                                </div>
                            </div>
                            <div class="timeline-item d-flex mb-3">
                                <div class="timeline-marker bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; min-width: 30px;">
                                    <i class="ri-check-line small"></i>
                                </div>
                                <div>
                                    <p class="mb-1 fw-medium">Start Managing Files</p>
                                    <p class="text-muted small mb-0">Once connected, you can upload, download, and manage your website files in the <code>/public_html</code> folder.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="https://wiki.filezilla-project.org/Using" target="_blank" class="btn btn-primary">
                        <i class="ri-external-link-line me-1"></i>
                        Official Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
// 🔧 Configuration and Global Variables
const CPANEL_VERIFY_KEY = 'cpanel_verify_{{ $account->username }}';
const VERIFICATION_CHECK_INTERVAL = 5000;
let verificationCheckTimer = null;
let isVerifying = false;
let currentDatabaseStats = null;
let charts = {};

// Get account information
const accountUsername = document.getElementById('accountUsername')?.value || '{{ $account->username }}';
const accountStatus = document.getElementById('accountStatus')?.value || '{{ $account->status }}';
const isCpanelVerified = document.getElementById('cpanelVerified')?.value === "1" || {{ $account->cpanel_verified ? 'true' : 'false' }};

// 🔑 Password Toggle Functions
function togglePassword(fieldId, button) {
    const field = document.getElementById(fieldId);
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'ri-eye-line';
    } else {
        field.type = 'password';
        icon.className = 'ri-eye-off-line';
    }
}

// 🖱️ Copy to Clipboard Functions
function copyToClipboard(text, element) {
    if (!text || text === '••••••••' || text === 'Loading...' || text === 'N/A') {
        showToast('Nothing to copy', 'warning');
        return;
    }
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text)
            .then(() => {
                showCopyAnimation(element);
                showToast('Copied to clipboard!', 'success');
            })
            .catch(() => fallbackCopy(text, element));
    } else {
        fallbackCopy(text, element);
    }
}

function fallbackCopy(text, element) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    textarea.style.left = '-9999px';
    
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    
    try {
        document.execCommand('copy');
        showCopyAnimation(element);
        showToast('Copied to clipboard!', 'success');
    } catch (err) {
        showToast('Copy failed, please try again', 'error');
    }
    
    document.body.removeChild(textarea);
}

function showCopyAnimation(element) {
    if (!element) return;
    
    element.classList.add('copied');
    setTimeout(() => {
        element.classList.remove('copied');
    }, 1000);
}

// 🎨 Toast Notification System
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');
    
    // Set icon and color based on type
    switch(type) {
        case 'success':
            toastIcon.className = 'ri-check-circle-line text-success me-2';
            break;
        case 'error':
            toastIcon.className = 'ri-close-circle-line text-danger me-2';
            break;
        case 'warning':
            toastIcon.className = 'ri-alert-circle-line text-warning me-2';
            break;
        case 'info':
            toastIcon.className = 'ri-information-circle-line text-info me-2';
            break;
        default:
            toastIcon.className = 'ri-check-circle-line text-success me-2';
    }
    
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// 🔒 Feature Verification Check Functions
function checkVerification(event, featureName = 'this feature') {
    if (!isCpanelVerified && accountStatus === 'active') {
        event.preventDefault();
        event.stopPropagation();
        
        Swal.fire({
            title: 'Verification Required',
            html: `
                <div class="text-center">
                    <i class="ri-lock-line text-warning" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>You need to verify your cPanel access before using <strong>${featureName}</strong>.</p>
                    <p class="text-muted small">Click "Verify Now" to login to cPanel and unlock all features.</p>
                    ${showFeaturePreview(featureName)}
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="ri-login-circle-line me-1"></i> Verify Now',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            customClass: {
                popup: 'swal-wide'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                handleCpanelLogin();
            }
        });
        
        return false;
    }
    
    return true;
}

function showFeaturePreview(featureName) {
    let previewContent = '';
    
    switch(featureName) {
        case 'File Manager':
            previewContent = `
                <div class="text-start mt-3">
                    <h6>📁 File Manager Features:</h6>
                    <ul class="list-unstyled ms-3">
                        <li>✅ Upload and download files</li>
                        <li>✅ Create and edit folders</li>
                        <li>✅ Edit code files online</li>
                        <li>✅ Set file permissions</li>
                        <li>✅ Extract ZIP archives</li>
                    </ul>
                </div>
            `;
            break;
        case 'Softaculous':
            previewContent = `
                <div class="text-start mt-3">
                    <h6>🚀 Softaculous Apps:</h6>
                    <ul class="list-unstyled ms-3">
                        <li>✅ WordPress - Blog & CMS</li>
                        <li>✅ Joomla - Content Management</li>
                        <li>✅ phpMyAdmin - Database Manager</li>
                        <li>✅ 400+ other applications</li>
                        <li>✅ One-click installation</li>
                    </ul>
                </div>
            `;
            break;
        case 'Database Management':
            previewContent = `
                <div class="text-start mt-3">
                    <h6>🗄️ Database Features:</h6>
                    <ul class="list-unstyled ms-3">
                        <li>✅ Create MySQL databases</li>
                        <li>✅ Manage database users</li>
                        <li>✅ phpMyAdmin access</li>
                        <li>✅ Import/Export data</li>
                        <li>✅ Real-time usage stats</li>
                    </ul>
                </div>
            `;
            break;
        case 'Usage Statistics':
            previewContent = `
                <div class="text-start mt-3">
                    <h6>📊 Statistics Features:</h6>
                    <ul class="list-unstyled ms-3">
                        <li>✅ Real-time disk usage charts</li>
                        <li>✅ Bandwidth usage tracking</li>
                        <li>✅ Inodes usage monitoring</li>
                        <li>✅ 30-day historical data</li>
                        <li>✅ Interactive data visualization</li>
                    </ul>
                </div>
            `;
            break;
        case 'Account Details':
            previewContent = `
                <div class="text-start mt-3">
                    <h6>👤 Account Details Features:</h6>
                    <ul class="list-unstyled ms-3">
                        <li>✅ Username and password access</li>
                        <li>✅ Domain information</li>
                        <li>✅ Server IP details</li>
                        <li>✅ One-click copy credentials</li>
                        <li>✅ Quick action buttons</li>
                    </ul>
                </div>
            `;
            break;
        case 'FTP Details':
            previewContent = `
                <div class="text-start mt-3">
                    <h6>📁 FTP Access Features:</h6>
                    <ul class="list-unstyled ms-3">
                        <li>✅ FTP hostname and credentials</li>
                        <li>✅ FileZilla download links</li>
                        <li>✅ Connection setup guide</li>
                        <li>✅ Copy credentials to clipboard</li>
                        <li>✅ Port and security settings</li>
                    </ul>
                </div>
            `;
            break;
        case 'Disk Statistics':
        case 'Bandwidth Statistics':
        case 'Inodes Statistics':
            previewContent = `
                <div class="text-start mt-3">
                    <h6>📊 ${featureName} Features:</h6>
                    <ul class="list-unstyled ms-3">
                        <li>✅ Real-time usage monitoring</li>
                        <li>✅ Historical usage trends</li>
                        <li>✅ Usage percentage calculations</li>
                        <li>✅ Limit and quota tracking</li>
                        <li>✅ Visual progress indicators</li>
                    </ul>
                </div>
            `;
            break;
        default:
            previewContent = `<p class="mt-3">This feature will be available after cPanel verification.</p>`;
    }
    
    return previewContent;
}

function preventLockedAction(event, featureName = 'this feature') {
    if (!isCpanelVerified && accountStatus === 'active') {
        event.preventDefault();
        event.stopPropagation();
        
        showToast(`Verify cPanel access to use ${featureName}`, 'warning');
        
        // Show verification modal after a short delay
        setTimeout(() => {
            checkVerification({ preventDefault: () => {}, stopPropagation: () => {} }, featureName);
        }, 500);
        
        return false;
    }
    return true;
}

function updateFeatureLockStates() {
    console.log('🔓 Updating feature lock states...');
    
    // Remove disabled classes
    document.querySelectorAll('.disabled').forEach(el => {
        if (!el.classList.contains('permanent-disabled')) {
            el.classList.remove('disabled');
        }
    });
    
    // Remove feature-locked classes
    document.querySelectorAll('.feature-locked').forEach(el => {
        el.classList.remove('feature-locked');
    });
    
    // Remove locked classes from stats cards
    document.querySelectorAll('[data-stat]').forEach(card => {
        card.classList.remove('locked');
    });
    
    // Remove all section locks
    const lockedSections = [
        '.database-section-locked',
        '.statistics-section-locked', 
        '.account-section-locked',
        '.ftp-section-locked'
    ];
    
    lockedSections.forEach(selector => {
        const section = document.querySelector(selector);
        if (section) {
            section.classList.remove(selector.substring(1)); // Remove the '.' from selector
        }
    });
    
    // Update quick action text
    const controlPanelCard = document.querySelector('.quick-action-card p');
    if (controlPanelCard && controlPanelCard.textContent.includes('Login & Verify')) {
        controlPanelCard.textContent = 'Access cPanel';
    }
    
    // Show success message
    showToast('All features unlocked! 🎉', 'success');
}

// 🔐 cPanel Login & Verification System
function handleCpanelLogin() {
    console.log('🔐 Handling cPanel login...');
    
    if (isCpanelVerified) {
        console.log('✅ Already verified, opening cPanel');
        window.open("{{ route('hosting.cpanel', $account->username) }}", '_blank');
        return;
    }
    
    if (isVerifying) {
        console.log('⏳ Verification already in progress');
        return;
    }

    isVerifying = true;
    localStorage.setItem(CPANEL_VERIFY_KEY, '1');
    
    const cpanelWindow = window.open("{{ route('hosting.cpanel', $account->username) }}", '_blank');
    
    if (cpanelWindow) {
        console.log('🚀 cPanel window opened, starting verification check');
        
        Swal.fire({
            title: 'cPanel Login',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Please wait while we verify your access...</p>
                    <small class="text-muted">This window will automatically close once verification is complete.</small>
                </div>
            `,
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-wide'
            }
        });

        startVerificationCheck();
    } else {
        console.log('❌ Failed to open cPanel window');
        isVerifying = false;
        Swal.fire({
            title: 'Popup Blocked',
            text: 'Please allow popups and try again.',
            icon: 'warning'
        });
    }
}

function startVerificationCheck() {
    if (!isVerifying) return;
    
    console.log('🔄 Starting verification check timer');
    checkVerificationStatus();
    verificationCheckTimer = setInterval(checkVerificationStatus, VERIFICATION_CHECK_INTERVAL);
}

function checkVerificationStatus() {
    if (!isVerifying) {
        clearInterval(verificationCheckTimer);
        return;
    }

    console.log('🔍 Checking verification status...');
    
    // Use proper route helper instead of hard-coded URL
    const verifyUrl = "{{ route('hosting.verify-cpanel', ':username') }}".replace(':username', accountUsername);
    
    fetch(verifyUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('📡 Verification response:', data);
        if (data.success) {
            handleVerificationSuccess();
        }
    })
    .catch(error => {
        console.error('❌ Verification failed:', error);
        // Don't stop verification on network errors, just log them
    });
}

function handleVerificationSuccess() {
    console.log('🎉 Verification successful!');
    
    clearInterval(verificationCheckTimer);
    localStorage.removeItem(CPANEL_VERIFY_KEY);
    isVerifying = false;

    Swal.fire({
        title: 'Verification Successful!',
        html: `
            <div class="text-center">
                <i class="ri-check-circle-line text-success" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                <p><strong>cPanel verified successfully!</strong></p>
                <p class="text-muted">All features are now unlocked and ready to use.</p>
            </div>
        `,
        icon: 'success',
        confirmButtonText: 'Continue',
        confirmButtonColor: '#28a745',
        timer: 3000,
        timerProgressBar: true
    }).then((result) => {
        // Update global verification state
        window.isCpanelVerified = true;
        
        // Update feature lock states
        updateFeatureLockStates();
        
        // Load charts now that verification is complete
        loadAccountStats();
        
        // Reload page after a short delay to ensure all states are updated
        setTimeout(() => {
            location.reload();
        }, 1000);
    });
}

// 📊 Account Statistics Management
function loadAccountStats() {
    // Only load stats if cPanel is verified
    if (!isCpanelVerified && accountStatus === 'active') {
        console.log('⚠️ Stats not loaded - cPanel not verified');
        return;
    }
    
    const statsUrl = "{{ route('hosting.all-stats', ':username') }}".replace(':username', accountUsername);
    
    fetch(statsUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsCards(data.stats);
                
                // Only initialize charts if cPanel is verified
                if (isCpanelVerified) {
                    initializeCharts(data.chart_data || {});
                } else {
                    console.log('⚠️ Charts not loaded - cPanel not verified');
                }
            } else {
                showStatsError(data.message);
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
            showStatsError('Failed to fetch statistics');
        });
}

function updateStatsCards(stats) {
    const statConfigs = {
        'disk': {
            title: 'Disk Space',
            icon: 'ri-hard-drive-2-line',
            color: 'var(--minia-info)'
        },
        'bandwidth': {
            title: 'Bandwidth',
            icon: 'ri-wifi-line',
            color: 'var(--minia-success)'
        },
        'inodes': {
            title: 'Inodes',
            icon: 'ri-file-line',
            color: 'var(--minia-warning)'
        }
    };

    Object.entries(statConfigs).forEach(([type, config]) => {
        const card = document.querySelector(`[data-stat="${type}"]`);
        if (!card || !stats[type]) return;

        const data = stats[type];
        const used = data.used || 0;
        const total = data.total || 0;
        const unit = data.unit || '';
        const percent = data.percent || 0;

        // Remove locked class if it exists
        card.classList.remove('locked');
        
        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stats-label mb-1">${config.title}</p>
                    <div class="stats-value">${formatNumber(used)} ${unit}</div>
                    <small class="text-muted">/ ${total === 'Unlimited' ? 'Unlimited' : formatNumber(total) + ' ' + unit}</small>
                </div>
                <div class="icon-box">
                    <i class="${config.icon}"></i>
                </div>
            </div>
            <div class="progress-custom mt-2">
                <div class="progress-bar-custom" style="width: ${Math.min(percent, 100)}%"></div>
            </div>
            <small class="text-muted">${percent}% used</small>
        `;
    });
}

function showStatsError(message) {
    document.querySelectorAll('[data-stat]').forEach(card => {
        card.style.display = 'block';
        card.innerHTML = `
            <div class="text-center py-3">
                <i class="ri-alert-circle-line text-danger" style="font-size: 2rem;"></i>
                <p class="mt-2 mb-2 text-muted">${message}</p>
                <button onclick="loadAccountStats()" class="btn btn-primary btn-sm">
                    <i class="ri-refresh-line me-1"></i> Retry
                </button>
            </div>
        `;
    });
}

// 📊 Chart Management System
function initializeCharts(chartData) {
    const chartTypes = [
        { key: 'diskspace', id: 'diskChart', title: 'Disk Usage', unit: 'MB', color: '#4B38B3' },
        { key: 'bandwidth', id: 'bandwidthChart', title: 'Bandwidth Usage', unit: 'MB', color: '#45CB85' },
        { key: 'inodes', id: 'inodesChart', title: 'Inodes Usage', unit: 'Files', color: '#FFB902' }
    ];

    chartTypes.forEach(chart => {
        const container = document.getElementById(chart.id);
        if (!container) return;

        const myChart = echarts.init(container);
        const data = chartData[chart.key] || { history: [], limit: 0 };
        
        const option = {
            title: {
                text: chart.title,
                left: 'center',
                textStyle: {
                    fontSize: 14,
                    fontWeight: 'normal'
                }
            },
            tooltip: {
                trigger: 'axis'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                top: '15%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: data.history.map(item => item.date) || []
            },
            yAxis: {
                type: 'value',
                name: chart.unit
            },
            series: [{
                type: 'line',
                smooth: true,
                data: data.history.map(item => item.value) || [],
                itemStyle: {
                    color: chart.color
                },
                areaStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        {
                            offset: 0,
                            color: chart.color + '20'
                        },
                        {
                            offset: 1,
                            color: chart.color + '05'
                        }
                    ])
                }
            }]
        };

        myChart.setOption(option);
        charts[chart.key] = myChart;
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        Object.values(charts).forEach(chart => chart.resize());
    });
}

// 🗄️ Database Management Functions
function loadDatabases() {
    const databasesUrl = "{{ route('hosting.databases', ':username') }}".replace(':username', accountUsername);
    
    fetch(databasesUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateDatabasesList(data.databases);
            updateDatabaseStats(data.stats);
            updateLimitAlert(data.stats);
        } else {
            throw new Error(data.message || 'Failed to load databases');
        }
    })
    .catch(error => {
        console.error('❌ Error loading databases:', error);
        showDatabaseError('Failed to load databases: ' + error.message);
    });
}

function updateDatabaseStats(stats) {
    if (!stats) return;
    
    currentDatabaseStats = stats;
    
    // Update stats display
    document.getElementById('db-used-count').innerHTML = stats.current_usage;
    document.getElementById('db-max-count').innerHTML = stats.max_databases;
    document.getElementById('db-available-count').innerHTML = stats.available;
    document.getElementById('db-usage-percent').innerHTML = Math.round(stats.usage_percent || 0) + '%';
}

function updateLimitAlert(stats) {
    const alertElement = document.getElementById('database-limit-alert');
    const messageElement = document.getElementById('limit-message');
    const createBtn = document.getElementById('create-db-btn');
    const dbInput = document.getElementById('databaseNameInput');
    
    if (!stats) return;
    
    // Show warning if approaching limit or at limit
    if (stats.usage_percent >= 80) {
        let message = '';
        
        if (stats.current_usage >= stats.max_databases) {
            message = `Database limit reached! You are using ${stats.current_usage} of ${stats.max_databases} available databases.`;
            alertElement.className = 'alert alert-danger';
            createBtn.disabled = true;
            dbInput.disabled = true;
        } else {
            message = `Currently using <strong>${stats.current_usage}</strong> of <strong>${stats.max_databases}</strong> available databases.`;
            alertElement.className = 'alert alert-warning';
            createBtn.disabled = false;
            dbInput.disabled = false;
        }
        
        messageElement.innerHTML = message;
        alertElement.classList.remove('d-none');
    } else {
        alertElement.classList.add('d-none');
        createBtn.disabled = false;
        dbInput.disabled = false;
    }
}

function updateDatabasesList(databases) {
    const tbody = document.getElementById('databaseTableBody');
    
    if (databases.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-4">
                    <i class="ri-database-line text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0 text-muted">No databases found</p>
                    <small class="text-muted">Create your first database using the form above.</small>
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = databases.map(db => `
            <tr id="db-row-${db.id}">
                <td>
                    <div class="fw-medium">${db.name}</div>
                    <small class="text-muted">${db.short_name}</small>
                </td>
                <td>
                    <small class="text-muted">${db.created_ago || 'N/A'}</small>
                </td>
                <td>
                    <small class="text-muted">${db.size || 'N/A'}</small>
                </td>
                <td class="text-end">
                    <button onclick="openPhpMyAdmin('${db.short_name}')" 
                            class="btn btn-soft-primary btn-sm me-2"
                            id="phpmyadmin-btn-${db.id}">
                        <i class="ri-database-line me-1"></i> phpMyAdmin
                    </button>
                    <button onclick="deleteDatabase('${db.short_name}')" 
                            class="btn btn-soft-danger btn-sm"
                            id="delete-btn-${db.id}">
                        <i class="ri-delete-bin-line me-1"></i> Delete
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

async function createDatabase() {
    const form = document.getElementById('createDatabaseForm');
    const formData = new FormData(form);
    const databaseName = formData.get('database_name');
    const createBtn = document.getElementById('create-db-btn');
    const btnText = createBtn.querySelector('.btn-text');
    const btnLoading = createBtn.querySelector('.btn-loading');
    
    if (!databaseName) {
        showToast('Please enter a database name', 'error');
        return;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(databaseName)) {
        showToast('Database name can only contain letters, numbers, and underscores', 'error');
        return;
    }

    // Check limits before creating
    if (currentDatabaseStats && currentDatabaseStats.current_usage >= currentDatabaseStats.max_databases) {
        showToast(`Database limit reached! You are using ${currentDatabaseStats.current_usage} of ${currentDatabaseStats.max_databases} available databases.`, 'error');
        return;
    }
    
    // Show loading state
    createBtn.disabled = true;
    btnText.classList.add('d-none');
    btnLoading.classList.remove('d-none');
    
    try {
        const response = await fetch(`/hosting/${accountUsername}/databases`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`Database '${databaseName}' created successfully!`, 'success');
            form.reset();
            loadDatabases(); // Reload to update stats and list
        } else {
            throw new Error(data.message || 'Failed to create database');
        }
        
    } catch (error) {
        console.error('❌ Create database error:', error);
        showToast('Failed to create database: ' + error.message, 'error');
    } finally {
        // Reset button state
        createBtn.disabled = false;
        btnText.classList.remove('d-none');
        btnLoading.classList.add('d-none');
    }
}

async function deleteDatabase(dbName) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: `Delete database '${dbName}'? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F06548',
        cancelButtonColor: '#74788D',
        confirmButtonText: 'Yes, delete it!',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            try {
                const response = await fetch(`/hosting/${accountUsername}/databases`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ database_name: dbName })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to delete database');
                }
                
                return data;
            } catch (error) {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result.isConfirmed && result.value) {
        showToast(`Database '${dbName}' deleted successfully!`, 'success');
        loadDatabases(); // Reload to update stats and list
    }
}

function openPhpMyAdmin(dbName) {
    const btn = document.querySelector(`[onclick="openPhpMyAdmin('${dbName}')"]`);
    
    if (btn) {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<div class="spinner-border spinner-border-sm me-1" role="status"></div> Opening...';
        btn.disabled = true;
    }
    
    fetch(`/hosting/${accountUsername}/phpmyadmin-link`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ database: dbName })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.open(data.url, '_blank');
            showToast('phpMyAdmin opened successfully', 'success');
        } else {
            throw new Error(data.message || 'Failed to get phpMyAdmin link');
        }
    })
    .catch(error => {
        console.error('❌ Error opening phpMyAdmin:', error);
        showToast('Failed to open phpMyAdmin: ' + error.message, 'error');
    })
    .finally(() => {
        if (btn) {
            btn.innerHTML = '<i class="ri-database-line me-1"></i> phpMyAdmin';
            btn.disabled = false;
        }
    });
}

async function syncDatabases() {
    const syncBtn = document.getElementById('sync-databases-btn');
    const originalHTML = syncBtn.innerHTML;
    
    syncBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1" role="status"></div> Syncing...';
    syncBtn.disabled = true;
    
    try {
        const response = await fetch(`/hosting/${accountUsername}/databases-sync`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Databases synced successfully!', 'success');
            loadDatabases();
        } else {
            throw new Error(data.message || 'Sync failed');
        }
        
    } catch (error) {
        console.error('❌ Sync error:', error);
        showToast('Sync failed: ' + error.message, 'error');
    } finally {
        syncBtn.innerHTML = originalHTML;
        syncBtn.disabled = false;
    }
}

// 🔒 Feature Lock Initialization
function initializeFeatureLocks() {
    console.log('🔒 Initializing feature locks...');
    console.log('Account status:', accountStatus);
    console.log('cPanel verified:', isCpanelVerified);
    
    if (accountStatus === 'active' && !isCpanelVerified) {
        console.log('⚠️ Account active but cPanel not verified - applying locks');
        
        // Add locked class to stats cards and click listeners
        document.querySelectorAll('[data-stat]').forEach(card => {
            card.classList.add('locked');
            card.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const statType = this.getAttribute('data-stat');
                const featureName = `${statType.charAt(0).toUpperCase() + statType.slice(1)} Statistics`;
                checkVerification(e, featureName);
            });
        });
        
        // Add click event listeners to locked features
        document.querySelectorAll('.feature-locked .feature-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const featureName = this.querySelector('h6')?.textContent || 'this feature';
                return checkVerification(e, featureName);
            });
        });
        
        // Add form submission prevention for database forms
        const dbForm = document.getElementById('createDatabaseForm');
        if (dbForm) {
            dbForm.addEventListener('submit', function(e) {
                return preventLockedAction(e, 'Database Management');
            });
        }
        
        // Add click listeners for locked sections
        const lockedSections = [
            { selector: '.statistics-section-locked', name: 'Usage Statistics' },
            { selector: '.account-section-locked', name: 'Account Details' },
            { selector: '.ftp-section-locked', name: 'FTP Details' }
        ];
        
        lockedSections.forEach(section => {
            const element = document.querySelector(section.selector);
            if (element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    checkVerification(e, section.name);
                });
            }
        });
        
        // Show warning in verification banner if exists
        const banner = document.getElementById('verificationBanner');
        if (banner) {
            banner.style.display = 'block';
        }
    } else {
        console.log('✅ Account verified or not active - no locks needed');
    }
}

// 🛠️ Utility Functions
function formatNumber(num) {
    if (typeof num !== 'number') return num || '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function isMobileDevice() {
    return window.innerWidth <= 768;
}

// 🚀 Main Initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing hosting management...');
    console.log('Account status:', accountStatus);
    console.log('cPanel verified:', isCpanelVerified);
    
    // Initialize feature locks first
    initializeFeatureLocks();
    
    // Database form handling - with verification check
    const dbForm = document.getElementById('createDatabaseForm');
    if (dbForm) {
        dbForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Check verification before allowing database creation
            if (!isCpanelVerified && accountStatus === 'active') {
                return preventLockedAction(e, 'Database Management');
            }
            
            await createDatabase();
        });
    }

    // Load data based on account status and verification
    if (accountStatus === 'active') {
        if (isCpanelVerified) {
            console.log('✅ Account verified - loading all features');
            // Load account stats (includes disk, bandwidth, inodes)
            loadAccountStats();
            // Load database list and charts
            loadDatabases();
        } else {
            console.log('⚠️ Account not verified - stats and features locked');
            
            // Check for pending verification
            const hasVerifyFlag = localStorage.getItem(CPANEL_VERIFY_KEY);
            if (hasVerifyFlag) {
                console.log('🔄 Found pending verification, resuming...');
                isVerifying = true;
                startVerificationCheck();
            }
            
            // Show verification reminder after a delay
            setTimeout(() => {
                const banner = document.getElementById('verificationBanner');
                if (banner) {
                    banner.classList.add('show');
                }
                
                // Show a gentle reminder toast
                showToast('💡 Tip: Login to cPanel to unlock all features', 'info');
            }, 2000);
        }
    } else {
        console.log('❌ Account not active - no data loading');
    }
    
    // Add global click handler for disabled elements
    document.addEventListener('click', function(e) {
        const disabledElement = e.target.closest('.disabled');
        if (disabledElement) {
            e.preventDefault();
            e.stopPropagation();
            
            if (accountStatus !== 'active') {
                showToast('Account must be active to use this feature', 'warning');
            } else if (!isCpanelVerified) {
                showToast('Please verify cPanel access first', 'warning');
            }
            
            return false;
        }
    });
    
    // Add hover effect for locked features on desktop
    if (!isMobileDevice()) {
        document.querySelectorAll('.feature-locked').forEach(container => {
            let hoverTimeout;
            container.addEventListener('mouseenter', function() {
                if (!isCpanelVerified && accountStatus === 'active') {
                    hoverTimeout = setTimeout(() => {
                        showToast('🔒 Verify cPanel to unlock this feature', 'info');
                    }, 1000);
                }
            });
            
            container.addEventListener('mouseleave', function() {
                if (hoverTimeout) {
                    clearTimeout(hoverTimeout);
                }
            });
        });
    }
});

// 🔄 Cleanup
window.addEventListener('beforeunload', () => {
    if (verificationCheckTimer) {
        clearInterval(verificationCheckTimer);
    }
    
    // Clean up any verification flags
    if (isVerifying) {
        localStorage.removeItem(CPANEL_VERIFY_KEY);
    }
});
</script>
@endsection