@extends('layouts.auth')

@section('title', 'Register | Rhymes Author Platform')

@section('page-title', 'Register')

@section('page-description', 'Create Your Rhymes Author Account')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="form-group">
        <label class="form-label" for="name">Name</label>
        <div class="form-control-wrap">
            <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" id="name" placeholder="Enter your name" value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <div class="form-control-wrap">
            <input type="email" name="email" class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" placeholder="Enter your email address" value="{{ old('email') }}" required autocomplete="username">
            @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="phone">Phone (Optional)</label>
        <div class="form-control-wrap">
            <input type="text" name="phone" class="form-control form-control-lg @error('phone') is-invalid @enderror" id="phone" placeholder="Enter your phone number" value="{{ old('phone') }}" autocomplete="tel">
            @error('phone')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="form-control-wrap position-relative">
            <a href="#" class="form-icon form-icon-right passcode-switch lg" data-target="password">
                <em class="passcode-icon icon-show icon ni ni-eye"></em>
                <em class="passcode-icon icon-hide icon ni ni-eye-off"></em>
            </a>
            <input type="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" id="password" placeholder="Enter your password" required autocomplete="new-password">
            @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <div class="form-control-wrap">
            <input type="password" name="password_confirmation" class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror" id="password_confirmation" placeholder="Confirm your password" required autocomplete="new-password">
            @error('password_confirmation')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <div class="custom-control custom-control-xs custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="checkbox" name="terms" required {{ old('terms') ? 'checked' : '' }}>
            <label class="custom-control-label" for="checkbox">I agree to Rhymes Platform <a href="#">Privacy Policy</a> &amp; <a href="#">Terms of Service</a></label>
        </div>
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn btn-lg btn-primary btn-block d-flex align-items-center justify-content-center" id="register-submit-btn">
            <span id="register-btn-text">Register</span>
            <span id="register-btn-spinner" class="spinner-border spinner-border-sm ms-2" style="display:none;" role="status" aria-hidden="true"></span>
        </button>
    </div>
</form>
@endsection

@section('auth-links')
Already have an account? <a href="{{ route('login') }}"><strong>Sign in instead</strong></a>
@endsection

@section('social-login')
<li class="nav-item"><a class="link link-primary fw-normal py-2 px-3" href="#">Facebook</a></li>
<li class="nav-item"><a class="link link-primary fw-normal py-2 px-3" href="#">Google</a></li>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.querySelector('form[action="{{ route('register') }}"]');
        var btn = document.getElementById('register-submit-btn');
        var btnText = document.getElementById('register-btn-text');
        var btnSpinner = document.getElementById('register-btn-spinner');
        
        if(form && btn && btnText && btnSpinner) {
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btnSpinner.style.display = 'inline-block';
                btnText.textContent = 'Registering...';
            });
        }
    });
</script>
@endpush
