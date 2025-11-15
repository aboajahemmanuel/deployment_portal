@extends('layouts.deployment')

@section('title', 'Edit Project')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Edit Project</h3>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <form action="{{ route('deployments.update', $project) }}" method="POST">
                @csrf
                @method('PUT')
                
                @if($errors->any())
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mt-2 mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="row g-gs">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="name">Project Name</label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $project->name) }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="current_branch">Current Branch</label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control" id="current_branch" name="current_branch" value="{{ old('current_branch', $project->current_branch) }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="repository_url">Repository URL</label>
                            <div class="form-control-wrap">
                                <input type="url" class="form-control" id="repository_url" name="repository_url" value="{{ old('repository_url', $project->repository_url) }}" required>
                                <div class="form-text">The Git repository URL (HTTPS or SSH)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><em class="icon ni ni-info"></em> Multi-Environment Project</h6>
                            <p class="mb-0">This project is configured for multiple environments. Environment-specific settings (deploy endpoints, paths, URLs) are managed through the Environment Management system.</p>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="access_token">Access Token</label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control" id="access_token" name="access_token" value="{{ old('access_token', $project->access_token) }}" required>
                                <div class="form-text">Token for authenticating with the deploy endpoint</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <div class="form-control-wrap">
                                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $project->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="is_active" id="is_active" value="1" {{ old('is_active', $project->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('deployments.index') }}" class="btn btn-light">
                                <em class="icon ni ni-arrow-left"></em>
                                <span>Back</span>
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <em class="icon ni ni-save"></em>
                                <span>Update Project</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection