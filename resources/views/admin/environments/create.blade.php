@extends('layouts.deployment')

@section('title', 'Create Environment')

@section('content')
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <a href="{{ route('admin.environments.index') }}" class="btn btn-light btn-sm">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Back to Environments</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <div class="card-head">
                <h5 class="card-title">Create New Environment</h5>
            </div>
            
            <form action="{{ route('admin.environments.store') }}" method="POST">
                @csrf
                
                <div class="row g-gs">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="name">Environment Name <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" 
                                       placeholder="e.g., Development, Staging, Production" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="slug">Slug <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                       id="slug" name="slug" value="{{ old('slug') }}" 
                                       placeholder="e.g., development, staging, production" required>
                                <div class="form-note">Lowercase letters, numbers, hyphens and underscores only</div>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <div class="form-control-wrap">
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3" 
                                          placeholder="Brief description of this environment">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="server_base_path">Server Base Path <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control @error('server_base_path') is-invalid @enderror" 
                                       id="server_base_path" name="server_base_path" value="{{ old('server_base_path') }}" 
                                       placeholder="C:\xampp\htdocs\dev" required>
                                <div class="form-note">Local server path where projects will be deployed</div>
                                @error('server_base_path')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="server_unc_path">Server UNC Path <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control @error('server_unc_path') is-invalid @enderror" 
                                       id="server_unc_path" name="server_unc_path" value="{{ old('server_unc_path') }}" 
                                       placeholder="\\10.10.15.59\c$\xampp\htdocs\dep_env_dev" required>
                                <div class="form-note">Network path for deployment file creation</div>
                                @error('server_unc_path')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="web_base_url">Web Base URL <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <input type="url" class="form-control @error('web_base_url') is-invalid @enderror" 
                                       id="web_base_url" name="web_base_url" value="{{ old('web_base_url') }}" 
                                       placeholder="http://dev-101-php-01.fmdqgroup.com" required>
                                <div class="form-note">Base URL where applications will be accessible</div>
                                @error('web_base_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="deploy_endpoint_base">Deploy Endpoint Base <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <input type="url" class="form-control @error('deploy_endpoint_base') is-invalid @enderror" 
                                       id="deploy_endpoint_base" name="deploy_endpoint_base" value="{{ old('deploy_endpoint_base') }}" 
                                       placeholder="http://101-php-01.fmdqgroup.com/dep_env_dev" required>
                                <div class="form-note">Base URL for deployment scripts</div>
                                @error('deploy_endpoint_base')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="order">Display Order</label>
                            <div class="form-control-wrap">
                                <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                       id="order" name="order" value="{{ old('order') }}" 
                                       placeholder="1" min="0">
                                <div class="form-note">Order in environment selection (lower numbers first)</div>
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active Environment</label>
                                <div class="form-note">Only active environments are available for deployment</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <em class="icon ni ni-check"></em>
                        <span>Create Environment</span>
                    </button>
                    <a href="{{ route('admin.environments.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    nameInput.addEventListener('input', function() {
        if (!slugInput.dataset.manuallyEdited) {
            const slug = this.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-_]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            slugInput.value = slug;
        }
    });
    
    slugInput.addEventListener('input', function() {
        slugInput.dataset.manuallyEdited = 'true';
    });
});
</script>
@endsection
