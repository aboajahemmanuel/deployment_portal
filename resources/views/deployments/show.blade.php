@extends('layouts.deployment')

@section('title', 'Project Details')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <a href="{{ route('deployments.index') }}" class="btn btn-light btn-sm">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Back to Projects</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card card-bordered mb-4">
        <div class="card-inner">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h3 class="card-title mb-1">{{ $project->name }}</h3>
                    <span class="badge bg-{{ $project->is_active ? 'success' : 'danger' }}">
                        {{ $project->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    @can('update', $project)
                    <a href="{{ route('deployments.edit', $project) }}" class="btn btn-sm btn-outline-primary">
                        <em class="icon ni ni-edit"></em>
                        <span>Edit</span>
                    </a>
                    @endcan
                    @if(!empty($project->application_url))
                    <a href="{{ $project->application_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-success ms-2">
                        <em class="icon ni ni-external"></em>
                        <span>Open App</span>
                    </a>
                    @endif
                 

                    @can('delete', $project)
                    <form action="{{ route('deployments.destroy', $project) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this project? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <em class="icon ni ni-trash"></em>
                            <span>Delete</span>
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            
            <div class="row g-gs">
                <div class="col-lg-6">
                    <div class="card card-bordered">
                        <div class="card-inner">
                            <h5 class="card-title mb-3">Project Information</h5>
                            <div class="row g-gs">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">Repository URL</label>
                                        <div class="form-control-wrap">
                                            <div class="form-text">{{ $project->repository_url }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">Deploy Endpoint</label>
                                        <div class="form-control-wrap">
                                            <div class="form-text">{{ $project->deploy_endpoint }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">Application URLs by Environment</label>
                                        <div class="form-control-wrap">
                                            @if($project->projectEnvironments->count() > 0)
                                                @foreach($project->projectEnvironments as $projectEnv)
                                                    <div class="mb-2">
                                                        <span class="badge bg-{{ $projectEnv->environment->slug == 'production' ? 'success' : ($projectEnv->environment->slug == 'staging' ? 'warning' : 'info') }} me-2">
                                                            {{ $projectEnv->environment->name }}
                                                        </span>
                                                        <a href="{{ $projectEnv->application_url }}" target="_blank" rel="noopener" class="text-primary">
                                                            {{ $projectEnv->application_url }}
                                                            <em class="icon ni ni-external"></em>
                                                        </a>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="form-text text-muted">No environments configured</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">Current Branch</label>
                                        <div class="form-control-wrap">
                                            <div class="form-text">{{ $project->current_branch }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">Created At</label>
                                        <div class="form-control-wrap">
                                            <div class="form-text">{{ $project->created_at->format('M d, Y H:i') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <div class="form-control-wrap">
                                            <div class="form-text">{{ $project->description ?? 'No description provided.' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="col-lg-6">
                    <div class="card card-bordered">
                        <div class="card-inner">
                            <h5 class="card-title mb-3">Project Information</h5>
                            <div class="row ">
                                 <div class="">
                                @can('deploy', $project)
                                    @if($project->is_active)
                                        <button 
                                            type="button"
                                            class="btn btn-primary btn-lg"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#environmentModaldeploy"
                                            id="deployButton">
                                            <span class="spinner-border spinner-border-sm d-none" id="deploySpinner" role="status" aria-hidden="true"></span>
                                            <em class="icon ni ni-send"></em>
                                            <span id="deployText">Deploy Now</span>
                                        </button>
                                        @if($project->projectEnvironments->where('environment.slug', 'production')->first())
                                        <a href="{{ $project->projectEnvironments->where('environment.slug', 'production')->first()->application_url }}" target="_blank" rel="noopener" class="btn btn-success btn-lg ms-2">
                                            <em class="icon ni ni-external"></em>
                                            <span>Open Production App</span>
                                        </a>
                                        @elseif($project->projectEnvironments->first())
                                        <a href="{{ $project->projectEnvironments->first()->application_url }}" target="_blank" rel="noopener" class="btn btn-success btn-lg ms-2">
                                            <em class="icon ni ni-external"></em>
                                            <span>Open App</span>
                                        </a>
                                        @endif
                                        <a href="{{ route('deployments.commits', $project) }}" class="btn btn-outline-secondary btn-lg">
                                            <em class="icon ni ni-list-index"></em>
                                            <span>View Commit History</span>
                                        </a>
                                    @else
                                        <button 
                                            disabled
                                            class="btn btn-secondary btn-lg">
                                            <em class="icon ni ni-send"></em>
                                            <span>Inactive Project</span>
                                        </button>
                                    @endif
                                @endcan
                                
                                <div class="alert alert-info mt-3">
                                    <h6 class="alert-heading">Deployment Instructions</h6>
                                    <p class="mb-0">Click the "Deploy Now" button to trigger deployment to the remote server. The deployment process will:</p>
                                    <ol class="mb-0 mt-2 small">
                                        <li>Pull the latest code from the repository</li>
                                        <li>Install/update dependencies</li>
                                        <li>Run database migrations</li>
                                        <li>Clear and rebuild caches</li>
                                    </ol>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
                
             
            </div>
        </div>
    </div>
    
    <div class="card card-bordered">
        <div class="card-inner">
            <div class="card-head">
                <h5 class="card-title mb-0">Deployment History</h5>
            </div>
            
            @if($deployments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-lg">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Environment</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deployments as $deployment)
                                <tr>
                                    <td>{{ $deployment->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $deployment->user->name }}</td>
                                    <td>
                                        @if($deployment->environment)
                                            <span class="badge bg-secondary">{{ $deployment->environment->name }}</span>
                                        @else
                                            <span class="badge bg-light text-dark">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $deployment->status === 'success' ? 'success' : 
                                            ($deployment->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($deployment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($deployment->is_rollback)
                                            <span class="badge bg-info">Rollback</span>
                                            @if($deployment->rollbackTarget)
                                                <small class="text-muted">(to #{{ $deployment->rollbackTarget->id }})</small>
                                            @endif
                                        @else
                                            <span class="badge bg-primary">Regular</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex ">
                                            <a href="{{ route('deployments.detailed-logs', [$project, $deployment]) }}" class="btn btn-sm btn-outline-secondary">
                                                <em class="icon ni ni-eye"></em>
                                                <span>View Logs</span>
                                            </a>
                                            
                                            @if($deployment->status === 'success' && !$deployment->is_rollback && $deployment->commit_hash)
                                                @can('deploy', $project)
                                                    <button onclick="rollbackDeployment({{ $project->id }}, {{ $deployment->id }})" class="btn btn-sm btn-outline-warning" title="Rollback to commit {{ substr($deployment->commit_hash, 0, 7) }}">
                                                        <em class="icon ni ni-redo"></em>
                                                        <span>Rollback to {{ substr($deployment->commit_hash, 0, 7) }}</span>
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($deployments->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    <nav aria-label="Deployment pagination">
                        {{ $deployments->onEachSide(1)->links('vendor.pagination.bootstrap-5') }}
                    </nav>
                </div>
                @endif
            @else
                <div class="text-center py-5">
                    <em class="icon icon-lg ni ni-package text-muted mb-3"></em>
                    <h5>No deployments yet</h5>
                    <p class="text-muted">This project has not been deployed yet.</p>
                    @can('deploy', $project)
                        @if($project->is_active)
                            <button 
                                data-bs-toggle="modal" 
                                data-bs-target="#environmentModaldeploy"
                                class="btn btn-primary">
                                <em class="icon ni ni-send"></em>
                                <span>Deploy Now</span>
                            </button>
                        @endif
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Rollback Modal -->
<div class="modal fade" id="rollbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rollback Deployment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rollbackForm">
                    @csrf
                    <input type="hidden" id="rollbackProjectId">
                    <input type="hidden" id="rollbackTargetDeploymentId">
                    
                    <div class="mb-3">
                        <label for="rollbackCommitSelect" class="form-label">Select Commit to Rollback To</label>
                        <select class="form-control" id="rollbackCommitSelect" required>
                            <option value="">Choose a commit...</option>
                            @foreach($deployments as $deployment)
                                @if($deployment->status === 'success' && !$deployment->is_rollback && $deployment->commit_hash)
                                    <option value="{{ $deployment->id }}" data-commit="{{ $deployment->commit_hash }}">
                                        {{ substr($deployment->commit_hash, 0, 7) }} - {{ $deployment->created_at->format('M d, Y H:i') }} by {{ $deployment->user->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Select a previous successful deployment to rollback to</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rollbackReason" class="form-label">Reason for Rollback</label>
                        <textarea class="form-control" id="rollbackReason" rows="3" placeholder="Enter reason for rollback (optional)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmRollback()">
                    <em class="icon ni ni-redo"></em>
                    <span>Rollback</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deployProject(projectId) {
    confirmAction('Are you sure you want to deploy this project?', function() {
        // Show full page loader only after user confirms
        showPageDeploymentLoader();
        
        fetch(`{{ url('/deployments') }}/${projectId}/deploy`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
        })
        .then(async (response) => {
            const raw = await response.text();
            let data = null;
            try { data = JSON.parse(raw); } catch (_) { /* not JSON, fall through */ }
            const looksSuccessful = typeof raw === 'string'
              && (
                raw.includes('DEPLOYMENT_STATUS=success')
                || raw.includes('✅ Deployment finished successfully')
                || raw.includes('Deployment started')
              )
              && !raw.includes('❌ Command failed')
              && raw.toLowerCase().indexOf('fatal error') === -1;
            let success = (data && data.success === true) || looksSuccessful;
            const runIdMatch = raw.match(/\[run_id:([^\]]+)\]/) || raw.match(/Run ID:\s*([0-9_\-]+)/);
            const runId = runIdMatch ? runIdMatch[1] : null;
            // If not successful yet but backend provided remote response_body, re-evaluate success based on it
            if (!success && data && typeof data.response_body === 'string') {
                const rb = data.response_body;
                const rbLower = rb.toLowerCase();
                const looksSuccessfulFromRB = (
                    /deployment_status\s*=\s*success/i.test(rb)
                    || rbLower.includes('✅ deployment finished successfully')
                    || rbLower.includes('deployment finished successfully')
                    || rbLower.includes('deployment started')
                ) && !rbLower.includes('❌ command failed') && rbLower.indexOf('fatal error') === -1;
                if (looksSuccessfulFromRB) {
                    success = true;
                }
            }
            if (success) {
                // Hide full page loader first
                hidePageDeploymentLoader();
                
                // Show enhanced success message for deployment
                Swal.fire({
                    icon: 'success',
                    title: '🚀 Deployment Successful!',
                    html: `
                        <div class="text-start">
                            <p class="mb-2"><strong>Project:</strong> {{ $project->name }}</p>
                            <p class="mb-2"><strong>Status:</strong> <span class="badge bg-success">Completed</span></p>
                            ${runId ? `<p class=\"mb-2\"><strong>Run ID:</strong> ${runId}</p>` : ''}
                            ${`{{ !empty($project->application_url) ? 1 : 0 }}` == '1' ? `<p class=\"mb-2\"><strong>Open App:</strong> <a href=\"{{ $project->application_url }}\" target=\"_blank\" rel=\"noopener\">{{ $project->application_url }}</a></p>` : ''}
                            <p class="mb-0"><strong>Time:</strong> ${new Date().toLocaleString()}</p>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'View Deployment History',
                    confirmButtonColor: '#28a745',
                    showCancelButton: true,
                    cancelButtonText: 'Stay Here',
                    cancelButtonColor: '#6c757d',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'swal-deployment-success'
                    }
                }).then((result) => {
                    // Reload the page regardless of which button is clicked
                    window.location.reload();
                });
            } else {
                // Hide full page loader
                hidePageDeploymentLoader();
                
                // Show enhanced error message for deployment failure
                const logFromJson = data && data.log ? (typeof data.log === 'string' ? data.log : JSON.stringify(data.log)) : null;
                const responseBodyFromJson = (data && data.response_body) ? data.response_body : null;
                const errorDetailRaw = logFromJson || responseBodyFromJson || raw;
                const errorExcerpt = (typeof errorDetailRaw === 'string') ? errorDetailRaw.substring(0, 500) : String(errorDetailRaw).substring(0, 500);
                Swal.fire({
                    icon: 'error',
                    title: '❌ Deployment Failed!',
                    html: `
                        <div class="text-start">
                            <p class="mb-2"><strong>Project:</strong> {{ $project->name }}</p>
                            <p class="mb-2"><strong>Status:</strong> <span class="badge bg-danger">Failed</span></p>
                            <p class="mb-2"><strong>Error:</strong></p>
                            <pre style="white-space:pre-wrap;max-height:240px;overflow:auto;border:1px solid #eee;padding:8px;border-radius:4px;background:#fafafa;">${errorExcerpt}</pre>
                            <p class="text-muted small mb-2">HTTP Status: ${response.status}</p>
                            <p class="mb-0"><strong>Time:</strong> ${new Date().toLocaleString()}</p>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'View Deployment Logs',
                    confirmButtonColor: '#dc3545',
                    showCancelButton: true,
                    cancelButtonText: 'Close',
                    cancelButtonColor: '#6c757d',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'swal-deployment-error'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Reload to show the failed deployment in history
                        window.location.reload();
                    }
                });
            }
        })
        .catch(error => {
            // Hide full page loader
            hidePageDeploymentLoader();
            
            console.error('Error:', error);
            showErrorMessage('An error occurred while deploying the project. Please check the deployment logs for details.');
        });
    }, function() {
        // If user cancels, do nothing (no loader was shown)
        return;
    });
}

function confirmRollback() {
    // Get the selected commit from dropdown
    const commitSelect = document.getElementById('rollbackCommitSelect');
    const selectedDeploymentId = commitSelect.value;
    const selectedCommit = commitSelect.options[commitSelect.selectedIndex].getAttribute('data-commit');
    
    if (!selectedDeploymentId) {
        showErrorMessage('Please select a commit to rollback to.');
        return;
    }
    
    const projectId = document.getElementById('rollbackProjectId').value;
    const reason = document.getElementById('rollbackReason').value;
    
    // Close the modal
    bootstrap.Modal.getInstance(document.getElementById('rollbackModal')).hide();
    
    const commitShort = selectedCommit ? selectedCommit.substring(0, 7) : selectedDeploymentId;
    confirmAction(`Are you sure you want to rollback to commit ${commitShort}? This will revert the application to the state it was in at the time of this deployment.`, function() {
        // Show full page loader only after user confirms
        showPageDeploymentLoader();
        
        fetch(`{{ url('/deployments') }}/${projectId}/rollback/${selectedDeploymentId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide full page loader first
                hidePageDeploymentLoader();
                
                // Show enhanced success message for rollback
                Swal.fire({
                    icon: 'success',
                    title: '🔄 Rollback Successful!',
                    html: `
                        <div class="text-start">
                            <p class="mb-2"><strong>Project:</strong> {{ $project->name }}</p>
                            <p class="mb-2"><strong>Status:</strong> <span class="badge bg-success">Rollback Completed</span></p>
                            <p class="mb-2"><strong>Target Commit:</strong> ${commitShort}</p>
                            <p class="mb-0"><strong>Time:</strong> ${new Date().toLocaleString()}</p>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'View Deployment History',
                    confirmButtonColor: '#28a745',
                    showCancelButton: true,
                    cancelButtonText: 'Stay Here',
                    cancelButtonColor: '#6c757d',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'swal-rollback-success'
                    }
                }).then((result) => {
                    // Reload the page regardless of which button is clicked
                    window.location.reload();
                });
            } else {
                // Hide full page loader
                hidePageDeploymentLoader();
                
                // Show enhanced error message for rollback failure
                Swal.fire({
                    icon: 'error',
                    title: '❌ Rollback Failed!',
                    html: `
                        <div class="text-start">
                            <p class="mb-2"><strong>Project:</strong> {{ $project->name }}</p>
                            <p class="mb-2"><strong>Status:</strong> <span class="badge bg-danger">Rollback Failed</span></p>
                            <p class="mb-2"><strong>Target Commit:</strong> ${commitShort}</p>
                            <p class="mb-2"><strong>Error:</strong> ${data.message}</p>
                            <p class="mb-0"><strong>Time:</strong> ${new Date().toLocaleString()}</p>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'View Deployment Logs',
                    confirmButtonColor: '#dc3545',
                    showCancelButton: true,
                    cancelButtonText: 'Close',
                    cancelButtonColor: '#6c757d',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'swal-rollback-error'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Reload to show the failed rollback in history
                        window.location.reload();
                    }
                });
            }
        })
        .catch(error => {
            // Hide full page loader
            hidePageDeploymentLoader();
            
            console.error('Error:', error);
            showErrorMessage('An error occurred while initiating the rollback. Please check the deployment logs for details.');
        });
    }, function() {
        // If user cancels, do nothing (no loader was shown)
        return;
    });
}

// Preserve existing functions
function rollbackDeployment(projectId, targetDeploymentId) {
    document.getElementById('rollbackProjectId').value = projectId;
    document.getElementById('rollbackTargetDeploymentId').value = targetDeploymentId;
    document.getElementById('rollbackReason').value = '';
    
    // Pre-select the clicked deployment in the dropdown if provided
    if (targetDeploymentId) {
        const commitSelect = document.getElementById('rollbackCommitSelect');
        commitSelect.value = targetDeploymentId;
    }
    
    new bootstrap.Modal(document.getElementById('rollbackModal')).show();
}

function viewLogs(deploymentId) {
    // This would typically open a modal with the logs
    showInfoMessage('Viewing logs for deployment ' + deploymentId);
}
</script>

<!-- Include Environment Selector Component -->
<x-environment-selector :project="$project" action="deploy" />

@endsection