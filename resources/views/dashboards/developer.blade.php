@extends('layouts.deployment-admin')

@section('title', 'Developer Dashboard | Deployment Manager')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">My Projects Dashboard</h3>
                <div class="nk-block-des text-soft">
                    <p>Manage your assigned projects and deployments</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <a href="#" class="btn btn-icon btn-trigger toggle-expand me-n1" data-target="pageMenu"><em class="icon ni ni-menu-alt-r"></em></a>
                    <div class="toggle-expand-content" data-content="pageMenu">
                        <ul class="nk-block-tools g-3">
                            <li class="nk-block-tools-opt">
                                <a href="{{ route('deployments.create') }}" class="btn btn-primary">
                                    <em class="icon ni ni-plus"></em>
                                    <span>Add Project</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Developer Statistics Cards -->
    <div class="row g-gs">
        <div class="col-xxl-3 col-sm-6">
            <div class="card">
                <div class="nk-ecwg nk-ecwg6">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">My Projects</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">{{ $stats['my_projects'] }}</div>
                            </div>
                            <div class="info">
                                <span class="change up text-success">
                                    <em class="icon ni ni-package"></em>Total
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
                                <h6 class="title">Success Rate</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">
                                    @php
                                        $total = $stats['deployment_stats']['success'] + $stats['deployment_stats']['failed'];
                                        $rate = $total > 0 ? round(($stats['deployment_stats']['success'] / $total) * 100) : 0;
                                    @endphp
                                    {{ $rate }}%
                                </div>
                            </div>
                            <div class="info">
                                <span class="change up text-{{ $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger') }}">
                                    <em class="icon ni ni-check-circle"></em>{{ $stats['deployment_stats']['success'] }}/{{ $total }}
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
                                <h6 class="title">Deployments</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">{{ $stats['my_deployments'] }}</div>
                            </div>
                            <div class="info">
                                <span class="change up text-warning">
                                    <em class="icon ni ni-send"></em>Total
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
                                <h6 class="title">Scheduled</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">{{ $stats['my_scheduled']->count() }}</div>
                            </div>
                            <div class="info">
                                <span class="change up text-info">
                                    <em class="icon ni ni-calendar"></em>Pending
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Statistics Chart -->
    <div class="row g-gs">
        <div class="col-12">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">Deployment Statistics</h6>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="nk-ecwg">
                                <div class="card-inner text-center">
                                    <div class="nk-ecwg-item">
                                        <div class="data-group">
                                            <div class="amount text-success h4">{{ $stats['deployment_stats']['success'] }}</div>
                                            <div class="nk-ecwg-subtitle">Successful</div>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-success" style="width: {{ $total > 0 ? ($stats['deployment_stats']['success'] / $total) * 100 : 0 }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="nk-ecwg">
                                <div class="card-inner text-center">
                                    <div class="nk-ecwg-item">
                                        <div class="data-group">
                                            <div class="amount text-danger h4">{{ $stats['deployment_stats']['failed'] }}</div>
                                            <div class="nk-ecwg-subtitle">Failed</div>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-danger" style="width: {{ $total > 0 ? ($stats['deployment_stats']['failed'] / $total) * 100 : 0 }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="nk-ecwg">
                                <div class="card-inner text-center">
                                    <div class="nk-ecwg-item">
                                        <div class="data-group">
                                            <div class="amount text-warning h4">{{ $stats['deployment_stats']['running'] }}</div>
                                            <div class="nk-ecwg-subtitle">Running</div>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-warning" style="width: {{ $total > 0 ? ($stats['deployment_stats']['running'] / $total) * 100 : 0 }}%"></div>
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

    <!-- My Projects -->
    <div class="row g-gs">
        <div class="col-12">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">My Projects</h6>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('deployments.create') }}" class="btn btn-primary btn-sm">
                                <em class="icon ni ni-plus"></em>
                                <span>Add Project</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-inner p-0">
                    <div class="nk-tb-list nk-tb-ulist">
                        <div class="nk-tb-head">
                            <div class="nk-tb-col"><span class="sub-text">Project</span></div>
                            <div class="nk-tb-col tb-col-md"><span class="sub-text">Status</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Last Deployment</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Actions</span></div>
                        </div>
                        @forelse($projects as $project)
                        <div class="nk-tb-item">
                            <div class="nk-tb-col">
                                <div class="project-card">
                                    <div class="project-info">
                                        <span class="tb-lead">{{ $project->name }}</span>
                                        <span class="fs-12px text-muted">{{ Str::limit($project->description ?? 'No description', 60) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="badge badge-sm badge-dot has-bg bg-{{ $project->is_active ? 'success' : 'gray' }} d-none d-sm-inline-flex">
                                    {{ $project->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                @if($project->latestDeployment)
                                    <div class="user-info">
                                        <span class="tb-lead">{{ $project->latestDeployment->created_at->format('M d, Y H:i') }}</span>
                                        <span class="badge badge-sm badge-dot has-bg bg-{{ $project->latestDeployment->status === 'success' ? 'success' : ($project->latestDeployment->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($project->latestDeployment->status) }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-muted">Never deployed</span>
                                @endif
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                <div class="dropdown">
                                    <a href="#" class="dropdown-toggle btn btn-icon btn-trigger" data-bs-toggle="dropdown">
                                        <em class="icon ni ni-more-h"></em>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <ul class="link-list-opt no-bdr">
                                            <li><a href="{{ route('deployments.show', $project) }}"><em class="icon ni ni-eye"></em><span>View Details</span></a></li>
                                            <li><a href="{{ route('deployments.edit', $project) }}"><em class="icon ni ni-edit"></em><span>Edit Project</span></a></li>
                                            <li><a href="{{ route('pipelines.project', $project) }}"><em class="icon ni ni-flow"></em><span>View Pipeline</span></a></li>
                                            <li class="divider"></li>
                                            <li>
                                                <a href="#" onclick="deployProject({{ $project->id }})" class="text-primary">
                                                    <em class="icon ni ni-send"></em><span>Deploy Now</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('scheduled-deployments.create') }}?project={{ $project->id }}" class="text-info">
                                                    <em class="icon ni ni-calendar"></em><span>Schedule Deployment</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="nk-tb-item">
                            <div class="nk-tb-col text-center" colspan="4">
                                <div class="py-4">
                                    <em class="icon icon-lg ni ni-package text-muted mb-3"></em>
                                    <p class="text-muted">No projects assigned yet</p>
                                    <a href="{{ route('deployments.create') }}" class="btn btn-primary">Create Your First Project</a>
                                </div>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-gs">
        <div class="col-xxl-8">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">My Recent Deployments</h6>
                        </div>
                    </div>
                </div>
                <div class="card-inner p-0">
                    <div class="nk-tb-list nk-tb-ulist">
                        <div class="nk-tb-head">
                            <div class="nk-tb-col"><span class="sub-text">Project</span></div>
                            <div class="nk-tb-col tb-col-md"><span class="sub-text">Status</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Date</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Actions</span></div>
                        </div>
                        @forelse($stats['recent_deployments'] as $deployment)
                        <div class="nk-tb-item">
                            <div class="nk-tb-col">
                                <div class="user-card">
                                    <div class="user-info">
                                        <span class="tb-lead">{{ $deployment->project->name ?? 'Unknown Project' }}</span>
                                        <span class="fs-12px text-muted">{{ Str::limit($deployment->project->description ?? '', 50) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="badge badge-sm badge-dot has-bg bg-{{ $deployment->status === 'success' ? 'success' : ($deployment->status === 'failed' ? 'danger' : 'warning') }} d-none d-sm-inline-flex">
                                    {{ ucfirst($deployment->status) }}
                                </span>
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                <span class="tb-date">{{ $deployment->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                <a href="{{ route('deployments.logs', [$deployment->project, $deployment]) }}" class="btn btn-sm btn-outline-light">
                                    <em class="icon ni ni-eye"></em>
                                    <span>View Logs</span>
                                </a>
                            </div>
                        </div>
                        @empty
                        <div class="nk-tb-item">
                            <div class="nk-tb-col text-center" colspan="4">
                                <span class="text-muted">No recent deployments</span>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">My Scheduled Deployments</h6>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('scheduled-deployments.create') }}" class="link">Schedule New</a>
                        </div>
                    </div>
                </div>
                <div class="card-inner p-0">
                    <div class="nk-tb-list nk-tb-ulist">
                        @forelse($stats['my_scheduled'] as $scheduled)
                        <div class="nk-tb-item">
                            <div class="nk-tb-col">
                                <div class="user-card">
                                    <div class="user-info">
                                        <span class="tb-lead">{{ $scheduled->project->name ?? 'Unknown Project' }}</span>
                                        <span class="fs-12px text-muted">{{ $scheduled->scheduled_at->format('M d, H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="nk-tb-col tb-col-sm">
                                <div class="dropdown">
                                    <a href="#" class="dropdown-toggle btn btn-icon btn-trigger btn-sm" data-bs-toggle="dropdown">
                                        <em class="icon ni ni-more-h"></em>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <ul class="link-list-opt no-bdr">
                                            <li><a href="{{ route('scheduled-deployments.show', $scheduled) }}"><em class="icon ni ni-eye"></em><span>View</span></a></li>
                                            <li><a href="{{ route('scheduled-deployments.edit', $scheduled) }}"><em class="icon ni ni-edit"></em><span>Edit</span></a></li>
                                            <li class="divider"></li>
                                            <li>
                                                <a href="#" onclick="cancelScheduled({{ $scheduled->id }})" class="text-danger">
                                                    <em class="icon ni ni-cross"></em><span>Cancel</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="nk-tb-item">
                            <div class="nk-tb-col text-center">
                                <span class="text-muted">No scheduled deployments</span>
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

@push('scripts')
<script>
function deployProject(projectId) {
    if (confirm('Deploy this project now?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/deployments/${projectId}/deploy`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function cancelScheduled(scheduledId) {
    if (confirm('Cancel this scheduled deployment?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/scheduled-deployments/${scheduledId}/cancel`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';
        
        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
