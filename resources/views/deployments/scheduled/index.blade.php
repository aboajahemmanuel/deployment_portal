@extends('layouts.deployment')

@section('title', 'Scheduled Deployments')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Scheduled Deployments</h3>
            </div>
            <div class="nk-block-head-content">
                <a href="{{ route('scheduled-deployments.create') }}" class="btn btn-primary">
                    <em class="icon ni ni-plus"></em>
                    <span>Schedule Deployment</span>
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card card-bordered">
        <div class="card-inner">
            @if($scheduledDeployments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-lg">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Scheduled Time</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scheduledDeployments as $scheduled)
                                <tr>
                                    <td>{{ $scheduled->project->name }}</td>
                                    <td>{{ $scheduled->scheduled_at->setTimezone('Africa/Lagos')->format('M d, Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $scheduled->status === 'pending' ? 'warning' : 
                                            ($scheduled->status === 'processing' ? 'primary' : 
                                            ($scheduled->status === 'completed' ? 'success' : 
                                            ($scheduled->status === 'failed' ? 'danger' : 'secondary'))) 
                                        }}">
                                            {{ ucfirst($scheduled->status) }}
                                        </span>
                                        @if($scheduled->is_recurring)
                                            <span class="badge bg-info">Recurring</span>
                                        @endif
                                    </td>
                                    <td>{{ $scheduled->user->name }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('scheduled-deployments.show', $scheduled) }}" class="btn btn-sm btn-outline-secondary">
                                                <em class="icon ni ni-eye"></em>
                                                <span>View</span>
                                            </a>
                                            @if($scheduled->status === 'pending')
                                                <a href="{{ route('scheduled-deployments.edit', $scheduled) }}" class="btn btn-sm btn-outline-primary">
                                                    <em class="icon ni ni-edit"></em>
                                                    <span>Edit</span>
                                                </a>
                                                <form action="{{ route('scheduled-deployments.cancel', $scheduled) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this scheduled deployment?')">
                                                        <em class="icon ni ni-cross"></em>
                                                        <span>Cancel</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    {{ $scheduledDeployments->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <em class="icon icon-lg ni ni-calendar text-muted mb-3"></em>
                    <h5>No scheduled deployments</h5>
                    <p class="text-muted">Get started by scheduling a new deployment.</p>
                    <a href="{{ route('scheduled-deployments.create') }}" class="btn btn-primary">
                        <em class="icon ni ni-plus"></em>
                        <span>Schedule Deployment</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection