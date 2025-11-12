@extends('layouts.auth')

@section('title', 'Forgot Password | Rhymes Author Platform')

@section('page-title', 'Forgot Password')

@section('page-description', 'Forgot your password? No problem. Enter your email address and we\'ll send you a password reset link.')

@section('content')
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="form-group">
        <div class="form-label-group">
            <label class="form-label" for="email">Email</label>
        </div>
        <div class="form-control-wrap">
            <input type="email" name="email" class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" placeholder="Enter your email address" value="{{ old('email') }}" required autofocus>
            @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn btn-lg btn-primary btn-block d-flex align-items-center justify-content-center" id="forgot-submit-btn">
            <span id="forgot-btn-text">Email Password Reset Link</span>
            <span id="forgot-btn-spinner" class="spinner-border spinner-border-sm ms-2" style="display:none;" role="status" aria-hidden="true"></span>
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
        var form = document.querySelector('form[action="{{ route('password.email') }}"]');
        var btn = document.getElementById('forgot-submit-btn');
        var btnText = document.getElementById('forgot-btn-text');
        var btnSpinner = document.getElementById('forgot-btn-spinner');
        
        if(form && btn && btnText && btnSpinner) {
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btnSpinner.style.display = 'inline-block';
                btnText.textContent = 'Sending...';
            });
        }
    });
</script>
@endpush
