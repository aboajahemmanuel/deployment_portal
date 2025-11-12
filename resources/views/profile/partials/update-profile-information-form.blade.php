<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="form-validate">
        @csrf
        @method('patch')

        <div class="row g-3">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label" for="name">Name</label>
                    <div class="form-control-wrap">
                        <input type="text" class="form-control @error('name') error @enderror" 
                               id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus>
                        @error('name')
                            <span class="invalid">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <div class="form-control-wrap">
                        <input type="email" class="form-control @error('email') error @enderror" 
                               id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <span class="invalid">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="form-note mt-2">
                            <p class="text-soft">
                                Your email address is unverified.
                                <button form="send-verification" class="btn btn-link p-0">
                                    Click here to re-send the verification email.
                                </button>
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <p class="text-success mt-2">
                                    A new verification link has been sent to your email address.
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <span>Save</span>
                    </button>

                    @if (session('status') === 'profile-updated')
                        <span class="text-success ms-2">
                            Saved.
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </form>
</section>