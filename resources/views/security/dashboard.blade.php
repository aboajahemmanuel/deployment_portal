@extends('layouts.deployment-admin')

@section('title', 'Security Dashboard | Deployment Manager')

@section('content')
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Security Dashboard</h3>
                <div class="nk-block-des text-soft">
                    <p>Security vulnerability monitoring and policy compliance</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <a href="#" class="btn btn-icon btn-trigger toggle-expand me-n1" data-target="pageMenu"><em class="icon ni ni-menu-alt-r"></em></a>
                    <div class="toggle-expand-content" data-content="pageMenu">
                        <ul class="nk-block-tools g-3">
                            <li><a href="{{ route('security.policies') }}" class="btn btn-outline-primary"><em class="icon ni ni-shield-check"></em><span>Security Policies</span></a></li>
                            <li><a href="#" class="btn btn-outline-info" onclick="triggerManualScan()"><em class="icon ni ni-scan"></em><span>Run Manual Scan</span></a></li>
                            @can('admin')
                            <li><a href="{{ route('security.policies.create') }}" class="btn btn-primary"><em class="icon ni ni-plus"></em><span>New Policy</span></a></li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Statistics -->
    <div class="row g-gs">
        <div class="col-xxl-3 col-sm-6">
            <div class="card">
                <div class="nk-ecwg nk-ecwg6">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">Total Vulnerabilities</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">{{ $vulnerabilityStats['total'] }}</div>
                                <div class="nk-ecwg6-ck">
                                    <canvas class="ecommerce-line-chart-s3" id="totalVulns"></canvas>
                                </div>
                            </div>
                            <div class="info">
                                <span class="change up text-danger">
                                    <em class="icon ni ni-alert-circle"></em>{{ $vulnerabilityStats['open'] }} Open
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="card">
                <div class="nk-ecwg nk-ecwg6">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">Critical Issues</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount text-danger">{{ $vulnerabilityStats['critical'] }}</div>
                                <div class="nk-ecwg6-ck">
                                    <canvas class="ecommerce-line-chart-s3" id="criticalVulns"></canvas>
                                </div>
                            </div>
                            <div class="info">
                                <span class="change up text-warning">
                                    <em class="icon ni ni-arrow-long-up"></em>{{ $vulnerabilityStats['high'] }} High
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="card">
                <div class="nk-ecwg nk-ecwg6">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">Policy Compliance</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">{{ $policyCompliance['compliance_rate'] }}%</div>
                                <div class="nk-ecwg6-ck">
                                    <canvas class="ecommerce-line-chart-s3" id="compliance"></canvas>
                                </div>
                            </div>
                            <div class="info">
                                <span class="change up text-success">
                                    <em class="icon ni ni-check-circle"></em>{{ $policyCompliance['compliant_projects'] }}/{{ $policyCompliance['total_policies'] }} Projects
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="card">
                <div class="nk-ecwg nk-ecwg6">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">Fixed Issues</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount text-success">{{ $vulnerabilityStats['fixed'] }}</div>
                                <div class="nk-ecwg6-ck">
                                    <canvas class="ecommerce-line-chart-s3" id="fixedVulns"></canvas>
                                </div>
                            </div>
                            <div class="info">
                                <span class="change up text-info">
                                    <em class="icon ni ni-check"></em>{{ $vulnerabilityStats['acknowledged'] }} Acknowledged
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Security Scan Results -->
    <div class="row g-gs">
        <div class="col-12">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">Recent Security Scan Results</h6>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('deployments.index') }}" class="link">View All Deployments</a>
                        </div>
                    </div>
                </div>
                <div class="card-inner p-0">
                    <div class="nk-tb-list nk-tb-ulist">
                        <div class="nk-tb-head">
                            <div class="nk-tb-col"><span class="sub-text">Project</span></div>
                            <div class="nk-tb-col tb-col-md"><span class="sub-text">Scan Type</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Severity</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Status</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Date</span></div>
                            <div class="nk-tb-col tb-col-sm"><span class="sub-text">Action</span></div>
                        </div>
                        @forelse($recentScans as $scan)
                        <div class="nk-tb-item">
                            <div class="nk-tb-col">
                                <div class="user-card">
                                    <div class="user-info">
                                        <span class="tb-lead">{{ $scan->deployment->project->name ?? 'Unknown Project' }}</span>
                                        <span class="fs-12px text-muted">{{ Str::limit($scan->title, 40) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="">{{ strtoupper($scan->scan_type) }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                <span class="badge badge-sm badge-dot has-bg bg-{{ $scan->severity_color }} d-none d-sm-inline-flex">
                                    {{ ucfirst($scan->severity) }}
                                </span>
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                                                    {{ ucfirst($scan->status) }}
                                
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                <span class="tb-date">{{ $scan->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-sm">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#vulnerabilityModal" onclick="loadVulnerabilityDetails({{ $scan->id }})">
                                    View Details
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="nk-tb-item">
                            <div class="nk-tb-col text-center" colspan="6">
                                <span class="text-muted">No security scan results found</span>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!-- Vulnerability Details Modal -->
<div class="modal fade" id="vulnerabilityModal" tabindex="-1" aria-labelledby="vulnerabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vulnerabilityModalLabel">Vulnerability Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="vulnerabilityModalBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="acknowledgeBtn" style="display:none;">Acknowledge</button>
                <button type="button" class="btn btn-info" id="falsePositiveBtn" style="display:none;">Mark False Positive</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Manual Scan Modal -->
<div class="modal fade" id="manualScanModal" tabindex="-1" aria-labelledby="manualScanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manualScanModalLabel">Run Manual Security Scan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Select a deployment to run a security scan:</p>
                <select class="form-select" id="deploymentSelect">
                    <option value="">Select a deployment...</option>
                    @foreach(\App\Models\Deployment::with('project')->latest()->limit(10)->get() as $deployment)
                    <option value="{{ $deployment->id }}">{{ $deployment->project->name }} - {{ $deployment->created_at->format('M d, Y H:i') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="runScanBtn">Run Scan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Security dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Security dashboard loaded');
    
    // Initialize mini charts
    initializeSecurityCharts();
});

// Initialize security dashboard mini charts
function initializeSecurityCharts() {
    // Total Vulnerabilities Chart
    const totalVulnsCtx = document.getElementById('totalVulns');
    if (totalVulnsCtx) {
        new Chart(totalVulnsCtx, {
            type: 'line',
            data: {
                labels: ['', '', '', '', '', '', ''],
                datasets: [{
                    data: [12, 19, 15, 25, 22, 30, {{ $vulnerabilityStats['total'] }}],
                    borderColor: '#e85347',
                    backgroundColor: 'rgba(232, 83, 71, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                elements: {
                    point: { radius: 0 }
                }
            }
        });
    }

    // Critical Issues Chart
    const criticalVulnsCtx = document.getElementById('criticalVulns');
    if (criticalVulnsCtx) {
        new Chart(criticalVulnsCtx, {
            type: 'line',
            data: {
                labels: ['', '', '', '', '', '', ''],
                datasets: [{
                    data: [5, 8, 6, 12, 9, 15, {{ $vulnerabilityStats['critical'] }}],
                    borderColor: '#e85347',
                    backgroundColor: 'rgba(232, 83, 71, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                elements: {
                    point: { radius: 0 }
                }
            }
        });
    }

    // Policy Compliance Chart
    const complianceCtx = document.getElementById('compliance');
    if (complianceCtx) {
        new Chart(complianceCtx, {
            type: 'line',
            data: {
                labels: ['', '', '', '', '', '', ''],
                datasets: [{
                    data: [65, 70, 68, 75, 80, 85, {{ $policyCompliance['compliance_rate'] }}],
                    borderColor: '#1ee0ac',
                    backgroundColor: 'rgba(30, 224, 172, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                elements: {
                    point: { radius: 0 }
                }
            }
        });
    }

    // Fixed Issues Chart
    const fixedVulnsCtx = document.getElementById('fixedVulns');
    if (fixedVulnsCtx) {
        new Chart(fixedVulnsCtx, {
            type: 'line',
            data: {
                labels: ['', '', '', '', '', '', ''],
                datasets: [{
                    data: [8, 12, 10, 18, 15, 22, {{ $vulnerabilityStats['fixed'] }}],
                    borderColor: '#1ee0ac',
                    backgroundColor: 'rgba(30, 224, 172, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                elements: {
                    point: { radius: 0 }
                }
            }
        });
    }
}

// Load vulnerability details when modal opens
function loadVulnerabilityDetails(resultId) {
    const modalBody = document.getElementById('vulnerabilityModalBody');
    
    // Show loading spinner
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    // Fetch vulnerability details
    fetch(`/security/vulnerability/${resultId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const vuln = data.data;
                modalBody.innerHTML = `
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="alert alert-${getSeverityColor(vuln.severity)}" role="alert">
                                <strong>${vuln.severity.toUpperCase()}</strong> - ${vuln.scan_type.toUpperCase()} Scan
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Title:</strong><br>
                            ${vuln.title}
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge badge-${getStatusColor(vuln.status)}">${vuln.status.toUpperCase()}</span>
                        </div>
                        <div class="col-12">
                            <strong>Description:</strong><br>
                            ${vuln.description}
                        </div>
                        ${vuln.file_path ? `
                        <div class="col-md-8">
                            <strong>File:</strong><br>
                            <code>${vuln.file_path}</code>
                        </div>
                        ` : ''}
                        ${vuln.line_number ? `
                        <div class="col-md-4">
                            <strong>Line:</strong><br>
                            ${vuln.line_number}
                        </div>
                        ` : ''}
                        ${vuln.cve_id ? `
                        <div class="col-md-6">
                            <strong>CVE ID:</strong><br>
                            ${vuln.cve_id}
                        </div>
                        ` : ''}
                        ${vuln.vulnerability_id ? `
                        <div class="col-md-6">
                            <strong>Vulnerability ID:</strong><br>
                            ${vuln.vulnerability_id}
                        </div>
                        ` : ''}
                        <div class="col-12">
                            <strong>Project:</strong> ${vuln.deployment.project_name}<br>
                            <strong>Deployment:</strong> ${vuln.deployment.created_at}
                        </div>
                        ${vuln.acknowledged_by ? `
                        <div class="col-12">
                            <div class="alert alert-info">
                                <strong>Acknowledged by:</strong> ${vuln.acknowledged_by} on ${vuln.acknowledged_at}<br>
                                <strong>Reason:</strong> ${vuln.acknowledgment_reason}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                // Show action buttons if vulnerability is open
                if (vuln.status === 'open') {
                    document.getElementById('acknowledgeBtn').style.display = 'inline-block';
                    document.getElementById('falsePositiveBtn').style.display = 'inline-block';
                    
                    document.getElementById('acknowledgeBtn').onclick = () => acknowledgeVulnerability(vuln.id);
                    document.getElementById('falsePositiveBtn').onclick = () => markFalsePositive(vuln.id);
                } else {
                    document.getElementById('acknowledgeBtn').style.display = 'none';
                    document.getElementById('falsePositiveBtn').style.display = 'none';
                }
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load vulnerability details.</div>';
            }
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading vulnerability details.</div>';
            console.error('Error:', error);
        });
}

// Trigger manual scan
function triggerManualScan() {
    const modal = new bootstrap.Modal(document.getElementById('manualScanModal'));
    modal.show();
    
    document.getElementById('runScanBtn').onclick = function() {
        const deploymentId = document.getElementById('deploymentSelect').value;
        if (!deploymentId) {
            alert('Please select a deployment');
            return;
        }
        
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Scanning...';
        btn.disabled = true;
        
        fetch(`{{ url('/security/deployment') }}/${deploymentId}/scan`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Security scan completed successfully!');
                modal.hide();
                location.reload(); // Refresh to show new results
            } else {
                alert('Scan failed: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error running scan: ' + error.message);
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    };
}

// Helper functions
function getSeverityColor(severity) {
    switch(severity) {
        case 'critical': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'info';
        case 'low': return 'secondary';
        default: return 'light';
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'open': return 'danger';
        case 'acknowledged': return 'warning';
        case 'fixed': return 'success';
        case 'false_positive': return 'info';
        default: return 'secondary';
    }
}

function acknowledgeVulnerability(resultId) {
    const reason = prompt('Please provide a reason for acknowledging this vulnerability:');
    if (!reason) return;
    
    fetch(`/security/vulnerability/${resultId}/acknowledge`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Vulnerability acknowledged successfully');
            location.reload();
        } else {
            alert('Failed to acknowledge vulnerability');
        }
    });
}

function markFalsePositive(resultId) {
    const reason = prompt('Please provide a reason for marking this as false positive:');
    if (!reason) return;
    
    fetch(`/security/vulnerability/${resultId}/false-positive`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Vulnerability marked as false positive');
            location.reload();
        } else {
            alert('Failed to mark as false positive');
        }
    });
}
</script>
@endpush
