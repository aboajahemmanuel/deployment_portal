@extends('layouts.deployment-admin')

@section('title', 'Admin Dashboard | Deployment Manager')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Admin Dashboard</h3>
                <div class="nk-block-des text-soft">
                    <p>Complete system overview and management</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-gs">
        <div class="col-xxl-3 col-sm-6">
            <div class="card">
                <div class="nk-ecwg nk-ecwg6">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">Total Projects</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">{{ $stats['total_projects'] }}</div>
                                <div class="nk-ecwg6-ck">
                                    <canvas class="ecommerce-line-chart-s3" id="totalProjects"></canvas>
                                </div>
                            </div>
                            <div class="info">
                                <span class="change up text-danger">
                                    <em class="icon ni ni-arrow-long-up"></em>{{ $stats['active_projects'] }} Active
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
                                <h6 class="title">Total Deployments</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">{{ $stats['total_deployments'] }}</div>
                                <div class="nk-ecwg6-ck">
                                    <canvas class="ecommerce-line-chart-s3" id="totalDeployments"></canvas>
                                </div>
                            </div>
                            <div class="info">
                                <span class="change up text-success">
                                    <em class="icon ni ni-arrow-long-up"></em>{{ $stats['deployment_stats']['success'] }} Success
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
                                <h6 class="title">Failed Deployments</h6>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">{{ $stats['deployment_stats']['failed'] }}</div>
                                <div class="nk-ecwg6-ck">
                                    <canvas class="ecommerce-line-chart-s3" id="failedDeployments"></canvas>
                                </div>
                            </div>
                            <div class="info">
                                <span class="change down text-danger">
                                    <em class="icon ni ni-arrow-long-down"></em>{{ $stats['deployment_stats']['running'] }} Running
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
                                <div class="amount">{{ $stats['scheduled_deployments']->count() }}</div>
                                <div class="nk-ecwg6-ck">
                                    <canvas class="ecommerce-line-chart-s3" id="scheduledDeployments"></canvas>
                                </div>
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

    <!-- Recent Activity -->
    <div class="row g-gs">
        <div class="col-xxl-8">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">Recent Deployments</h6>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('deployments.index') }}" class="link">View All</a>
                        </div>
                    </div>
                </div>
                <div class="card-inner p-0">
                    <div class="nk-tb-list nk-tb-ulist">
                        <div class="nk-tb-head">
                            <div class="nk-tb-col"><span class="sub-text">Project</span></div>
                            <div class="nk-tb-col tb-col-md"><span class="sub-text">User</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Status</span></div>
                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Date</span></div>
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
                                <span class="tb-amount">{{ $deployment->user->name ?? 'System' }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                <span class="badge badge-sm badge-dot has-bg bg-{{ $deployment->status === 'success' ? 'success' : ($deployment->status === 'failed' ? 'danger' : 'warning') }} d-none d-sm-inline-flex">
                                    {{ ucfirst($deployment->status) }}
                                </span>
                            </div>
                            <div class="nk-tb-col tb-col-lg">
                                <span class="tb-date">{{ $deployment->created_at->format('M d, Y H:i') }}</span>
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
                            <h6 class="title">Scheduled Deployments</h6>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('scheduled-deployments.index') }}" class="link">View All</a>
                        </div>
                    </div>
                </div>
                <div class="card-inner p-0">
                    <div class="nk-tb-list nk-tb-ulist">
                        @forelse($stats['scheduled_deployments'] as $scheduled)
                        <div class="nk-tb-item">
                            <div class="nk-tb-col">
                                <div class="user-card">
                                    <div class="user-info">
                                        <span class="tb-lead">{{ $scheduled->project->name ?? 'Unknown Project' }}</span>
                                        <span class="fs-12px text-muted">by {{ $scheduled->user->name ?? 'System' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="nk-tb-col tb-col-sm">
                                <span class="tb-date">{{ $scheduled->scheduled_at->format('M d, H:i') }}</span>
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
