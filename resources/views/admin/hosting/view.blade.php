@extends('layouts.master')

@section('title') Account Details - {{ $account->username }} @endsection

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17/dist/sweetalert2.all.min.js"></script>

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

.stats-card.locked {
    opacity: 0.7;
    cursor: not-allowed;
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

.stats-card.locked .icon-box {
    background: #6c757d !important;
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

.verification-alert {
    background: linear-gradient(135deg, rgba(255,185,0,0.1), rgba(255,185,0,0.05));
    border-left: 4px solid var(--minia-warning);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.admin-alert {
    background: linear-gradient(135deg, rgba(240,101,72,0.1), rgba(240,101,72,0.05));
    border-left: 4px solid var(--minia-danger);
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

.locked-section {
    position: relative;
    opacity: 0.5;
    pointer-events: none;
}

.locked-section::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 10;
}

.locked-section::after {
    content: "🔒 User must verify cPanel to view sensitive data";
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
    text-align: center;
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

.status-warning {
    background: rgba(255,185,2,0.1);
    color: var(--minia-warning);
}

.status-suspended {
    background: rgba(240,101,72,0.1);
    color: var(--minia-danger);
}

.status-pending {
    background: rgba(255,185,2,0.1);
    color: var(--minia-warning);
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

.table-hover tbody tr:hover {
    background-color: rgba(75,56,179,0.05);
}

.badge {
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 1rem;
    }
    
    .quick-action-card {
        margin-bottom: 0.75rem;
    }
    
    .locked-section::after {
        font-size: 0.875rem;
        padding: 0.75rem 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
}
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('admin.hosting.index') }}">Hosting Accounts</a> @endslot
        @slot('title') Account Details @endslot
    @endcomponent

    {{-- Alert Messages --}}
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i data-feather="check-circle" class="me-2" style="width: 16px; height: 16px;"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i data-feather="x-circle" class="me-2" style="width: 16px; height: 16px;"></i>
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Verification Status Alert --}}
    @if($account->status === 'active' && !$account->cpanel_verified)
    <div class="verification-alert">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <i data-feather="alert-triangle" class="text-warning" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h5 class="mb-1 fw-semibold">⚠️ Account Not Verified</h5>
                <p class="mb-0 text-muted">This user has not verified their cPanel access yet. Sensitive information is hidden for security. The user must login to cPanel to complete verification.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Admin Deactivation Notice --}}
    @if($account->admin_deactivated)
    <div class="admin-alert">
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i data-feather="shield-off" style="font-size: 1.5rem;"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mt-0 mb-1">🛡️ Admin Deactivated Account</h5>
                <p class="mb-0">This account has been administratively deactivated and cannot be reactivated by the user.</p>
                <p class="mt-2 mb-0"><strong>Reason:</strong> {{ $account->admin_deactivation_reason }}</p>
                <p class="mt-1 mb-0"><strong>Deactivated:</strong> {{ $account->admin_deactivated_at->format('F j, Y, g:i a') }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Account Overview Stats --}}
    <div class="row mb-4">
        {{-- Account Status --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="stats-label mb-2">Account Status</p>
                        @if($account->status === 'active')
                            @if($account->cpanel_verified)
                                <span class="status-badge status-active">
                                    <i data-feather="check-circle" class="me-1" style="width: 12px; height: 12px;"></i>
                                    Active & Verified
                                </span>
                            @else
                                <span class="status-badge status-warning">
                                    <i data-feather="shield" class="me-1" style="width: 12px; height: 12px;"></i>
                                    Active (Unverified)
                                </span>
                            @endif
                        @elseif($account->status === 'suspended')
                            <span class="status-badge status-suspended">
                                <i data-feather="x-circle" class="me-1" style="width: 12px; height: 12px;"></i>
                                Suspended
                            </span>
                        @else
                            <span class="status-badge status-pending">
                                <i data-feather="clock" class="me-1" style="width: 12px; height: 12px;"></i>
                                {{ ucfirst($account->status) }}
                            </span>
                        @endif
                    </div>
                    <div class="icon-box">
                        <i data-feather="server" class="font-size-20"></i>
                    </div>
                </div>
                <div class="mt-auto">
                    <small class="text-muted d-block">Created: {{ $account->created_at->format('M j, Y') }}</small>
                    <small class="text-muted">Updated: {{ $account->updated_at->format('M j, Y') }}</small>
                </div>
            </div>
        </div>

        {{-- User Information --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="stats-label mb-2">Account Owner</p>
                        <div class="stats-value">{{ $account->user->name ?? 'Unknown' }}</div>
                        <small class="text-muted d-block">{{ $account->user->email }}</small>
                        <small class="text-muted">User ID: {{ $account->user_id }}</small>
                    </div>
                    <div class="icon-box">
                        <i data-feather="user" class="font-size-20"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Domain Info --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="stats-label mb-2">Primary Domain</p>
                        <div class="stats-value">{{ $account->domain }}</div>
                        @if($account->status === 'active')
                            <a href="http://{{ $account->domain }}" target="_blank" class="text-primary small">
                                <i class="ri-external-link-line me-1"></i>Visit Website
                            </a>
                        @endif
                    </div>
                    <div class="icon-box">
                        <i data-feather="globe" class="font-size-20"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Verification Status --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card {{ !$account->cpanel_verified && $account->status === 'active' ? 'locked' : '' }}">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="stats-label mb-2">cPanel Verification</p>
                        @if($account->cpanel_verified)
                            <span class="status-badge status-active">
                                <i data-feather="check-circle" class="me-1" style="width: 12px; height: 12px;"></i>
                                Verified
                            </span>
                            <small class="text-muted d-block mt-1">{{ $account->cpanel_verified_at->format('M j, Y') }}</small>
                        @elseif($account->status === 'active')
                            <span class="status-badge status-warning">
                                <i data-feather="shield" class="me-1" style="width: 12px; height: 12px;"></i>
                                Pending
                            </span>
                            <small class="text-muted d-block mt-1">User must verify</small>
                        @else
                            <span class="status-badge status-pending">
                                <i data-feather="minus-circle" class="me-1" style="width: 12px; height: 12px;"></i>
                                N/A
                            </span>
                        @endif
                    </div>
                    <div class="icon-box">
                        <i data-feather="shield" class="font-size-20"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Admin Quick Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i data-feather="users" class="me-2 text-primary"></i>
                            Admin Actions
                        </h5>
                        <span class="badge bg-info-subtle text-info">
                            <i data-feather="settings" class="me-1" style="width: 12px; height: 12px;"></i>
                            Administrator Panel
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- cPanel Access --}}
                        <div class="col-lg-3 col-md-6">
                            @if($account->status === 'active' && $account->cpanel_verified)
                                <a href="{{ route('admin.hosting.cpanel', $account->username) }}" 
                                   target="_blank"
                                   class="quick-action-card">
                                    <div class="icon-box">
                                        <i data-feather="monitor" class="font-size-20"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">cPanel Access</h6>
                                    <p class="text-muted mb-0 small">Direct admin access</p>
                                </a>
                            @else
                                <div class="quick-action-card disabled">
                                    <div class="icon-box">
                                        <i data-feather="monitor" class="font-size-20"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">cPanel Access</h6>
                                    <p class="text-muted mb-0 small">
                                        @if($account->status !== 'active')
                                            Account not active
                                        @else
                                            User must verify first
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- File Manager --}}
                        <div class="col-lg-3 col-md-6">
                            @if($account->status === 'active' && $account->cpanel_verified)
                                <a href="{{ route('admin.hosting.filemanager', $account->username) }}"
                                   target="_blank"
                                   class="quick-action-card">
                                    <div class="icon-box">
                                        <i data-feather="folder" class="font-size-20"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">File Manager</h6>
                                    <p class="text-muted mb-0 small">Manage files</p>
                                </a>
                            @else
                                <div class="quick-action-card disabled">
                                    <div class="icon-box">
                                        <i data-feather="folder" class="font-size-20"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">File Manager</h6>
                                    <p class="text-muted mb-0 small">
                                        @if($account->status !== 'active')
                                            Account not active
                                        @else
                                            User must verify first
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- User Account View --}}
                        <div class="col-lg-3 col-md-6">
                            <a href="{{ route('hosting.view', $account->username) }}" 
                               target="_blank"
                               class="quick-action-card">
                                <div class="icon-box" style="background: linear-gradient(135deg, var(--minia-info), #68e1fd);">
                                    <i data-feather="eye" class="font-size-20"></i>
                                </div>
                                <h6 class="fw-medium mb-1">User View</h6>
                                <p class="text-muted mb-0 small">See user's perspective</p>
                            </a>
                        </div>

                        {{-- Admin Action --}}
                        <div class="col-lg-3 col-md-6">
                            @if($account->status === 'active' && !$account->admin_deactivated)
                                <button type="button" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#adminDeactivateModal"
                                       class="quick-action-card w-100 border-0 bg-transparent">
                                    <div class="icon-box" style="background: linear-gradient(135deg, var(--minia-danger), #f06548);">
                                        <i data-feather="shield-off" class="font-size-20"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">Admin Deactivate</h6>
                                    <p class="text-muted mb-0 small">Block account access</p>
                                </button>
                            @elseif($account->status === 'deactivated' && $account->admin_deactivated)
                                <button type="button" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#adminReactivateModal"
                                       class="quick-action-card w-100 border-0 bg-transparent">
                                    <div class="icon-box" style="background: linear-gradient(135deg, var(--minia-success), #45cb85);">
                                        <i data-feather="shield" class="font-size-20"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">Admin Reactivate</h6>
                                    <p class="text-muted mb-0 small">Remove restrictions</p>
                                </button>
                            @else
                                <div class="quick-action-card w-100" style="opacity: 0.5; cursor: not-allowed; pointer-events: none;">
                                    <div class="icon-box" style="background: #6c757d;">
                                        <i data-feather="shield" class="font-size-20"></i>
                                    </div>
                                    <h6 class="fw-medium mb-1">Admin Action</h6>
                                    <p class="text-muted mb-0 small">Not available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Account Details & Connection Info --}}
    <div class="row mb-4">
        {{-- Account Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i data-feather="settings" class="me-2 text-primary"></i>
                            Account Credentials
                        </h5>
                        @if(!$account->cpanel_verified && $account->status === 'active')
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-lock-line me-1"></i>Protected - User Must Verify
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body {{ (!$account->cpanel_verified && $account->status === 'active') ? 'locked-section' : '' }}">
                    @if(!$account->cpanel_verified && $account->status === 'active')
                        {{-- Locked State for Unverified Accounts --}}
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i data-feather="lock" class="text-warning" style="font-size: 4rem; opacity: 0.5;"></i>
                            </div>
                            <h5 class="text-warning mb-3">Account Credentials Protected</h5>
                            <p class="text-muted mb-4">
                                For security reasons, sensitive account information is hidden until the user verifies their cPanel access.<br>
                                <strong>Only the account owner can complete verification.</strong>
                            </p>
                            <div class="alert alert-info">
                                <i data-feather="info" class="me-2" style="width: 16px; height: 16px;"></i>
                                <strong>Note:</strong> Administrators cannot verify accounts on behalf of users for security compliance.
                            </div>
                        </div>
                    @else
                        {{-- Account Details Content --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Username</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="{{ $account->username }}" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('{{ $account->username }}', this)">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="position-relative">
                                    <input type="password" 
                                           value="{{ $account->password }}" 
                                           id="password-field" 
                                           class="form-control pe-5" 
                                           readonly
                                           onclick="copyToClipboard('{{ $account->password }}', this)"
                                           style="cursor: pointer;">
                                    <button type="button" 
                                            class="btn btn-link position-absolute end-0 top-50 translate-middle-y border-0 text-muted"
                                            onclick="togglePassword('password-field', this)"
                                            style="z-index: 10; padding: 0.375rem 0.75rem;">
                                        <i data-feather="eye-off" style="width: 16px; height: 16px;"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Primary Domain</label>
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

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">MySQL Host</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="{{ $account->mysql_host }}" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('{{ $account->mysql_host }}', this)">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">SQL Server</label>
                                <div class="copy-field">
                                    <input type="text" 
                                           value="{{ $account->sql_server ?: 'Not assigned yet' }}" 
                                           class="copy-input form-control" 
                                           readonly 
                                           onclick="copyToClipboard('{{ $account->sql_server ?? '' }}', this)">
                                </div>
                            </div>
                        </div>

                        {{-- Admin Quick Links --}}
                        <div class="mt-4">
                            <label class="form-label fw-semibold">Admin Quick Actions</label>
                            <div class="d-flex flex-wrap gap-2">
                                @if($account->status === 'active' && $account->cpanel_verified)
                                    <a href="{{ route('admin.hosting.cpanel', $account->username) }}"
                                       target="_blank"
                                       class="btn btn-primary btn-sm">
                                        <i data-feather="monitor" class="me-1" style="width: 14px; height: 14px;"></i> cPanel
                                    </a>
                                    
                                    <a href="{{ route('admin.hosting.filemanager', $account->username) }}"
                                       target="_blank"
                                       class="btn btn-info btn-sm">
                                        <i data-feather="folder" class="me-1" style="width: 14px; height: 14px;"></i> Files
                                    </a>
                                @else
                                    <button type="button" class="btn btn-secondary btn-sm" disabled>
                                        <i data-feather="monitor" class="me-1" style="width: 14px; height: 14px;"></i> cPanel
                                    </button>
                                    
                                    <button type="button" class="btn btn-secondary btn-sm" disabled>
                                        <i data-feather="folder" class="me-1" style="width: 14px; height: 14px;"></i> Files
                                    </button>
                                    
                                    <small class="text-muted align-self-center ms-2">
                                        @if($account->status !== 'active')
                                            Account must be active
                                        @else
                                            User must verify cPanel first
                                        @endif
                                    </small>
                                @endif
                                
                                <a href="https://{{ $account->domain }}" 
                                   target="_blank"
                                   class="btn btn-success btn-sm">
                                    <i data-feather="external-link" class="me-1" style="width: 14px; height: 14px;"></i> Visit Site
                                </a>

                                <a href="{{ route('hosting.view', $account->username) }}" 
                                   target="_blank"
                                   class="btn btn-outline-secondary btn-sm">
                                    <i data-feather="eye" class="me-1" style="width: 14px; height: 14px;"></i> User View
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Connection Details --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i data-feather="upload" class="me-2 text-primary"></i>
                            FTP Connection
                        </h5>
                        @if(!$account->cpanel_verified && $account->status === 'active')
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-lock-line me-1"></i>Protected
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body {{ (!$account->cpanel_verified && $account->status === 'active') ? 'locked-section' : '' }}">
                    @if(!$account->cpanel_verified && $account->status === 'active')
                        {{-- Locked State --}}
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i data-feather="folder" class="text-warning" style="font-size: 3rem; opacity: 0.5;"></i>
                            </div>
                            <h6 class="text-warning mb-3">FTP Access Protected</h6>
                            <p class="text-muted mb-0 small">
                                FTP credentials are secured until user verification.
                            </p>
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
                                        <i data-feather="eye-off" style="width: 16px; height: 16px;"></i>
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
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Related Support Tickets --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i data-feather="headphones" class="me-2 text-primary"></i>
                            Related Support Tickets
                        </h5>
                        <div class="d-flex gap-2">
                            <span class="badge bg-info-subtle text-info">
                                {{ count($relatedTickets) }} tickets found
                            </span>
                            <a href="{{ route('admin.tickets.index') }}?user_id={{ $account->user_id }}" 
                               class="btn btn-sm btn-outline-primary">
                                <i data-feather="external-link" class="me-1" style="width: 14px; height: 14px;"></i>
                                View All User Tickets
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($relatedTickets) > 0)
                        <div class="table-responsive">
                            <table class="table table-nowrap table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Last Update</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($relatedTickets as $ticket)
                                        <tr>
                                            <td>
                                                <span class="fw-medium">#{{ substr($ticket->uuid, 0, 8) }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ \Illuminate\Support\Str::limit($ticket->title, 40) }}</div>
                                                @if(\Illuminate\Support\Str::contains(strtolower($ticket->title), ['hosting', 'cpanel', $account->username, $account->domain]))
                                                    <small class="text-success">
                                                        <i data-feather="link" style="width: 12px; height: 12px;"></i>
                                                        Hosting related
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($ticket->category)
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        {{ $ticket->category->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">No category</span>
                                                @endif
                                            </td>
                                            <td>
                                                @switch($ticket->status)
                                                    @case('open')
                                                        <span class="badge bg-success-subtle text-success">
                                                            <i data-feather="circle" style="width: 8px; height: 8px;" class="me-1"></i>
                                                            Open
                                                        </span>
                                                        @break
                                                    @case('answered')
                                                        <span class="badge bg-info-subtle text-info">
                                                            <i data-feather="message-circle" style="width: 8px; height: 8px;" class="me-1"></i>
                                                            Answered
                                                        </span>
                                                        @break
                                                    @case('pending')
                                                        <span class="badge bg-warning-subtle text-warning">
                                                            <i data-feather="clock" style="width: 8px; height: 8px;" class="me-1"></i>
                                                            Pending
                                                        </span>
                                                        @break
                                                    @case('closed')
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            <i data-feather="x-circle" style="width: 8px; height: 8px;" class="me-1"></i>
                                                            Closed
                                                        </span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            {{ ucfirst($ticket->status) }}
                                                        </span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $ticket->created_at->format('M j, Y') }}</small>
                                                <br>
                                                <small class="text-muted">{{ $ticket->created_at->format('g:i A') }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $ticket->updated_at->diffForHumans() }}</small>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <a href="{{ route('admin.tickets.show', $ticket) }}" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="View Ticket">
                                                        <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                                    </a>
                                                    @if($ticket->status !== 'closed')
                                                        <a href="{{ route('admin.tickets.show', $ticket) }}#reply" 
                                                           class="btn btn-sm btn-outline-success"
                                                           title="Reply">
                                                            <i data-feather="message-circle" style="width: 14px; height: 14px;"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if(count($relatedTickets) >= 10)
                            <div class="mt-3 text-center">
                                <a href="{{ route('admin.tickets.index') }}?user_id={{ $account->user_id }}" 
                                   class="btn btn-outline-primary">
                                    <i data-feather="plus" class="me-1" style="width: 16px; height: 16px;"></i>
                                    View All {{ $account->user->name }}'s Tickets
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i data-feather="headphones" class="text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                            </div>
                            <h5 class="text-muted mb-3">No Related Tickets Found</h5>
                            <p class="text-muted mb-4">
                                No support tickets related to this hosting account were found.<br>
                                This could mean the user hasn't submitted any hosting-related support requests.
                            </p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="{{ route('admin.tickets.index') }}?user_id={{ $account->user_id }}" 
                                   class="btn btn-outline-primary">
                                    <i data-feather="search" class="me-1" style="width: 16px; height: 16px;"></i>
                                    View All User Tickets
                                </a>
                                <a href="{{ route('admin.tickets.index') }}" 
                                   class="btn btn-outline-secondary">
                                    <i data-feather="list" class="me-1" style="width: 16px; height: 16px;"></i>
                                    All Tickets
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Deactivate Modal -->
    @if($account->status === 'active' && !$account->admin_deactivated)
    <div class="modal fade" id="adminDeactivateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.hosting.settings.update', $account->username) }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="admin_deactivate">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i data-feather="shield-off" class="me-2 text-danger"></i>
                            Admin Deactivate Account
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i data-feather="alert-triangle" class="me-2" style="width: 16px; height: 16px;"></i>
                            <strong>Warning:</strong> This will immediately deactivate the account and prevent the user from reactivating it themselves.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Username</label>
                            <input type="text" class="form-control" value="{{ $account->username }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Owner</label>
                            <input type="text" class="form-control" value="{{ $account->user->email }}" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deactivation Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" rows="4" 
                                placeholder="Enter detailed reason for deactivation (will be shown to user)" required></textarea>
                            <small class="text-muted">This reason will be displayed to the user and recorded in logs.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i data-feather="shield-off" class="me-1"></i> Deactivate Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Admin Reactivate Modal -->
    @if($account->status === 'deactivated' && $account->admin_deactivated)
    <div class="modal fade" id="adminReactivateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.hosting.settings.update', $account->username) }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="admin_reactivate">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i data-feather="shield" class="me-2 text-success"></i>
                            Admin Reactivate Account
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i data-feather="info" class="me-2" style="width: 16px; height: 16px;"></i>
                            <strong>Action:</strong> This will remove the admin deactivation restrictions and allow the user to reactivate their account.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Username</label>
                            <input type="text" class="form-control" value="{{ $account->username }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Owner</label>
                            <input type="text" class="form-control" value="{{ $account->user->email }}" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Previous Deactivation Reason</label>
                            <textarea class="form-control" rows="3" readonly>{{ $account->admin_deactivation_reason }}</textarea>
                            <small class="text-muted">Deactivated on: {{ $account->admin_deactivated_at->format('F j, Y, g:i a') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i data-feather="shield" class="me-1"></i> Remove Admin Restrictions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Toast Notification --}}
    <div id="toast" class="toast-notification">
        <div class="d-flex align-items-center">
            <i data-feather="check-circle" class="text-success me-2" id="toast-icon" style="width: 16px; height: 16px;"></i>
            <span id="toast-message">Copied to clipboard!</span>
        </div>
    </div>

    {{-- Hidden Data --}}
    <input type="hidden" id="accountUsername" value="{{ $account->username }}">
@endsection

@section('script')
<script>
// Password Toggle Function
function togglePassword(fieldId, button) {
    const field = document.getElementById(fieldId);
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.setAttribute('data-feather', 'eye');
    } else {
        field.type = 'password';
        icon.setAttribute('data-feather', 'eye-off');
    }
    
    // Update feather icon
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Copy to Clipboard Functions
function copyToClipboard(text, element) {
    if (!text || text === '••••••••' || text === 'Loading...' || text === 'N/A' || text === 'Not assigned yet') {
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

// Toast Notification System
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');
    
    // Set icon and color based on type
    switch(type) {
        case 'success':
            toastIcon.setAttribute('data-feather', 'check-circle');
            toastIcon.className = 'text-success me-2';
            break;
        case 'error':
            toastIcon.setAttribute('data-feather', 'x-circle');
            toastIcon.className = 'text-danger me-2';
            break;
        case 'warning':
            toastIcon.setAttribute('data-feather', 'alert-circle');
            toastIcon.className = 'text-warning me-2';
            break;
        case 'info':
            toastIcon.setAttribute('data-feather', 'info');
            toastIcon.className = 'text-info me-2';
            break;
        default:
            toastIcon.setAttribute('data-feather', 'check-circle');
            toastIcon.className = 'text-success me-2';
    }
    
    // Update feather icon
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather Icons if available
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    console.log('🛡️ Admin hosting panel initialized');
});
</script>
@endsection