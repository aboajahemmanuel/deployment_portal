@extends('layouts.deployment')

@section('title', 'Deployment Monitoring')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Deployment Monitoring</h3>
                <div class="nk-block-des text-soft">
                    <p>Monitor all deployments across projects</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-bordered mb-4">
        <div class="card-inner">
            <div class="row g-gs mb-3">
                <div class="col-xxl-3 col-sm-6">
                    <div class="card">
                        <div class="nk-ecwg nk-ecwg6">
                            <div class="card-inner">
                                <div class="nk-ecwg6-title">
                                    <h6 class="title">Total Deployments</h6>
                                </div>
                                <div class="nk-ecwg6-group g-3">
                                    <div class="nk-ecwg6-content">
                                        <div class="h3 mb-0">{{ $totalDeployments }}</div>
                                    </div>
                                    <div class="nk-ecwg6-chart">
                                        <div class="icon icon-circle icon-lg bg-primary-dim">
                                            <em class="icon ni ni-send"></em>
                                        </div>
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
                                <div class="nk-ecwg6-title">
                                    <h6 class="title">Successful</h6>
                                </div>
                                <div class="nk-ecwg6-group g-3">
                                    <div class="nk-ecwg6-content">
                                        <div class="h3 mb-0 text-success">{{ $successfulDeployments }}</div>
                                    </div>
                                    <div class="nk-ecwg6-chart">
                                        <div class="icon icon-circle icon-lg bg-success-dim">
                                            <em class="icon ni ni-check-circle"></em>
                                        </div>
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
                                <div class="nk-ecwg6-title">
                                    <h6 class="title">Failed</h6>
                                </div>
                                <div class="nk-ecwg6-group g-3">
                                    <div class="nk-ecwg6-content">
                                        <div class="h3 mb-0 text-danger">{{ $failedDeployments }}</div>
                                    </div>
                                    <div class="nk-ecwg6-chart">
                                        <div class="icon icon-circle icon-lg bg-danger-dim">
                                            <em class="icon ni ni-cross-circle"></em>
                                        </div>
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
                                <div class="nk-ecwg6-title">
                                    <h6 class="title">Pending</h6>
                                </div>
                                <div class="nk-ecwg6-group g-3">
                                    <div class="nk-ecwg6-content">
                                        <div class="h3 mb-0 text-warning">{{ $pendingDeployments }}</div>
                                    </div>
                                    <div class="nk-ecwg6-chart">
                                        <div class="icon icon-circle icon-lg bg-warning-dim">
                                            <em class="icon ni ni-loader"></em>
                                        </div>
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
                <h5 class="card-title mb-0">Recent Deployments</h5>
            </div>
            
            @if($recentDeployments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-lg">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Completed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentDeployments as $deployment)
                                <tr>
                                    <td>
                                        <a href="{{ route('deployments.show', $deployment->project) }}">
                                            {{ $deployment->project->name }}
                                        </a>
                                    </td>
                                    <td>{{ $deployment->user->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $deployment->status === 'success' ? 'success' : 
                                            ($deployment->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($deployment->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $deployment->started_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                    <td>{{ $deployment->completed_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('deployments.detailed-logs', [$deployment->project, $deployment]) }}" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <em class="icon ni ni-eye"></em>
                                            <span>View Logs</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    {{ $recentDeployments->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <em class="icon icon-lg ni ni-send text-muted mb-3"></em>
                    <h5>No deployments found</h5>
                    <p class="text-muted">There are no deployments to display.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection