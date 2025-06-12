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

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(75,56,179,0.1);
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
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.Hosting') @endslot
        @slot('title') @lang('translation.Account_Details') @endslot
    @endcomponent

    {{-- 🔐 Verification Banner --}}
    @if($account->status === 'active' && !$account->cpanel_verified)
    <div class="verification-banner">
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

    {{-- 📊 Stats Grid --}}
    <div class="row mb-4">
        {{-- Account Status --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="stats-label mb-2">Account Status</p>
                        @if($account->status === 'active')
                            <span class="status-badge status-active">
                                <i class="ri-check-circle-line me-1"></i>
                                Active
                            </span>
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
            <div class="stats-card" data-stat="{{ $stat }}" style="display: none;">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0 text-muted">Loading...</p>
                </div>
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
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <button type="button" 
                                    onclick="handleCpanelLogin()" 
                                    class="quick-action-card w-100 border-0 bg-transparent {{ $account->status !== 'active' ? 'disabled' : '' }}">
                                <div class="icon-box">
                                    <i class="ri-computer-line"></i>
                                </div>
                                <h6 class="fw-medium mb-1">Control Panel</h6>
                                <p class="text-muted mb-0 small">Manage cPanel</p>
                            </button>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            @if(App\Models\WebFtpSetting::isEnabled())
                                <a href="{{ route('webftp.index', $account->username) }}"
                                   target="_blank"
                                   class="quick-action-card {{ (!$account->cpanel_verified || $account->status !== 'active') ? 'disabled' : '' }}">
                                    <div class="icon-box">
                                        <i class="ri-folder-line"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">File Manager</h6>
                                    <p class="text-muted mb-0 small">Manage files</p>
                                </a>
                            @else
                                <a href="{{ route('hosting.filemanager', $account->username) }}"
                                   target="_blank"
                                   class="quick-action-card {{ (!$account->cpanel_verified || $account->status !== 'active') ? 'disabled' : '' }}">
                                    <div class="icon-box">
                                        <i class="ri-folder-line"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">File Manager</h6>
                                    <p class="text-muted mb-0 small">Manage files</p>
                                </a>
                            @endif
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="{{ route('hosting.softaculous', $account->username) }}" 
                               target="_blank"
                               class="quick-action-card {{ (!$account->cpanel_verified || $account->status !== 'active') ? 'disabled' : '' }}">
                                <div class="icon-box">
                                    <i class="ri-archive-line"></i>
                                </div>
                                <h6 class="fw-medium mb-1">Softaculous</h6>
                                <p class="text-muted mb-0 small">Install apps</p>
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
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
                                   class="quick-action-card {{ (!$account->cpanel_verified || $account->status !== 'active') ? 'disabled' : '' }}">
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
                    <h5 class="card-title mb-0">Account Details</h5>
                </div>
                <div class="card-body">
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
                </div>
            </div>
        </div>

        {{-- FTP Details --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">FTP Details</h5>
                </div>
                <div class="card-body">
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
                                <i class="ri-question-line me-1"></i> Connection Guide
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🗄️ Database Management --}}
@if($account->status === 'active' && $account->cpanel_verified)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Database Management</h5>
                    <button type="button" 
                            onclick="syncDatabases()" 
                            class="btn btn-soft-primary btn-sm"
                            id="sync-databases-btn">
                        <i class="ri-refresh-line me-1"></i> Sync
                    </button>
                </div>
            </div>
            
            <div class="card-body">
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
                        <span class="glyphicon glyphicon-exclamation-sign"></span>
                        <div class="alert-message">
                            <span id="limit-message"></span>
                        </div>
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
                    <h5 class="card-title mb-0">Usage Statistics (Last 30 days)</h5>
                </div>
                <div class="card-body">
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

                    {{-- Common Issues --}}
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-3">
                            <i class="ri-error-warning-line me-2 text-warning"></i>
                            Common Issues & Solutions
                        </h6>
                        <div class="accordion" id="ftpTroubleshooting">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="issue1">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                        Connection Timeout / Can't Connect
                                    </button>
                                </h2>
                                <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#ftpTroubleshooting">
                                    <div class="accordion-body">
                                        <ul class="mb-0">
                                            <li>Check your internet connection</li>
                                            <li>Verify firewall settings (allow FTP on port 21)</li>
                                            <li>Try switching to Passive Mode in FileZilla settings</li>
                                            <li>Contact your ISP if they block FTP connections</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="issue2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                        Login Failed / Authentication Error
                                    </button>
                                </h2>
                                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#ftpTroubleshooting">
                                    <div class="accordion-body">
                                        <ul class="mb-0">
                                            <li>Double-check your username and password (copy from above)</li>
                                            <li>Ensure your hosting account is active</li>
                                            <li>Wait a few minutes if you just created the account</li>
                                            <li>Contact support if credentials are correct but still failing</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="issue3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                        Can't Upload Files
                                    </button>
                                </h2>
                                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#ftpTroubleshooting">
                                    <div class="accordion-body">
                                        <ul class="mb-0">
                                            <li>Check if you have sufficient disk space</li>
                                            <li>Ensure you're uploading to the correct directory (<code>/public_html</code>)</li>
                                            <li>Verify file permissions and ownership</li>
                                            <li>Try uploading smaller files first</li>
                                        </ul>
                                    </div>
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
// Configuration
const HOSTING_CONFIG = {
    CPANEL_VERIFY_KEY: 'cpanel_verify_{{ $account->username }}',
    VERIFICATION_CHECK_INTERVAL: 5000,
    accountUsername: '{{ $account->username }}',
    accountStatus: '{{ $account->status }}',
    isCpanelVerified: {{ $account->cpanel_verified ? 'true' : 'false' }},
    csrfToken: '{{ csrf_token() }}'
};
let verificationCheckTimer = null;
let isVerifying = false;
let currentDatabaseStats = null;
let charts = {};

// Get account information
const accountUsername = document.getElementById('accountUsername')?.value;
const accountStatus = document.getElementById('accountStatus')?.value;
const isCpanelVerified = document.getElementById('cpanelVerified')?.value === "1";

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

// 🖱️ Copy to Clipboard
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

// 🔐 cPanel Login & Verification
function handleCpanelLogin() {
    if (isCpanelVerified) {
        window.open("{{ route('hosting.cpanel', $account->username) }}", '_blank');
        return;
    }
    
    if (isVerifying) return;

    isVerifying = true;
    localStorage.setItem(CPANEL_VERIFY_KEY, '1');
    
    const cpanelWindow = window.open("{{ route('hosting.cpanel', $account->username) }}", '_blank');
    
    if (cpanelWindow) {
        Swal.fire({
            title: 'cPanel Login',
            text: 'Please wait while we verify your access...',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        startVerificationCheck();
    } else {
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
    
    checkVerificationStatus();
    verificationCheckTimer = setInterval(checkVerificationStatus, VERIFICATION_CHECK_INTERVAL);
}

function checkVerificationStatus() {
    if (!isVerifying) {
        clearInterval(verificationCheckTimer);
        return;
    }

    fetch(`/hosting/${accountUsername}/verify-cpanel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            handleVerificationSuccess();
        }
    })
    .catch(error => {
        console.error('Verification failed:', error);
    });
}

function handleVerificationSuccess() {
    clearInterval(verificationCheckTimer);
    localStorage.removeItem(CPANEL_VERIFY_KEY);
    isVerifying = false;

    Swal.fire({
        title: 'Verification Successful!',
        text: 'cPanel verified! All features are now unlocked.',
        icon: 'success',
        confirmButtonText: 'Continue'
    }).then((result) => {
        if (result.isConfirmed) {
            location.reload();
        }
    });
}

// 📊 Load Account Statistics
function loadAccountStats() {
    fetch(`/hosting/${accountUsername}/all-stats`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsCards(data.stats);
                initializeCharts(data.chart_data || {});
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

        card.style.display = 'block';
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

// 📊 Chart Functions
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

// 🗄️ Enhanced Database Management Functions
function loadDatabases() {
    fetch(`/hosting/${HOSTING_CONFIG.accountUsername}/databases`, {
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

// Enhanced Create Database with Loading States
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
        const response = await fetch(`/hosting/${HOSTING_CONFIG.accountUsername}/databases`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': HOSTING_CONFIG.csrfToken,
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

// Enhanced Delete Database with Loading States
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
                const response = await fetch(`/hosting/${HOSTING_CONFIG.accountUsername}/databases`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': HOSTING_CONFIG.csrfToken,
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

// Enhanced phpMyAdmin with Loading State
function openPhpMyAdmin(dbName) {
    const btnId = `phpmyadmin-btn-${dbName}`;
    const btn = document.querySelector(`[onclick="openPhpMyAdmin('${dbName}')"]`);
    
    if (btn) {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<div class="spinner-border spinner-border-sm me-1" role="status"></div> Opening...';
        btn.disabled = true;
    }
    
    fetch(`/hosting/${HOSTING_CONFIG.accountUsername}/phpmyadmin-link`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': HOSTING_CONFIG.csrfToken,
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

// Enhanced Sync with Loading State
async function syncDatabases() {
    const syncBtn = document.getElementById('sync-databases-btn');
    const originalHTML = syncBtn.innerHTML;
    
    syncBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1" role="status"></div> Syncing...';
    syncBtn.disabled = true;
    
    try {
        const response = await fetch(`/hosting/${HOSTING_CONFIG.accountUsername}/databases-sync`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': HOSTING_CONFIG.csrfToken,
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Database form handling
    const dbForm = document.getElementById('createDatabaseForm');
    if (dbForm) {
        dbForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await createDatabase();
        });
    }

    // Load data if account is active and verified
    if (HOSTING_CONFIG.accountStatus === 'active' && HOSTING_CONFIG.isCpanelVerified) {
        loadDatabases();
    }
});

// Utility Functions
function formatNumber(num) {
    if (typeof num !== 'number') return num || '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Initialize Everything
document.addEventListener('DOMContentLoaded', function() {
    // Database form handling
    const dbForm = document.getElementById('createDatabaseForm');
    if (dbForm) {
        dbForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await createDatabase();
        });
    }

    // Load data if account is active
    if (accountStatus === 'active') {
        // Always load account stats (includes database limits)
        loadAccountStats();
        
        if (isCpanelVerified) {
            // Load database list only if verified
            loadDatabases();
        } else {
            // Show database info without list if not verified
            updateDatabaseInfo(0, 1); // Default for free hosting
            
            const hasVerifyFlag = localStorage.getItem(CPANEL_VERIFY_KEY);
            if (hasVerifyFlag) {
                isVerifying = true;
                startVerificationCheck();
            }
        }
    }
});

// Cleanup
window.addEventListener('beforeunload', () => {
    if (verificationCheckTimer) {
        clearInterval(verificationCheckTimer);
    }
});
</script>
@endsection