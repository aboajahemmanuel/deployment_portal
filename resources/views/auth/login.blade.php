@extends('layouts.auth')

@section('title', 'Login | Rhymes Author Platform')

@section('page-title', 'Sign In')

@section('page-description', 'Access your Rhymes Author account')

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="form-group">
        <div class="form-label-group">
            <label class="form-label" for="email">Email</label>
        </div>
        <div class="form-control-wrap">
            <input type="email" name="email" class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" placeholder="Enter your email address" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <div class="form-label-group d-flex justify-content-between align-items-center">
            <label class="form-label" for="password">Password</label>
            @if (Route::has('password.request'))
                <a class="link link-primary link-sm" href="{{ route('password.request') }}">Forgot Password?</a>
            @endif
        </div>
        <div class="form-control-wrap position-relative">
            <a href="#" class="form-icon form-icon-right passcode-switch lg" data-target="password">
                <em class="passcode-icon icon-show icon ni ni-eye"></em>
                <em class="passcode-icon icon-hide icon ni ni-eye-off"></em>
            </a>
            <input type="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" id="password" placeholder="Enter your password" required autocomplete="current-password">
            @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group d-flex align-items-center justify-content-between">
        <div class="custom-control custom-control-xs custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="remember_me" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="custom-control-label" for="remember_me">Remember me</label>
        </div>
        <button type="submit" class="btn btn-lg btn-primary d-flex align-items-center justify-content-center" id="login-submit-btn">
            <span id="login-btn-text">Sign in</span>
            <span id="login-btn-spinner" class="spinner-border spinner-border-sm ms-2" style="display:none;" role="status" aria-hidden="true"></span>
        </button>
    </div>
</form>
@endsection

@section('auth-links')
New on our platform? <a href="{{ route('register') }}"><strong>Create an account</strong></a>
@endsection

@section('social-login')
<li class="nav-item"><a class="link link-primary fw-normal py-2 px-3" href="#">Facebook</a></li>
<li class="nav-item"><a class="link link-primary fw-normal py-2 px-3" href="#">Google</a></li>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.querySelector('form[action="{{ route('login') }}"]');
        var btn = document.getElementById('login-submit-btn');
        var btnText = document.getElementById('login-btn-text');
        var btnSpinner = document.getElementById('login-btn-spinner');
        
        if(form && btn && btnText && btnSpinner) {
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btnSpinner.style.display = 'inline-block';
                btnText.textContent = 'Signing in...';
            });
        }
    });
</script>
@endpush
