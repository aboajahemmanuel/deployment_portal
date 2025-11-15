@props(['project', 'action' => 'deploy'])

<!-- Environment Selection Modal -->
<div class="modal fade" id="environmentModal{{ $action }}" tabindex="-1" aria-labelledby="environmentModalLabel{{ $action }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="environmentModalLabel{{ $action }}">
                    Select Environment for {{ ucfirst($action) }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">Choose the environment where you want to {{ $action }} this project.</p>
                
                @if($project->projectEnvironments->isEmpty())
                    <div class="alert alert-warning">
                        <em class="icon ni ni-alert-circle"></em>
                        No environments configured for this project. Please contact an administrator.
                    </div>
                @else
                    <div class="list-group">
                        @foreach($project->projectEnvironments as $projectEnv)
                            @if($projectEnv->is_active)
                                <button type="button" 
                                        class="list-group-item list-group-item-action environment-option"
                                        data-environment-id="{{ $projectEnv->environment_id }}"
                                        data-environment-name="{{ $projectEnv->environment->name }}"
                                        data-action="{{ $action }}">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ $projectEnv->environment->name }}</h6>
                                            <small class="text-muted">{{ $projectEnv->environment->description }}</small>
                                            <div class="mt-2">
                                                <span class="badge bg-secondary">Branch: {{ $projectEnv->branch }}</span>
                                                <span class="badge bg-info">Order: {{ $projectEnv->environment->order }}</span>
                                            </div>
                                        </div>
                                        <em class="icon ni ni-chevron-right"></em>
                                    </div>
                                </button>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle environment selection
    document.querySelectorAll('.environment-option').forEach(button => {
        button.addEventListener('click', function() {
            const environmentId = this.dataset.environmentId;
            const environmentName = this.dataset.environmentName;
            const action = this.dataset.action;
            
            if (action === 'deploy') {
                triggerDeployment(environmentId, environmentName);
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('environmentModal' + action));
            if (modal) {
                modal.hide();
            }
        });
    });
});

function triggerDeployment(environmentId, environmentName) {
    if (!confirm(`Are you sure you want to deploy to ${environmentName}?`)) {
        return;
    }
    
    const projectId = {{ $project->id }};
    const deployButton = document.getElementById('deployButton');
    const deploySpinner = document.getElementById('deploySpinner');
    const deployText = document.getElementById('deployText');
    
    if (deployButton) {
        deployButton.disabled = true;
    }
    if (deploySpinner) {
        deploySpinner.classList.remove('d-none');
    }
    if (deployText) {
        deployText.textContent = 'Deploying...';
    }
    
    fetch(`/deployments/${projectId}/deploy`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            environment_id: environmentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Deployment to ${environmentName} successful!`);
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert('danger', data.message || `Deployment to ${environmentName} failed!`);
            if (deployButton) {
                deployButton.disabled = false;
            }
            if (deploySpinner) {
                deploySpinner.classList.add('d-none');
            }
            if (deployText) {
                deployText.textContent = 'Deploy';
            }
        }
    })
    .catch(error => {
        console.error('Deployment error:', error);
        showAlert('danger', 'An error occurred during deployment. Please try again.');
        if (deployButton) {
            deployButton.disabled = false;
        }
        if (deploySpinner) {
            deploySpinner.classList.add('d-none');
        }
        if (deployText) {
            deployText.textContent = 'Deploy';
        }
    });
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.querySelector('.nk-block');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }
}
</script>
