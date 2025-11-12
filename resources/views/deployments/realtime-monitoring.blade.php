@extends('layouts.deployment')

@section('title', 'Real-time Deployment Monitoring')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Real-time Deployment Monitoring</h3>
                <div class="nk-block-des text-soft">
                    <p>Monitor deployments as they happen</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <a href="{{ route('monitoring') }}" class="btn btn-light">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Back to Monitoring</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card card-bordered mb-4">
        <div class="card-inner">
            <div class="row g-gs">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Select Deployment</label>
                        <div class="form-control-wrap">
                            <select id="deploymentSelect" class="form-select">
                                <option value="">Select a deployment to monitor</option>
                                @foreach($recentDeployments as $deployment)
                                    <option value="{{ $deployment->id }}">
                                        #{{ $deployment->id }} - {{ $deployment->project->name }} 
                                        ({{ $deployment->started_at?->format('M d, Y H:i') ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-control-wrap">
                            <button id="startMonitoringBtn" class="btn btn-primary" disabled>
                                <em class="icon ni ni-play"></em>
                                <span>Start Monitoring</span>
                            </button>
                            <button id="stopMonitoringBtn" class="btn btn-danger d-none">
                                <em class="icon ni ni-stop"></em>
                                <span>Stop Monitoring</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="monitoringPanel" class="card card-bordered d-none">
        <div class="card-inner">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Deployment Logs</h5>
                <div>
                    <span id="deploymentStatus" class="badge bg-warning">Pending</span>
                </div>
            </div>
            
            <div class="bg-light p-3 rounded mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Project:</strong> <span id="projectName"></span></p>
                        <p><strong>User:</strong> <span id="userName"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Started:</strong> <span id="startedAt"></span></p>
                        <p><strong>Completed:</strong> <span id="completedAt">-</span></p>
                    </div>
                </div>
            </div>
            
            <div id="logsContainer" class="bg-dark p-3 rounded" style="max-height: 400px; overflow-y: auto;">
                <div id="noLogsMessage" class="text-muted text-center">
                    No logs available yet. Waiting for deployment to start...
                </div>
                <div id="logsList"></div>
            </div>
        </div>
    </div>
</div>

<script>
let monitoringInterval = null;
let lastLogCount = 0;

document.getElementById('deploymentSelect').addEventListener('change', function() {
    const startBtn = document.getElementById('startMonitoringBtn');
    startBtn.disabled = !this.value;
});

document.getElementById('startMonitoringBtn').addEventListener('click', function() {
    const deploymentId = document.getElementById('deploymentSelect').value;
    if (deploymentId) {
        startMonitoring(deploymentId);
    }
});

document.getElementById('stopMonitoringBtn').addEventListener('click', function() {
    stopMonitoring();
});

function startMonitoring(deploymentId) {
    // Hide the selection panel and show the monitoring panel
    document.getElementById('monitoringPanel').classList.remove('d-none');
    document.getElementById('startMonitoringBtn').classList.add('d-none');
    document.getElementById('stopMonitoringBtn').classList.remove('d-none');
    
    // Clear previous logs
    document.getElementById('logsList').innerHTML = '';
    document.getElementById('noLogsMessage').classList.remove('d-none');
    lastLogCount = 0;
    
    // Start polling for logs
    fetchLogs(deploymentId);
    monitoringInterval = setInterval(() => fetchLogs(deploymentId), 2000); // Poll every 2 seconds
}

function stopMonitoring() {
    if (monitoringInterval) {
        clearInterval(monitoringInterval);
        monitoringInterval = null;
    }
    
    // Reset UI
    document.getElementById('monitoringPanel').classList.add('d-none');
    document.getElementById('startMonitoringBtn').classList.remove('d-none');
    document.getElementById('stopMonitoringBtn').classList.add('d-none');
    document.getElementById('deploymentSelect').value = '';
    document.getElementById('startMonitoringBtn').disabled = true;
}

function fetchLogs(deploymentId) {
    fetch(`/deployments/deployments/${deploymentId}/realtime-logs`)
        .then(response => response.json())
        .then(data => {
            // Update deployment info
            document.getElementById('projectName').textContent = data.deployment.project.name;
            document.getElementById('userName').textContent = data.deployment.user.name;
            document.getElementById('startedAt').textContent = data.deployment.started_at 
                ? new Date(data.deployment.started_at).toLocaleString() 
                : 'N/A';
            document.getElementById('completedAt').textContent = data.deployment.completed_at 
                ? new Date(data.deployment.completed_at).toLocaleString() 
                : '-';
            
            // Update status badge
            const statusBadge = document.getElementById('deploymentStatus');
            statusBadge.className = 'badge';
            statusBadge.classList.add(
                data.deployment.status === 'success' ? 'bg-success' : 
                data.deployment.status === 'failed' ? 'bg-danger' : 'bg-warning'
            );
            statusBadge.textContent = data.deployment.status.charAt(0).toUpperCase() + data.deployment.status.slice(1);
            
            // Update logs
            if (data.logs.length > 0) {
                document.getElementById('noLogsMessage').classList.add('d-none');
                
                // Only update if there are new logs
                if (data.logs.length !== lastLogCount) {
                    const logsList = document.getElementById('logsList');
                    logsList.innerHTML = '';
                    
                    // Add logs in reverse order (newest first)
                    data.logs.forEach(log => {
                        const logElement = document.createElement('div');
                        logElement.className = 'mb-2';
                        
                        const time = new Date(log.created_at).toLocaleTimeString();
                        const levelClass = 
                            log.log_level === 'error' ? 'text-danger' : 
                            log.log_level === 'warning' ? 'text-warning' : 
                            log.log_level === 'info' ? 'text-info' : 'text-secondary';
                        
                        logElement.innerHTML = `
                            <div class="d-flex">
                                <div class="me-2"><small class="text-muted">[${time}]</small></div>
                                <div class="flex-grow-1">
                                    <span class="${levelClass}">${log.log_level.toUpperCase()}:</span> 
                                    <span class="text-white">${log.message}</span>
                                </div>
                            </div>
                        `;
                        
                        logsList.appendChild(logElement);
                    });
                    
                    // Scroll to bottom
                    const container = document.getElementById('logsContainer');
                    container.scrollTop = container.scrollHeight;
                    
                    lastLogCount = data.logs.length;
                }
            }
        })
        .catch(error => {
            console.error('Error fetching logs:', error);
        });
}
</script>
@endsection