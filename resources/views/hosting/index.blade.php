@extends('layouts.master')

@section('title') @lang('translation.Hosting_Accounts') @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.Hosting') @endslot
        @slot('title') @lang('translation.Hosting_Accounts') @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0">@lang('translation.Your_Hosting_Accounts')</h4>
                        @if(count($accounts) < 3)
                            <a href="{{ route('hosting.create') }}" class="btn btn-primary waves-effect waves-light">
                                <i data-feather="plus" class="font-size-16 align-middle me-2"></i> @lang('translation.Create_New_Account')
                            </a>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>@lang('translation.Username')</th>
                                <th>@lang('translation.Label')</th>
                                <th>@lang('translation.Status')</th>
                                <th class="text-end">@lang('translation.Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($accounts as $key => $account)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $account->username }}</td>
                                <td>{{ $account->label }}</td>
                                <td>
                                    @if(in_array($account->status, ['pending', 'deactivating', 'reactivating']))
                                        <span class="badge bg-warning">
                                            <i data-feather="loader" class="font-size-14 align-middle me-1"></i>
                                            @lang('translation.status_' . $account->status)
                                        </span>
                                    @elseif($account->status === 'active')
                                        @if($account->cpanel_verified)
                                            <span class="badge bg-success">
                                                <i data-feather="check-circle" class="font-size-14 align-middle me-1"></i>
                                                @lang('translation.status_active') & Verified
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i data-feather="shield" class="font-size-14 align-middle me-1"></i>
                                                @lang('translation.status_active') (Unverified)
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-danger">
                                            <i data-feather="x-circle" class="font-size-14 align-middle me-1"></i>
                                            @lang('translation.status_' . $account->status)
                                        </span>
                                        
                                        @if($account->admin_deactivated)
                                            <span class="badge bg-dark ms-1">
                                                <i data-feather="shield" class="font-size-14 align-middle me-1"></i>
                                                @lang('translation.Admin')
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end">
                                    @php
                                        $btnClass = 'btn-danger'; // Default for inactive states
                                        if ($account->status === 'active' && $account->cpanel_verified) {
                                            $btnClass = 'btn-success';
                                        } elseif ($account->status === 'active' && !$account->cpanel_verified) {
                                            $btnClass = 'btn-warning';
                                        } elseif (in_array($account->status, ['pending', 'deactivating', 'reactivating'])) {
                                            $btnClass = 'btn-warning';
                                        }
                                    @endphp
                                    <a href="{{ route('hosting.view', $account->username) }}" 
                                       class="btn btn-sm waves-effect waves-light {{ $btnClass }}">
                                        <i data-feather="settings" class="font-size-14 align-middle me-1"></i>
                                        @lang('translation.Manage')
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">@lang('translation.No_accounts_found')</td>
                            </tr>
                            @endforelse
                        </tbody>
                        </table>
                    </div>

                    @if(count($accounts) > 0)
                        <div class="mt-3">
                            <span class="badge bg-info">{{ count($accounts) }} / 3 @lang('translation.Free_accounts')</span>
                            @php
                                $verifiedCount = $accounts->where('status', 'active')->where('cpanel_verified', true)->count();
                                $unverifiedCount = $accounts->where('status', 'active')->where('cpanel_verified', false)->count();
                            @endphp
                            @if($verifiedCount > 0)
                                <span class="badge bg-success ms-1">{{ $verifiedCount }} Verified</span>
                            @endif
                            @if($unverifiedCount > 0)
                                <span class="badge bg-warning ms-1">{{ $unverifiedCount }} Unverified</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection