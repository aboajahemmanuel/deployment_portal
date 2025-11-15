@extends('layouts.deployment')

@section('title', 'Scheduled Deployment Details')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Scheduled Deployment Details</h3>
                <div class="nk-block-des text-soft">
                    <p>View details of scheduled deployment #{{ $scheduledDeployment->id }}</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <a href="{{ route('scheduled-deployments.index') }}" class="btn btn-light">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Back to List</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <div class="row g-gs">
                <div class="col-lg-6">
                    <div class="nk-block">
                        <div class="nk-block-head">
                            <h6 class="title">Basic Information</h6>
                        </div>
                        <div class="profile-ud-list">
                            <div class="profile-ud-item">
                                <div class="profile-ud wider">
                                    <span class="profile-ud-label">Project</span>
                                    <span class="profile-ud-value">{{ $scheduledDeployment->project->name }}</span>
                                </div>
                            </div>
                            <div class="profile-ud-item">
                                <div class="profile-ud wider">
                                    <span class="profile-ud-label">Environment</span>
                                    <span class="profile-ud-value">
                                        @if($scheduledDeployment->environment)
                                            <span class="badge bg-info">{{ $scheduledDeployment->environment->name }}</span>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="profile-ud-item">
                                <div class="profile-ud wider">
                                    <span class="profile-ud-label">Status</span>
                                    <span class="profile-ud-value">
                                        <span class="badge bg-{{ $scheduledDeployment->status === 'pending' ? 'warning' : ($scheduledDeployment->status === 'completed' ? 'success' : ($scheduledDeployment->status === 'failed' ? 'danger' : 'secondary')) }}">
                                            {{ ucfirst($scheduledDeployment->status) }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="profile-ud-item">
                                <div class="profile-ud wider">
                                    <span class="profile-ud-label">Created By</span>
                                    <span class="profile-ud-value">{{ $scheduledDeployment->user->name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="nk-block">
                        <div class="nk-block-head">
                            <h6 class="title">Schedule Information</h6>
                        </div>
                        <div class="profile-ud-list">
                            <div class="profile-ud-item">
                                <div class="profile-ud wider">
                                    <span class="profile-ud-label">Scheduled At</span>
                                    <span class="profile-ud-value">{{ $scheduledDeployment->scheduled_at->format('M d, Y H:i') }}</span>
                                </div>
                            </div>
                            <div class="profile-ud-item">
                                <div class="profile-ud wider">
                                    <span class="profile-ud-label">Recurring</span>
                                    <span class="profile-ud-value">
                                        @if($scheduledDeployment->is_recurring)
                                            <span class="badge bg-primary">{{ ucfirst($scheduledDeployment->recurrence_pattern) }}</span>
                                        @else
                                            <span class="text-muted">No</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            @if($scheduledDeployment->is_recurring && $scheduledDeployment->next_run_at)
                            <div class="profile-ud-item">
                                <div class="profile-ud wider">
                                    <span class="profile-ud-label">Next Run</span>
                                    <span class="profile-ud-value">{{ $scheduledDeployment->next_run_at->format('M d, Y H:i') }}</span>
                                </div>
                            </div>
                            @endif
                            @if($scheduledDeployment->last_run_at)
                            <div class="profile-ud-item">
                                <div class="profile-ud wider">
                                    <span class="profile-ud-label">Last Run</span>
                                    <span class="profile-ud-value">{{ $scheduledDeployment->last_run_at->format('M d, Y H:i') }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            @if($scheduledDeployment->description)
            <div class="row g-gs mt-4">
                <div class="col-12">
                    <div class="nk-block">
                        <div class="nk-block-head">
                            <h6 class="title">Description</h6>
                        </div>
                        <div class="nk-block-content">
                            <p class="text-soft">{{ $scheduledDeployment->description }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <div class="row g-gs mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <div>
                            @can('update', $scheduledDeployment)
                                @if($scheduledDeployment->status === 'pending')
                                    <a href="{{ route('scheduled-deployments.edit', $scheduledDeployment) }}" class="btn btn-primary">
                                        <em class="icon ni ni-edit"></em>
                                        <span>Edit Schedule</span>
                                    </a>
                                    <button onclick="cancelScheduledDeployment({{ $scheduledDeployment->id }})" class="btn btn-warning">
                                        <em class="icon ni ni-cross"></em>
                                        <span>Cancel</span>
                                    </button>
                                @endif
                            @endcan
                        </div>
                        <div>
                            <small class="text-muted">
                                Created {{ $scheduledDeployment->created_at->format('M d, Y H:i') }}
                                @if($scheduledDeployment->updated_at != $scheduledDeployment->created_at)
                                    • Updated {{ $scheduledDeployment->updated_at->format('M d, Y H:i') }}
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelScheduledDeployment(id) {
    if (confirm('Are you sure you want to cancel this scheduled deployment?')) {
        fetch(`{{ url('/scheduled-deployments') }}/${id}/cancel`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Failed to cancel scheduled deployment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while canceling the scheduled deployment');
        });
    }
}
</script>
@endsection
