@extends('layouts.auth')

@section('title', 'Reset Password | Rhymes Author Platform')

@section('page-title', 'Reset Password')

@section('page-description', 'Enter your email and new password to reset your account password.')

@section('content')
<form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    
    <div class="form-group">
        <div class="form-label-group">
            <label class="form-label" for="email">Email</label>
        </div>
        <div class="form-control-wrap">
            <input type="email" name="email" class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" placeholder="Enter your email address" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <div class="form-label-group">
            <label class="form-label" for="password">New Password</label>
        </div>
        <div class="form-control-wrap position-relative">
            <a href="#" class="form-icon form-icon-right passcode-switch lg" data-target="password">
                <em class="passcode-icon icon-show icon ni ni-eye"></em>
                <em class="passcode-icon icon-hide icon ni ni-eye-off"></em>
            </a>
            <input type="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" id="password" placeholder="Enter new password" required autocomplete="new-password">
            @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <div class="form-label-group">
            <label class="form-label" for="password_confirmation">Confirm Password</label>
        </div>
        <div class="form-control-wrap">
            <input type="password" name="password_confirmation" class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror" id="password_confirmation" placeholder="Confirm new password" required autocomplete="new-password">
            @error('password_confirmation')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn btn-lg btn-primary btn-block d-flex align-items-center justify-content-center" id="reset-submit-btn">
            <span id="reset-btn-text">Reset Password</span>
            <span id="reset-btn-spinner" class="spinner-border spinner-border-sm ms-2" style="display:none;" role="status" aria-hidden="true"></span>
        </button>
    </div>
</form>
@endsection

@section('auth-links')
Remember your password? <a href="{{ route('login') }}"><strong>Sign in</strong></a>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.querySelector('form[action="{{ route('password.store') }}"]');
        var btn = document.getElementById('reset-submit-btn');
        var btnText = document.getElementById('reset-btn-text');
        var btnSpinner = document.getElementById('reset-btn-spinner');
        
        if(form && btn && btnText && btnSpinner) {
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btnSpinner.style.display = 'inline-block';
                btnText.textContent = 'Resetting...';
            });
        }
    });
</script>
@endpush
