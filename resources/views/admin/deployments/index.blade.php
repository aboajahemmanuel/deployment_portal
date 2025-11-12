@extends('layouts.deployment')

@section('title', 'All Deployments | Admin')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">All Deployments</h3>
                <div class="nk-block-des text-soft">
                    <p>Manage all deployments across projects</p>
                </div>
            </div><!-- .nk-block-head-content -->
        </div><!-- .nk-block-between -->
    </div><!-- .nk-block-head -->
    
    <div class="nk-block">
        <div class="card card-bordered card-preview">
            <div class="card-inner">
                <table class="datatable-init table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Started At</th>
                            <th>Completed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deployments as $deployment)
                        <tr>
                            <td>{{ $deployment->project->name }}</td>
                            <td>{{ $deployment->user->name }}</td>
                            <td>
                                <span class="badge bg-{{ $deployment->status === 'success' ? 'success' : 
                                    ($deployment->status === 'failed' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($deployment->status) }}
                                </span>
                            </td>
                            <td>{{ $deployment->started_at ? $deployment->started_at->format('M d, Y H:i') : 'N/A' }}</td>
                            <td>{{ $deployment->completed_at ? $deployment->completed_at->format('M d, Y H:i') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('deployments.show', $deployment->project) }}" class="btn btn-sm btn-primary">
                                    <em class="icon ni ni-eye"></em>
                                    <span>View</span>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div><!-- .card-preview -->
    </div> <!-- .nk-block -->
</div>
@endsection