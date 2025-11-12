<section>
    <form method="post" action="" class="form-validate">
        @csrf
        @method('put')

        <div class="row g-3">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label" for="update_password_current_password">Current Password</label>
                    <div class="form-control-wrap">
                        <input type="password" class="form-control @error('current_password', 'updatePassword') error @enderror" 
                               id="update_password_current_password" name="current_password" autocomplete="current-password">
                        @error('current_password', 'updatePassword')
                            <span class="invalid">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label" for="update_password_password">New Password</label>
                    <div class="form-control-wrap">
                        <input type="password" class="form-control @error('password', 'updatePassword') error @enderror" 
                               id="update_password_password" name="password" autocomplete="new-password">
                        @error('password', 'updatePassword')
                            <span class="invalid">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label" for="update_password_password_confirmation">Confirm Password</label>
                    <div class="form-control-wrap">
                        <input type="password" class="form-control @error('password_confirmation', 'updatePassword') error @enderror" 
                               id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password">
                        @error('password_confirmation', 'updatePassword')
                            <span class="invalid">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <span>Save</span>
                    </button>

                    @if (session('status') === 'password-updated')
                        <span class="text-success ms-2">
                            Saved.
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </form>
</section>