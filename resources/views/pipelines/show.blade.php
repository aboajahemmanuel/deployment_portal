@extends('layouts.app')

@section('content')
<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Pipeline Visualization</h3>
                            <div class="nk-block-des text-soft">
                                <p>{{ $deployment->project->name }} - Deployment #{{ $deployment->id }}</p>
                            </div>
                        </div>
                        <div class="nk-block-head-content">
                            <div class="toggle-wrap nk-block-tools-toggle">
                                <a href="#" class="btn btn-icon btn-trigger toggle-expand me-n1" data-target="pageMenu"><em class="icon ni ni-menu-alt-r"></em></a>
                                <div class="toggle-expand-content" data-content="pageMenu">
                                    <ul class="nk-block-tools g-3">
                                        <li><a href="{{ route('deployments.show', $deployment->project) }}" class="btn btn-white btn-outline-light"><em class="icon ni ni-arrow-left"></em><span>Back to Project</span></a></li>
                                        @can('deploy', $deployment->project)
                                        <li class="nk-block-tools-opt">
                                            <div class="dropdown">
                                                <a href="#" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <em class="icon ni ni-play"></em><span>Pipeline Actions</span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <ul class="link-list-opt no-bdr">
                                                        <li><a href="#" onclick="simulatePipeline()"><em class="icon ni ni-play-circle"></em><span>Start Simulation</span></a></li>
                                                        <li><a href="#" onclick="advancePipeline()"><em class="icon ni ni-forward"></em><span>Advance Stage</span></a></li>
                                                        <li><a href="#" onclick="failPipeline()"><em class="icon ni ni-cross-circle"></em><span>Fail Current Stage</span></a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deployment Overview -->
                <div class="row g-gs">
                    <div class="col-12">
                        <div class="card card-bordered">
                            <div class="card-inner">
                                <div class="row g-4 align-items-center">
                                    <div class="col-lg-8">
                                        <div class="media-group">
                                            <div class="media media-md media-middle media-circle text-bg-{{ $deployment->status === 'success' ? 'success' : ($deployment->status === 'failed' ? 'danger' : 'warning') }}">
                                                <em class="icon ni ni-{{ $deployment->status === 'success' ? 'check' : ($deployment->status === 'failed' ? 'cross' : 'clock') }}"></em>
                                            </div>
                                            <div class="media-text">
                                                <h6 class="title">{{ $deployment->project->name }}</h6>
                                                <span class="text smaller">
                                                    Deployment started {{ $deployment->started_at->format('M d, Y H:i') }} by {{ $deployment->user->name }}
                                                </span>
                                                @if($deployment->completed_at)
                                                <span class="d-block text smaller text-soft">
                                                    Completed {{ $deployment->completed_at->format('M d, Y H:i') }}
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="text-end">
                                            <div class="badge badge-lg badge-dot has-bg bg-{{ $deployment->status === 'success' ? 'success' : ($deployment->status === 'failed' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($deployment->status) }}
                                            </div>
                                            @if($deployment->hasPipelineStages())
                                            <div class="mt-2">
                                                <div class="progress progress-md">
                                                    <div class="progress-bar bg-{{ $deployment->status === 'success' ? 'success' : ($deployment->status === 'failed' ? 'danger' : 'primary') }}" 
                                                         style="width: {{ $deployment->pipeline_progress }}%"></div>
                                                </div>
                                                <span class="fs-12px text-soft mt-1 d-block">{{ $deployment->pipeline_progress }}% Complete</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipeline Visualization -->
                @if($deployment->hasPipelineStages())
                <div class="row g-gs">
                    <div class="col-12">
                        <div class="card card-bordered">
                            <div class="card-inner">
                                <div class="card-title-group">
                                    <div class="card-title">
                                        <h6 class="title">Pipeline Stages</h6>
                                    </div>
                                    <div class="card-tools">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                                            <label class="form-check-label" for="autoRefresh">Auto Refresh</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pipeline Flow -->
                                <div class="pipeline-container mt-4">
                                    <div class="pipeline-flow">
                                        @foreach($deployment->pipelineStages as $index => $stage)
                                        <div class="pipeline-stage stage-{{ $stage->status }}" data-stage-id="{{ $stage->id }}">
                                            <div class="stage-connector">
                                                @if($index > 0)
                                                <div class="connector-line"></div>
                                                @endif
                                            </div>
                                            
                                            <div class="stage-content" onclick="showStageDetails({{ $stage->id }})">
                                                <div class="stage-icon">
                                                    <div class="stage-circle bg-{{ $stage->status_color }}">
                                                        @if($stage->status === 'running')
                                                        <div class="spinner-border spinner-border-sm text-white" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        @else
                                                        <em class="icon ni {{ $stage->status_icon }} text-white"></em>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <div class="stage-info">
                                                    <h6 class="stage-title">{{ $stage->display_name }}</h6>
                                                    <p class="stage-description">{{ $stage->description }}</p>
                                                    
                                                    <div class="stage-meta">
                                                        @if($stage->started_at)
                                                        <span class="badge badge-sm badge-outline-secondary">
                                                            Started: {{ $stage->started_at->format('H:i:s') }}
                                                        </span>
                                                        @endif
                                                        
                                                        @if($stage->completed_at)
                                                        <span class="badge badge-sm badge-outline-secondary">
                                                            Duration: {{ $stage->formatted_duration }}
                                                        </span>
                                                        @endif
                                                        
                                                        @if($stage->status === 'running')
                                                        <span class="badge badge-sm badge-outline-warning">
                                                            <span class="pulse-dot"></span> Running
                                                        </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stage Details Modal -->
                <div class="modal fade" id="stageDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Stage Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="stageDetailsContent">
                                    <div class="text-center py-4">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <!-- No Pipeline Stages -->
                <div class="row g-gs">
                    <div class="col-12">
                        <div class="card card-bordered">
                            <div class="card-inner text-center py-5">
                                <div class="nk-block-image">
                                    <em class="icon icon-lg ni ni-img text-muted"></em>
                                </div>
                                <div class="nk-block-content">
                                    <h6>No Pipeline Visualization Available</h6>
                                    <p class="text-soft">This deployment doesn't have pipeline stages configured. Pipeline visualization will be available for future deployments.</p>
                                    @can('deploy', $deployment->project)
                                    <button class="btn btn-primary" onclick="simulatePipeline()">
                                        <em class="icon ni ni-play"></em>
                                        <span>Create Demo Pipeline</span>
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.pipeline-container {
    overflow-x: auto;
    padding: 20px 0;
}

.pipeline-flow {
    display: flex;
    align-items: flex-start;
    min-width: 800px;
    position: relative;
}

.pipeline-stage {
    flex: 1;
    position: relative;
    margin: 0 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pipeline-stage:hover {
    transform: translateY(-2px);
}

.stage-connector {
    position: relative;
    height: 20px;
    margin-bottom: 10px;
}

.connector-line {
    position: absolute;
    top: 50%;
    left: -20px;
    right: 100%;
    height: 2px;
    background: #e5e9f2;
    transform: translateY(-50%);
}

.stage-pending .connector-line {
    background: #e5e9f2;
}

.stage-running .connector-line,
.stage-success .connector-line {
    background: #1ee0ac;
}

.stage-failed .connector-line {
    background: #e85347;
}

.stage-content {
    background: #fff;
    border: 2px solid #e5e9f2;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
}

.stage-pending .stage-content {
    border-color: #e5e9f2;
    background: #f8f9fa;
}

.stage-running .stage-content {
    border-color: #f4bd0e;
    background: #fffdf2;
    box-shadow: 0 0 20px rgba(244, 189, 14, 0.2);
}

.stage-success .stage-content {
    border-color: #1ee0ac;
    background: #f0fdf9;
}

.stage-failed .stage-content {
    border-color: #e85347;
    background: #fef7f6;
}

.stage-skipped .stage-content {
    border-color: #8094ae;
    background: #f6f8fa;
}

.stage-icon {
    margin-bottom: 15px;
}

.stage-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 20px;
}

.stage-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #364a63;
}

.stage-description {
    font-size: 12px;
    color: #8094ae;
    margin-bottom: 15px;
    line-height: 1.4;
}

.stage-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: center;
}

.pulse-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #f4bd0e;
    animation: pulse 1.5s infinite;
    margin-right: 5px;
}

@keyframes pulse {
    0% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@media (max-width: 768px) {
    .pipeline-flow {
        flex-direction: column;
        min-width: auto;
    }
    
    .pipeline-stage {
        margin: 10px 0;
    }
    
    .connector-line {
        display: none;
    }
}
</style>

<script>
let autoRefreshInterval;
let isAutoRefreshEnabled = true;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize auto-refresh
    const autoRefreshCheckbox = document.getElementById('autoRefresh');
    if (autoRefreshCheckbox) {
        autoRefreshCheckbox.addEventListener('change', function() {
            isAutoRefreshEnabled = this.checked;
            if (isAutoRefreshEnabled) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
        
        startAutoRefresh();
    }
});

function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    autoRefreshInterval = setInterval(function() {
        if (isAutoRefreshEnabled) {
            refreshPipelineStatus();
        }
    }, 3000); // Refresh every 3 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function refreshPipelineStatus() {
    fetch(`/pipelines/{{ $deployment->id }}/status`)
        .then(response => response.json())
        .then(data => {
            updatePipelineUI(data);
        })
        .catch(error => {
            console.error('Error refreshing pipeline status:', error);
        });
}

function updatePipelineUI(data) {
    // Update progress bar
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = data.pipeline_progress + '%';
        progressBar.nextElementSibling.textContent = data.pipeline_progress + '% Complete';
    }
    
    // Update deployment status
    const statusBadge = document.querySelector('.badge-lg');
    if (statusBadge) {
        statusBadge.textContent = data.deployment_status.charAt(0).toUpperCase() + data.deployment_status.slice(1);
        statusBadge.className = `badge badge-lg badge-dot has-bg bg-${getStatusColor(data.deployment_status)}`;
    }
    
    // Update individual stages
    data.stages.forEach(stage => {
        const stageElement = document.querySelector(`[data-stage-id="${stage.id}"]`);
        if (stageElement) {
            // Update stage classes
            stageElement.className = `pipeline-stage stage-${stage.status}`;
            
            // Update stage icon
            const stageCircle = stageElement.querySelector('.stage-circle');
            const stageIcon = stageElement.querySelector('.stage-circle em, .stage-circle .spinner-border');
            
            if (stageCircle) {
                stageCircle.className = `stage-circle bg-${stage.status_color}`;
                
                if (stage.status === 'running') {
                    stageIcon.outerHTML = '<div class="spinner-border spinner-border-sm text-white" role="status"><span class="visually-hidden">Loading...</span></div>';
                } else {
                    stageIcon.outerHTML = `<em class="icon ni ${stage.status_icon} text-white"></em>`;
                }
            }
            
            // Update stage meta information
            const stageMeta = stageElement.querySelector('.stage-meta');
            if (stageMeta) {
                let metaHTML = '';
                
                if (stage.started_at) {
                    metaHTML += `<span class="badge badge-sm badge-outline-secondary">Started: ${stage.started_at}</span>`;
                }
                
                if (stage.duration && stage.duration !== 'N/A') {
                    metaHTML += `<span class="badge badge-sm badge-outline-secondary">Duration: ${stage.duration}</span>`;
                }
                
                if (stage.status === 'running') {
                    metaHTML += '<span class="badge badge-sm badge-outline-warning"><span class="pulse-dot"></span> Running</span>';
                }
                
                stageMeta.innerHTML = metaHTML;
            }
        }
    });
}

function getStatusColor(status) {
    switch (status) {
        case 'success': return 'success';
        case 'failed': return 'danger';
        case 'running': return 'warning';
        default: return 'secondary';
    }
}

function showStageDetails(stageId) {
    const modal = new bootstrap.Modal(document.getElementById('stageDetailsModal'));
    const content = document.getElementById('stageDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`/pipelines/stages/${stageId}/details`)
        .then(response => response.json())
        .then(stage => {
            content.innerHTML = `
                <div class="row g-4">
                    <div class="col-12">
                        <div class="media-group">
                            <div class="media media-lg media-middle media-circle text-bg-${stage.status_color}">
                                <em class="icon ni ${stage.status_icon}"></em>
                            </div>
                            <div class="media-text">
                                <h5>${stage.display_name}</h5>
                                <p class="text-soft">${stage.description || 'No description available'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <div class="badge badge-lg badge-dot has-bg bg-${stage.status_color}">
                                ${stage.status.charAt(0).toUpperCase() + stage.status.slice(1)}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Duration</label>
                            <p class="form-control-plaintext">${stage.duration}</p>
                        </div>
                    </div>
                    
                    ${stage.started_at ? `
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Started At</label>
                            <p class="form-control-plaintext">${stage.started_at}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${stage.completed_at ? `
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Completed At</label>
                            <p class="form-control-plaintext">${stage.completed_at}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${stage.output ? `
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Output</label>
                            <pre class="form-control" style="max-height: 200px; overflow-y: auto; background: #f8f9fa;">${stage.output}</pre>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${stage.error_message ? `
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label text-danger">Error Message</label>
                            <div class="alert alert-danger">${stage.error_message}</div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <h6>Error Loading Stage Details</h6>
                    <p>Unable to load stage details. Please try again.</p>
                </div>
            `;
        });
}

// Pipeline control functions
function simulatePipeline() {
    fetch(`/pipelines/{{ $deployment->id }}/simulate`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            refreshPipelineStatus();
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Error starting pipeline simulation');
    });
}

function advancePipeline() {
    fetch(`/pipelines/{{ $deployment->id }}/advance`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            refreshPipelineStatus();
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Error advancing pipeline');
    });
}

function failPipeline() {
    if (confirm('Are you sure you want to fail the current stage?')) {
        fetch(`/pipelines/{{ $deployment->id }}/fail`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.error(data.message);
                refreshPipelineStatus();
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            toastr.error('Error failing pipeline stage');
        });
    }
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});
</script>
@endsection
