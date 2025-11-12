@extends('layouts.deployment')

@section('title', 'Commit History | ' . $project->name)

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">
                    <a href="{{ route('deployments.show', $project) }}">Commit History</a>
                </h3>
                <div class="nk-block-des text-soft">
                    <p>Project: {{ $project->name }} | Branch: {{ $project->current_branch }}</p>
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
            <h5 class="card-title mb-3">Repository Information</h5>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <p><strong>Repository:</strong> {{ $project->repository_url }}</p>
                    <p><strong>Branch:</strong> {{ $project->current_branch }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Last Updated:</strong> {{ $project->updated_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-bordered mt-4">
        <div class="card-inner">
            <h5 class="card-title mb-3">Commit History</h5>
            
            @if($error)
                <div class="alert alert-warning">
                    <h6 class="alert-heading">Warning</h6>
                    <p>{{ $error }}</p>
                </div>
            @endif
            
            @if(count($commits) > 0)
                <div class="table-responsive">
                    <table class="table table-lg">
                        <thead>
                            <tr>
                                <th>Commit</th>
                                <th>Author</th>
                                <th>Date</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commits as $commit)
                            <tr>
                                <td>
                                    <a href="{{ $commit['html_url'] ?? '#' }}" target="_blank" class="text-monospace">
                                        {{ substr($commit['sha'] ?? '', 0, 7) }}
                                    </a>
                                </td>
                                <td>
                                    @if(isset($commit['author']))
                                        {{ $commit['author']['login'] ?? $commit['commit']['author']['name'] ?? 'Unknown' }}
                                    @else
                                        Unknown
                                    @endif
                                </td>
                                <td>
                                    @if(isset($commit['commit']['author']['date']))
                                        {{ \Carbon\Carbon::parse($commit['commit']['author']['date'])->format('M d, Y H:i') }}
                                    @else
                                        Unknown
                                    @endif
                                </td>
                                <td>
                                    @if(isset($commit['commit']['message']))
                                        {{ \Illuminate\Support\Str::limit($commit['commit']['message'], 100) }}
                                    @else
                                        No message
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <h6 class="alert-heading">Note</h6>
                    <p class="mb-0">Showing the most recent {{ count($commits) }} commits. For a complete history, visit the repository directly.</p>
                </div>
            @else
                <div class="text-center py-5">
                    <em class="icon icon-lg ni ni-file-text text-muted mb-3"></em>
                    <h5>No commit history available</h5>
                    <p class="text-muted">
                        @if($error)
                            {{ $error }}
                        @else
                            No commits found for this branch.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection