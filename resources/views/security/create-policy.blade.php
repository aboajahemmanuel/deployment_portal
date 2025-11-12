@extends('layouts.deployment-admin')

@section('title', 'Create Security Policy | Deployment Manager')

@section('content')
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Create Security Policy</h3>
                <div class="nk-block-des text-soft">
                    <p>Define security scanning rules and thresholds</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <a href="#" class="btn btn-icon btn-trigger toggle-expand me-n1" data-target="pageMenu"><em class="icon ni ni-menu-alt-r"></em></a>
                    <div class="toggle-expand-content" data-content="pageMenu">
                        <ul class="nk-block-tools g-3">
                            <li><a href="{{ route('security.policies') }}" class="btn btn-outline-primary"><em class="icon ni ni-arrow-left"></em><span>Back to Policies</span></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-gs">
        <div class="col-12">
            <div class="card card-bordered">
                <div class="card-inner">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Validation Error!</strong> Please correct the following issues:
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="create-policy-form" action="{{ route('security.policies.store') }}" method="POST">
                        @csrf
                        
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label class="form-label" for="name">Policy Name <span class="text-danger">*</span></label>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="form-label" for="project_id">Project (Optional)</label>
                                    <div class="form-control-wrap">
                                        <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id">
                                            <option value="">All Projects (Global Policy)</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="description">Description</label>
                                    <div class="form-control-wrap">
                                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Active Policy</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="card card-bordered">
                                    <div class="card-inner">
                                        <h6 class="card-title">Vulnerability Thresholds</h6>
                                        <p class="card-text">Set maximum allowed vulnerabilities by severity level. Deployment will be blocked if thresholds are exceeded.</p>
                                        
                                        <div class="row g-4">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label" for="max_critical_vulnerabilities">Critical <span class="text-danger">*</span></label>
                                                    <div class="form-control-wrap">
                                                        <input type="number" class="form-control" id="max_critical_vulnerabilities" name="max_critical_vulnerabilities" value="0" min="0" required>
                                                        <small class="form-text text-muted">Recommended: 0 (Block all)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label" for="max_high_vulnerabilities">High <span class="text-danger">*</span></label>
                                                    <div class="form-control-wrap">
                                                        <input type="number" class="form-control" id="max_high_vulnerabilities" name="max_high_vulnerabilities" value="0" min="0" required>
                                                        <small class="form-text text-muted">Recommended: 0 (Block all)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label" for="max_medium_vulnerabilities">Medium <span class="text-danger">*</span></label>
                                                    <div class="form-control-wrap">
                                                        <input type="number" class="form-control" id="max_medium_vulnerabilities" name="max_medium_vulnerabilities" value="10" min="0" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label" for="max_low_vulnerabilities">Low <span class="text-danger">*</span></label>
                                                    <div class="form-control-wrap">
                                                        <input type="number" class="form-control" id="max_low_vulnerabilities" name="max_low_vulnerabilities" value="50" min="0" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="card card-bordered">
                                    <div class="card-inner">
                                        <h6 class="card-title">Scan Configuration</h6>
                                        
                                        <div class="row g-4">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label class="form-label">Required Scan Types <span class="text-danger">*</span></label>
                                                    <ul class="custom-control-group g-3 align-center flex-wrap">
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="scan-sast" name="required_scan_types[]" value="sast" checked>
                                                                <label class="custom-control-label" for="scan-sast">SAST</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="scan-dependency" name="required_scan_types[]" value="dependency" checked>
                                                                <label class="custom-control-label" for="scan-dependency">Dependency</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="scan-secrets" name="required_scan_types[]" value="secrets" checked>
                                                                <label class="custom-control-label" for="scan-secrets">Secrets</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="scan-infrastructure" name="required_scan_types[]" value="infrastructure">
                                                                <label class="custom-control-label" for="scan-infrastructure">Infrastructure</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="scan-container" name="required_scan_types[]" value="container">
                                                                <label class="custom-control-label" for="scan-container">Container</label>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="scan_timeout_minutes">Scan Timeout (minutes) <span class="text-danger">*</span></label>
                                                    <div class="form-control-wrap">
                                                        <input type="number" class="form-control" id="scan_timeout_minutes" name="scan_timeout_minutes" value="30" min="1" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="max_retry_attempts">Max Retry Attempts <span class="text-danger">*</span></label>
                                                    <div class="form-control-wrap">
                                                        <input type="number" class="form-control" id="max_retry_attempts" name="max_retry_attempts" value="3" min="0" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="card card-bordered">
                                    <div class="card-inner">
                                        <h6 class="card-title">Security Settings</h6>
                                        
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="block_on_secrets" name="block_on_secrets" checked>
                                                        <label class="custom-control-label" for="block_on_secrets">Block Deployment on Secret Detection</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="block_on_license_violations" name="block_on_license_violations">
                                                        <label class="custom-control-label" for="block_on_license_violations">Block on License Violations</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="card card-bordered">
                                    <div class="card-inner">
                                        <h6 class="card-title">Notification Settings</h6>
                                        
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="notify_on_failure" name="notify_on_failure" checked>
                                                        <label class="custom-control-label" for="notify_on_failure">Notify on Scan Failure</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="notify_on_new_vulnerabilities" name="notify_on_new_vulnerabilities" checked>
                                                        <label class="custom-control-label" for="notify_on_new_vulnerabilities">Notify on New Vulnerabilities</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label class="form-label">Notification Channels</label>
                                                    <ul class="custom-control-group g-3 align-center flex-wrap">
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="channel-email" name="notification_channels[]" value="email" checked>
                                                                <label class="custom-control-label" for="channel-email">Email</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="channel-slack" name="notification_channels[]" value="slack">
                                                                <label class="custom-control-label" for="channel-slack">Slack</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="channel-webhook" name="notification_channels[]" value="webhook">
                                                                <label class="custom-control-label" for="channel-webhook">Webhook</label>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Create Security Policy</button>
                                    <a href="{{ route('security.policies') }}" class="btn btn-outline-danger">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Add loading state to submit button on form submission
document.getElementById('create-policy-form').addEventListener('submit', function(e) {
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';
    submitButton.disabled = true;
    
    // Re-enable button after a timeout in case of server error
    setTimeout(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }, 10000); // 10 seconds timeout
});
</script>
@endsection