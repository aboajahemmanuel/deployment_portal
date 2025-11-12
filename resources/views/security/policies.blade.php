@extends('layouts.deployment-admin')

@section('title', 'Security Policies | Deployment Manager')

@section('content')
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Security Policies</h3>
                <div class="nk-block-des text-soft">
                    <p>Manage security scanning policies and thresholds</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <a href="#" class="btn btn-icon btn-trigger toggle-expand me-n1" data-target="pageMenu"><em class="icon ni ni-menu-alt-r"></em></a>
                    <div class="toggle-expand-content" data-content="pageMenu">
                        <ul class="nk-block-tools g-3">
                            <li><a href="{{ route('security.dashboard') }}" class="btn btn-outline-primary"><em class="icon ni ni-arrow-left"></em><span>Back to Dashboard</span></a></li>
                            <li><a href="{{ route('security.policies.create') }}" class="btn btn-primary"><em class="icon ni ni-plus"></em><span>New Policy</span></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Policies List -->
    <div class="row g-gs">
        <div class="col-12">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">Security Policies</h6>
                        </div>
                    </div>
                </div>
                <div class="card-inner p-0">
                    <div class="nk-tb-list nk-tb-ulist">
                        <div class="nk-tb-head">
                            <div class="nk-tb-col"><span class="sub-text">Policy Name</span></div>
                            <div class="nk-tb-col tb-col-md"><span class="sub-text">Project</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Critical Limit</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">High Limit</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Status</span></div>
                            <div class="nk-tb-col tb-col-sm"><span class="sub-text">Actions</span></div>
                        </div>
                        @forelse($policies as $policy)
                        <div class="nk-tb-item">
                            <div class="nk-tb-col">
                                <div class="user-card">
                                    <div class="user-info">
                                        <span class="tb-lead">{{ $policy->name }}</span>
                                        <span class="fs-12px text-muted">{{ Str::limit($policy->description ?? '', 50) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="tb-amount">{{ $policy->project->name ?? 'All Projects' }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                
                                    {{ $policy->max_critical_vulnerabilities }}
                                                            </div>
                            <div class="nk-tb-col tb-col-lg">
                              
                                    {{ $policy->max_high_vulnerabilities }}
                              
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                               
                                    {{ $policy->is_active ? 'Active' : 'Inactive' }}
                               
                            </div>
                            <div class="nk-tb-col tb-col-sm">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('security.policies.edit', $policy->id) }}" class="btn btn-xs btn-outline-primary" title="Edit Policy">
                                        <em class="icon ni ni-edit"></em>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="viewPolicyDetails({{ $policy->id }})" title="View Details">
                                        <em class="icon ni ni-eye"></em>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-info" onclick="duplicatePolicy({{ $policy->id }})" title="Duplicate Policy">
                                        <em class="icon ni ni-copy"></em>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="deletePolicy({{ $policy->id }})" title="Delete Policy">
                                        <em class="icon ni ni-trash"></em>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="nk-tb-item">
                            <div class="nk-tb-col text-center" colspan="6">
                                <span class="text-muted">No security policies found</span>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Policy Information Cards -->
    <div class="row g-gs mt-4">
        <div class="col-md-6">
            <div class="card card-bordered">
                <div class="card-inner">
                    <h6 class="card-title">Policy Guidelines</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Critical Vulnerabilities
                            <span class="badge bg-danger rounded-pill">Block Deployment</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            High Vulnerabilities
                            <span class="badge bg-warning rounded-pill">Review Required</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Medium/Low Vulnerabilities
                            <span class="badge bg-info rounded-pill">Monitor</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-bordered">
                <div class="card-inner">
                    <h6 class="card-title">Scan Types</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>SAST:</strong> Static Application Security Testing
                        </li>
                        <li class="list-group-item">
                            <strong>Dependency:</strong> Third-party vulnerability scanning
                        </li>
                        <li class="list-group-item">
                            <strong>Secrets:</strong> Hardcoded credentials detection
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!-- Policy Details Modal -->
<div class="modal fade" id="policyDetailsModal" tabindex="-1" aria-labelledby="policyDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="policyDetailsModalLabel">Security Policy Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="policyDetailsModalBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Initialize Bootstrap dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    console.log('Security policies page loaded, dropdowns initialized');
});

// View policy details
function viewPolicyDetails(policyId) {
    const modal = new bootstrap.Modal(document.getElementById('policyDetailsModal'));
    const modalBody = document.getElementById('policyDetailsModalBody');
    
    // Show loading spinner
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch policy details
    fetch(`/security/policies/${policyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const policy = data.data;
                modalBody.innerHTML = `
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="alert alert-${policy.is_active ? 'success' : 'secondary'}" role="alert">
                                <strong>${policy.is_active ? 'ACTIVE' : 'INACTIVE'}</strong> Security Policy
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Name:</strong><br>
                            ${policy.name}
                        </div>
                        <div class="col-md-6">
                            <strong>Project:</strong><br>
                            ${policy.project}
                        </div>
                        <div class="col-12">
                            <strong>Description:</strong><br>
                            ${policy.description || 'No description provided'}
                        </div>
                        <div class="col-12">
                            <h6>Vulnerability Thresholds:</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card card-bordered">
                                        <div class="card-inner text-center">
                                            <div class="amount text-danger">${policy.max_critical_vulnerabilities}</div>
                                            <div class="text-muted">Critical</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-bordered">
                                        <div class="card-inner text-center">
                                            <div class="amount text-warning">${policy.max_high_vulnerabilities}</div>
                                            <div class="text-muted">High</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-bordered">
                                        <div class="card-inner text-center">
                                            <div class="amount text-info">${policy.max_medium_vulnerabilities}</div>
                                            <div class="text-muted">Medium</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-bordered">
                                        <div class="card-inner text-center">
                                            <div class="amount text-secondary">${policy.max_low_vulnerabilities}</div>
                                            <div class="text-muted">Low</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <strong>Required Scan Types:</strong><br>
                            ${policy.required_scan_types.map(type => `<span class="badge badge-outline-primary me-1">${type.toUpperCase()}</span>`).join('')}
                        </div>
                        <div class="col-md-6">
                            <strong>Created:</strong><br>
                            ${policy.created_at}
                        </div>
                        <div class="col-md-6">
                            <strong>Last Updated:</strong><br>
                            ${policy.updated_at}
                        </div>
                    </div>
                `;
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load policy details.</div>';
            }
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading policy details.</div>';
            console.error('Error:', error);
        });
}

// Duplicate policy
function duplicatePolicy(policyId) {
    if (!confirm('Are you sure you want to duplicate this security policy?')) {
        return;
    }
    
    fetch(`/security/policies/${policyId}/duplicate`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                location.reload();
            }
        } else {
            alert('Failed to duplicate policy: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error duplicating policy: ' + error.message);
        console.error('Error:', error);
    });
}

// Delete policy
function deletePolicy(policyId) {
    if (!confirm('Are you sure you want to delete this security policy? This action cannot be undone.')) {
        return;
    }
    
    fetch(`/security/policies/${policyId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Failed to delete policy: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error deleting policy: ' + error.message);
        console.error('Error:', error);
    });
}
</script>
@endpush
