@extends('layouts.deployment')

@section('title', 'Deployment Logs | ' . $project->name)

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">
                    <a href="{{ route('deployments.show', $project) }}">Deployment Logs</a>
                </h3>
                <div class="nk-block-des text-soft">
                    <p>Project: {{ $project->name }} | Deployment #{{ $deployment->id }}</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <a href="{{ route('deployments.show', $project) }}" class="btn btn-primary">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Back to Project</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title">Deployment Details</h5>
                <span class="badge bg-{{ $deployment->status === 'success' ? 'success' : ($deployment->status === 'failed' ? 'danger' : 'warning') }}">
                    {{ ucfirst($deployment->status) }}
                </span>
            </div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <p><strong>Started:</strong> {{ $deployment->started_at?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                    <p><strong>Completed:</strong> {{ $deployment->completed_at?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                    @if($deployment->commit_hash)
                        <p><strong>Commit Hash:</strong> <code>{{ $deployment->commit_hash }}</code></p>
                    @endif
                </div>
                <div class="col-md-6">
                    <p><strong>User:</strong> {{ $deployment->user->name }}</p>
                    <p><strong>Branch:</strong> {{ $project->current_branch }}</p>
                    @if($deployment->environment)
                        <p><strong>Environment:</strong> 
                            <span class="badge bg-secondary">{{ $deployment->environment->name }}</span>
                        </p>
                    @endif
                    @if($deployment->is_rollback)
                        <p><strong>Type:</strong> <span class="badge bg-info">Rollback</span></p>
                        @if($deployment->rollbackTarget)
                            <p><strong>Rollback Target:</strong> 
                                <a href="{{ route('deployments.detailed-logs', [$project, $deployment->rollbackTarget]) }}">Deployment #{{ $deployment->rollbackTarget->id }}</a>
                            </p>
                        @endif
                        @if($deployment->rollback_reason)
                            <p><strong>Rollback Reason:</strong> {{ $deployment->rollback_reason }}</p>
                        @endif
                    @else
                        <p><strong>Type:</strong> <span class="badge bg-primary">Regular Deployment</span></p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-bordered mt-4">
        <div class="card-inner">
            <h5 class="card-title mb-3">Deployment Logs</h5>
            
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-lg">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Level</th>
                                <th>Message</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('H:i:s') }}</td>
                                <td>
                                    @if($log->log_level === 'error')
                                        <span class="badge bg-danger">{{ ucfirst($log->log_level) }}</span>
                                    @elseif($log->log_level === 'warning')
                                        <span class="badge bg-warning">{{ ucfirst($log->log_level) }}</span>
                                    @elseif($log->log_level === 'info')
                                        <span class="badge bg-info">{{ ucfirst($log->log_level) }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($log->log_level) }}</span>
                                    @endif
                                </td>
                                <td>{{ $log->message }}</td>
                                <td>
                                    @if($log->context)
                                        <button class="btn btn-sm btn-light" 
                                                onclick="showContext({{ json_encode($log->context) }})">
                                            View Details
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <em class="icon icon-lg ni ni-file-text text-muted mb-3"></em>
                    <p class="text-muted">No logs available for this deployment</p>
                </div>
            @endif
        </div>
    </div>
    
    @if($deployment->is_rollback && $deployment->rollbackTarget)
        <div class="card card-bordered mt-4">
            <div class="card-inner">
                <h5 class="card-title mb-3">Rollback Target Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <p><strong>Target Deployment:</strong> #{{ $deployment->rollbackTarget->id }}</p>
                        <p><strong>Target Status:</strong> 
                            <span class="badge bg-{{ $deployment->rollbackTarget->status === 'success' ? 'success' : ($deployment->rollbackTarget->status === 'failed' ? 'danger' : 'warning') }}">
                                {{ ucfirst($deployment->rollbackTarget->status) }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Target Completed:</strong> {{ $deployment->rollbackTarget->completed_at?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                        @if($deployment->rollbackTarget->commit_hash)
                            <p><strong>Target Commit:</strong> <code>{{ $deployment->rollbackTarget->commit_hash }}</code></p>
                        @endif
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('deployments.detailed-logs', [$project, $deployment->rollbackTarget]) }}" class="btn btn-outline-primary">
                        <em class="icon ni ni-eye"></em>
                        <span>View Target Deployment Logs</span>
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal for showing context details -->
<div class="modal fade" id="contextModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Context Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="contextContent" class="bg-light p-3" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showContext(context) {
    const formattedContext = JSON.stringify(context, null, 2);
    document.getElementById('contextContent').textContent = formattedContext;
    new bootstrap.Modal(document.getElementById('contextModal')).show();
}
</script>
@endsection