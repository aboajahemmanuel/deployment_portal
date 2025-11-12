@extends('layouts.deployment')

@section('title', 'Profile | Rhymes Platform')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Profile</h3>
            <div class="nk-block-des text-soft">
                <p>Manage your profile information and account settings</p>
            </div>
        </div>
    </div>
</div>

<div class="nk-block">
    <div class="row g-gs">
        <div class="col-lg-8">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="nk-block-head">
                        <h5 class="title">Update Profile Information</h5>
                        <p>Update your account's profile information and email address.</p>
                    </div>
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
            
            <div class="card card-bordered mt-4">
                <div class="card-inner">
                    <div class="nk-block-head">
                        <h5 class="title">Update Password</h5>
                        <p>Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                    @include('profile.partials.update-password-form')
                </div>
            </div>
            
            <div class="card card-bordered mt-4">
                <div class="card-inner">
                    <div class="nk-block-head">
                        <h5 class="title">Delete Account</h5>
                        <p>Once your account is deleted, all of its resources and data will be permanently deleted.</p>
                    </div>
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="nk-block-head">
                        <h5 class="title">Profile Information</h5>
                    </div>
                    <div class="profile-stats">
                        <div class="profile-stat-item">
                            <strong>Name:</strong> {{ $user->name }}
                        </div>
                        <div class="profile-stat-item mt-2">
                            <strong>Email:</strong> {{ $user->email }}
                        </div>
                        <div class="profile-stat-item mt-2">
                            <strong>Member Since:</strong> {{ $user->created_at->format('M d, Y') }}
                        </div>
                        @if($user->email_verified_at)
                        <div class="profile-stat-item mt-2">
                            <strong>Email Verified:</strong> {{ $user->email_verified_at->format('M d, Y') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection