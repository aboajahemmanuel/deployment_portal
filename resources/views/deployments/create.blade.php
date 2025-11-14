@extends('layouts.deployment')

@section('title', 'Add New Project')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Add New Project</h3>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <form action="{{ route('deployments.store') }}" method="POST">
                @csrf
                
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
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="current_branch">Current Branch</label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control" id="current_branch" name="current_branch" value="{{ old('current_branch', 'main') }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="repository_url">Repository URL</label>
                            <div class="form-control-wrap">
                                <input type="url" class="form-control" id="repository_url" name="repository_url" value="{{ old('repository_url') }}" required>
                                <div class="form-text">The Git repository URL (HTTPS or SSH)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="deploy_endpoint">Deploy Endpoint</label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control" id="deploy_endpoint" name="deploy_endpoint" value="{{ old('deploy_endpoint') }}" required>
                                <div class="form-text">The URL to the deploy.php script on the remote server</div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="rollback_endpoint">Rollback Endpoint (Optional)</label>
                            <div class="form-control-wrap">
                                <input type="url" class="form-control" id="rollback_endpoint" name="rollback_endpoint" value="{{ old('rollback_endpoint') }}">
                                <div class="form-text">The URL to the rollback.php script on the remote server (if different from deploy endpoint)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="access_token">Access Token</label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control" id="access_token" name="access_token" value="{{ old('access_token') }}" required>
                                <div class="form-text">Token for authenticating with the deploy endpoint</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <div class="form-control-wrap">
                                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="project_type">Project Type</label>
                            <div class="form-control-wrap">
                                <select class="form-control" id="project_type" name="project_type">
                                    <option value="laravel" {{ old('project_type', 'laravel') == 'laravel' ? 'selected' : '' }}>Laravel</option>
                                    <option value="nodejs" {{ old('project_type') == 'nodejs' ? 'selected' : '' }}>Node.js</option>
                                    <option value="php" {{ old('project_type') == 'php' ? 'selected' : '' }}>PHP</option>
                                    <option value="other" {{ old('project_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-12" id="env-section">
                        <div class="form-group">
                            <label class="form-label" for="env_variables">Environment Variables (.env)</label>
                            <div class="form-control-wrap">
                                <textarea class="form-control" id="env_variables" name="env_variables" rows="8" placeholder="APP_NAME=MyApp&#10;APP_ENV=production&#10;APP_KEY=&#10;APP_DEBUG=false&#10;APP_URL=http://example.com&#10;&#10;DB_CONNECTION=mysql&#10;DB_HOST=127.0.0.1&#10;DB_PORT=3306&#10;DB_DATABASE=mydb&#10;DB_USERNAME=root&#10;DB_PASSWORD=">{{ old('env_variables') }}</textarea>
                                <div class="form-text">For Laravel projects, provide the .env file content. This will be created during initial deployment.</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12" style="display: none;">
                        <div class="alert alert-info">
                            <strong>Server Details</strong> — Provide where to create the deployment file and the Windows project path used by the script. Leave blank to use defaults.
                        </div>
                    </div>

                    <div class="col-md-6" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="server_unc_base">UNC Base Directory (optional)</label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control" id="server_unc_base" name="server_unc_base" >
                                <div class="form-text">Example: \\10.10.15.59\c$\xampp\htdocs\dep_env</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="windows_project_path">Windows Project Path (optional)</label>
                            <div class="form-control-wrap">
                                <input type="text" class="form-control" id="windows_project_path" name="windows_project_path" value="{{ old('windows_project_path') }}" placeholder="C:\\wamp64\\www\\<slug>_deploy">
                                <div class="form-text">If empty, it will default to C:\\wamp64\\www\\[project-name-slug]_deploy</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
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
                                <span>Create Project</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection