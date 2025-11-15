@extends('layouts.deployment')

@section('title', 'Deployment Manager')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Deployment Manager</h3>
            </div>
            @can('create', App\Models\Project::class)
            <div class="nk-block-head-content">
                <a href="{{ route('deployments.create') }}" class="btn btn-primary">
                    <em class="icon ni ni-plus"></em>
                    <span>Add New Project</span>
                </a>
            </div>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Scheduled Deployments Section -->
    <div class="nk-block-head nk-block-head-sm mt-4">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h4 class="nk-block-title">Scheduled Deployments</h4>
            </div>
            <div class="nk-block-head-content">
                <a href="{{ route('scheduled-deployments.create') }}" class="btn btn-primary">
                    <em class="icon ni ni-calendar-plus"></em>
                    <span>Schedule Deployment</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card card-bordered mb-4">
        <div class="card-inner">
            <p class="card-text">Schedule deployments to run automatically at specific times. You can also set up recurring deployments for regular maintenance.</p>
            <a href="{{ route('scheduled-deployments.index') }}" class="btn btn-light">
                <em class="icon ni ni-calendar"></em>
                <span>View All Scheduled Deployments</span>
            </a>
        </div>
    </div>

    @isset($upcomingScheduledDeployments)
        @if($upcomingScheduledDeployments->count() > 0)
            <div class="card card-bordered mb-4">
                <div class="card-inner">
                    <h5 class="card-title">Upcoming Scheduled Deployments</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Project</th>
<th>Scheduled Time</th>
<th>Description</th>
<th>Scheduled By</th>
</tr>
</thead>
<tbody>
@foreach($upcomingScheduledDeployments as $scheduled)
<tr>
<td>{{ $scheduled->project->name }}</td>
<td>{{ $scheduled->scheduled_at->format('M j, Y g:i A') }}</td>
<td>{{ $scheduled->description ?? 'No description' }}</td>
<td>{{ $scheduled->user->name }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>
</div>
@endif
@endisset

<div class="row g-gs">
@forelse($projects as $project)
<div class="col-xxl-4 col-lg-6 col-md-6">
<div class="card card-bordered h-100">
<div class="card-inner">
<div class="d-flex justify-content-between align-items-start mb-3">
<h5 class="card-title mb-0">{{ $project->name }}</h5>
<span class="badge bg-{{ $project->is_active ? 'success' : 'danger' }} ms-2">
{{ $project->is_active ? 'Active' : 'Inactive' }}
</span>
</div>

<p class="card-text text-muted small">{{ Str::limit($project->description, 100) }}</p>

<div class="card-text mt-3">
<div class="small text-muted">
<strong>Branch:</strong> {{ $project->current_branch }}
</div>
<div class="small text-muted mt-1">
<strong>Last Deployed:</strong>
@if($project->latestDeployment)
{{ $project->latestDeployment->completed_at?->diffForHumans() ?? 'Never' }}
@else
Never
@endif
</div>
<div class="small text-muted mt-1">
<strong>Environment:</strong>
@if($project->latestDeployment && $project->latestDeployment->environment)
<span class="badge bg-secondary">{{ $project->latestDeployment->environment->name }}</span>
@else
<span class="text-muted">None</span>
@endif
</div>
<div class="small text-muted mt-1">
<strong>Status:</strong>
@if($project->latestDeployment)
<span class="badge bg-{{ $project->latestDeployment->status === 'success' ? 'success' :
($project->latestDeployment->status === 'failed' ? 'danger' : 'warning') }}">
{{ ucfirst($project->latestDeployment->status) }}
</span>
@else
<span class="text-muted">No deployments yet</span>
@endif
</div>
</div>

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ route('deployments.show', $project) }}" class="btn btn-light">
                            <em class="icon ni ni-eye"></em>
                            <span>View Details</span>
                        </a>
                        @if($project->projectEnvironments->where('environment.slug', 'production')->first())
                        <a href="{{ $project->projectEnvironments->where('environment.slug', 'production')->first()->application_url }}" target="_blank" rel="noopener" class="btn btn-success">
                            <em class="icon ni ni-external"></em>
                            <span>Open Production</span>
                        </a>
                        @elseif($project->projectEnvironments->first())
                        <a href="{{ $project->projectEnvironments->first()->application_url }}" target="_blank" rel="noopener" class="btn btn-success">
                            <em class="icon ni ni-external"></em>
                            <span>Open App</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="text-center py-5">
                        <em class="icon icon-lg ni ni-package text-muted mb-3"></em>
                        <h5>No projects found</h5>
                        <p class="text-muted">Get started by adding a new project.</p>
                        @can('create', App\Models\Project::class)
                        <a href="{{ route('deployments.create') }}" class="btn btn-primary">
                            <em class="icon ni ni-plus"></em>
                            <span>Add Project</span>
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>

@endsection