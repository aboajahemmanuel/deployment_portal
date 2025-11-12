@extends('layouts.deployment-admin')

@section('title', 'Dashboard | Deployment Manager')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Welcome to Deployment Manager</h3>
                <div class="nk-block-des text-soft">
                    <p>Your deployment management dashboard</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="row g-gs">
        <div class="col-12">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="row g-4 align-center">
                        <div class="col-lg-8">
                            <div class="nk-block-content">
                                <h4 class="title">Hello, {{ auth()->user()->name }}!</h4>
                                <p>Welcome to the Deployment Management System. Your account is set up and ready to use.</p>
                                <p class="text-soft">If you need access to specific projects or features, please contact your administrator.</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="nk-block-content text-center">
                                <div class="user-avatar xl bg-primary">
                                    <span>{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                                </div>
                                <div class="mt-3">
                                    <h6 class="title">{{ auth()->user()->name }}</h6>
                                    <span class="sub-text">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-gs">
        <div class="col-md-6 col-xxl-4">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group align-start mb-2">
                        <div class="card-title">
                            <h6 class="title">Profile Settings</h6>
                        </div>
                        <div class="card-tools">
                            <em class="card-hint-icon icon ni ni-user-fill-c"></em>
                        </div>
                    </div>
                    <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                        <div class="nk-sale-data">
                            <span class="amount">Manage your account settings and preferences</span>
                        </div>
                        <div class="nk-sales-ck">
                            <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-4">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group align-start mb-2">
                        <div class="card-title">
                            <h6 class="title">System Status</h6>
                        </div>
                        <div class="card-tools">
                            <em class="card-hint-icon icon ni ni-activity-fill"></em>
                        </div>
                    </div>
                    <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                        <div class="nk-sale-data">
                            <span class="amount">All systems operational</span>
                        </div>
                        <div class="nk-sales-ck">
                            <span class="badge badge-sm badge-dot has-bg bg-success d-none d-sm-inline-flex">Online</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-4">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group align-start mb-2">
                        <div class="card-title">
                            <h6 class="title">Need Help?</h6>
                        </div>
                        <div class="card-tools">
                            <em class="card-hint-icon icon ni ni-help-fill"></em>
                        </div>
                    </div>
                    <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                        <div class="nk-sale-data">
                            <span class="amount">Contact your administrator for assistance</span>
                        </div>
                        <div class="nk-sales-ck">
                            <a href="mailto:admin@company.com" class="btn btn-outline-light">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Info -->
    <div class="row g-gs">
        <div class="col-12">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">Account Information</h6>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <div class="form-control-wrap">
                                    <input type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" value="{{ auth()->user()->roles->pluck('name')->implode(', ') ?: 'User' }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Account Status</label>
                                <div class="form-control-wrap">
                                    <span class="badge badge-sm badge-dot has-bg bg-{{ auth()->user()->email_verified_at ? 'success' : 'warning' }} d-inline-flex">
                                        {{ auth()->user()->email_verified_at ? 'Verified' : 'Unverified' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Member Since</label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" value="{{ auth()->user()->created_at->format('F d, Y') }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
