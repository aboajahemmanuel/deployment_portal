@extends('layouts.deployment')

@section('title', 'Admin Dashboard | Deployment Manager')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Deployment Manager Admin Dashboard</h3>
                <div class="nk-block-des text-soft">
                    <p>Welcome back, {{ Auth::user()->name }}!</p>
                </div>
            </div><!-- .nk-block-head-content -->
        </div><!-- .nk-block-between -->
    </div><!-- .nk-block-head -->
    
    <div class="nk-block">
        <!-- Stats Section -->
        <div class="row g-gs mb-4">
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
                                </div>
                                <div class="info">
                                    <span class="change up text-success">
                                        <em class="icon ni ni-trending-up"></em>{{ $stats['active_projects'] }} Active
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
                                    <h6 class="title">Total Pipelines</h6>
                                </div>
                            </div>
                            <div class="data">
                                <div class="data-group">
                                    <div class="amount">{{ $pipelineMetrics['total_pipelines'] }}</div>
                                </div>
                                <div class="info">
                                    <span class="change up text-info">
                                        <em class="icon ni ni-activity"></em>{{ $pipelineMetrics['active_pipelines'] }} Running
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
                                    <div class="amount">{{ $pipelineMetrics['success_rate'] }}%</div>
                                </div>
                                <div class="info">
                                    <span class="change up text-{{ $pipelineMetrics['success_rate'] >= 80 ? 'success' : ($pipelineMetrics['success_rate'] >= 60 ? 'warning' : 'danger') }}">
                                        <em class="icon ni ni-check-circle"></em>Overall
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
                                </div>
                                <div class="info">
                                    <span class="change down text-danger">
                                        <em class="icon ni ni-cross-circle"></em>{{ $stats['deployment_stats']['running'] }} Running
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- .row -->
        
        <!-- Pipeline Trends Chart -->
        <div class="row g-gs mb-4">
            <div class="col-12">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">Pipeline Execution Trends</h6>
                            </div>
                        </div>
                        <div class="nk-ck-sm">
                            <canvas class="line-chart" id="pipelineTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row g-gs mb-4">
            <div class="col-xxl-12">
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
            
            {{-- <div class="col-xxl-4">
                <div class="card card-bordered h-100">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">Stage Success Rates</h6>
                            </div>
                        </div>
                        <div class="nk-ck-sm" style="height: 350px; position: relative;">
                            <canvas id="stageSuccessChart"></canvas>
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>
        
        <!-- Quick Actions -->
        <div class="card card-bordered mb-4">
            <div class="card-inner">
                <div class="card-title-group">
                    <div class="card-title">
                        <h6 class="title">Quick Actions</h6>
                    </div>
                </div>
                
                <div class="row g-gs">
                    <div class="col-md-3">
                        <a href="{{ route('deployments.create') }}" class="btn btn-outline-primary btn-block">
                            <em class="icon ni ni-plus"></em>
                            <span>Add Project</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-info btn-block">
                            <em class="icon ni ni-users"></em>
                            <span>Manage Users</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('deployments.index') }}" class="btn btn-outline-success btn-block">
                            <em class="icon ni ni-send"></em>
                            <span>View Deployments</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('pipelines.analytics') }}" class="btn btn-outline-warning btn-block">
                            <em class="icon ni ni-bar-chart"></em>
                            <span>Pipeline Analytics</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Project Performance Analytics -->
        @if(isset($projectAnalytics) && !empty($projectAnalytics))
        <div class="card card-bordered">
            <div class="card-inner">
                <div class="card-title-group">
                    <div class="card-title">
                        <h6 class="title">Project Performance Analytics</h6>
                    </div>
                </div>
                <div class="nk-tb-list nk-tb-ulist">
                    <div class="nk-tb-head">
                        <div class="nk-tb-col"><span class="sub-text">Project</span></div>
                        <div class="nk-tb-col tb-col-md"><span class="sub-text">Deployments</span></div>
                        <div class="nk-tb-col tb-col-lg"><span class="sub-text">Success Rate</span></div>
                        <div class="nk-tb-col tb-col-lg"><span class="sub-text">Avg Duration</span></div>
                        <div class="nk-tb-col"><span class="sub-text">Status</span></div>
                    </div>
                    @foreach($projectAnalytics as $project)
                    <div class="nk-tb-item">
                        <div class="nk-tb-col">
                            <div class="user-card">
                                <div class="user-info">
                                    <span class="tb-lead">{{ $project['name'] }}</span>
                                    <span class="fs-12px text-muted">{{ $project['owner'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="nk-tb-col tb-col-md">
                            <span class="tb-amount">{{ $project['total_deployments'] }}</span>
                        </div>
                        <div class="nk-tb-col tb-col-lg">
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-{{ $project['success_rate'] >= 80 ? 'success' : ($project['success_rate'] >= 60 ? 'warning' : 'danger') }}" 
                                     style="width: {{ $project['success_rate'] }}%"></div>
                            </div>
                            <span class="fs-12px text-muted">{{ $project['success_rate'] }}%</span>
                        </div>
                        <div class="nk-tb-col tb-col-lg">
                            <span class="tb-amount">{{ $project['avg_duration'] }}</span>
                        </div>
                        <div class="nk-tb-col">
                            <span class="badge badge-sm badge-dot has-bg bg-{{ $project['success_rate'] >= 80 ? 'success' : ($project['success_rate'] >= 60 ? 'warning' : 'danger') }}">
                                {{ $project['success_rate'] >= 80 ? 'Excellent' : ($project['success_rate'] >= 60 ? 'Good' : 'Needs Attention') }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div><!-- .nk-block -->
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Pipeline Trends Chart
const trendsCtx = document.getElementById('pipelineTrendsChart').getContext('2d');
const trendsData = @json($pipelineMetrics['pipeline_trends'] ?? []);

const trendsLabels = Object.keys(trendsData);
const successData = trendsLabels.map(date => trendsData[date]?.successful || 0);
const failureData = trendsLabels.map(date => trendsData[date]?.failed || 0);
const totalData = trendsLabels.map(date => trendsData[date]?.total || 0);

new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: trendsLabels,
        datasets: [
            {
                label: 'Successful',
                data: successData,
                borderColor: '#1ee0ac',
                backgroundColor: 'rgba(30, 224, 172, 0.1)',
                tension: 0.4
            },
            {
                label: 'Failed',
                data: failureData,
                borderColor: '#e85347',
                backgroundColor: 'rgba(232, 83, 71, 0.1)',
                tension: 0.4
            },
            {
                label: 'Total',
                data: totalData,
                borderColor: '#526484',
                backgroundColor: 'rgba(82, 100, 132, 0.1)',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(82, 100, 132, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Stage Success Chart - Radial Progress Chart
const stageCtx = document.getElementById('stageSuccessChart').getContext('2d');

@if(isset($stageSuccessRates) && !empty($stageSuccessRates))
const stageData = @json($stageSuccessRates);

const stageLabels = Object.keys(stageData);
const stageRates = stageLabels.map(stage => stageData[stage].rate);
const stageTotals = stageLabels.map(stage => stageData[stage].total);
const stageSuccessful = stageLabels.map(stage => stageData[stage].successful);

// Create a polar area chart for better visual appeal
new Chart(stageCtx, {
    type: 'polarArea',
    data: {
        labels: stageLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1).replace('_', ' ')),
        datasets: [{
            data: stageRates,
            backgroundColor: [
                'rgba(30, 224, 172, 0.8)',  // Green
                'rgba(244, 189, 14, 0.8)',  // Yellow
                'rgba(232, 83, 71, 0.8)',   // Red
                'rgba(82, 100, 132, 0.8)',  // Blue
                'rgba(139, 69, 255, 0.8)',  // Purple
                'rgba(255, 159, 67, 0.8)'   // Orange
            ],
            borderColor: [
                '#1ee0ac',
                '#f4bd0e',
                '#e85347',
                '#526484',
                '#8b45ff',
                '#ff9f43'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true,
                    font: {
                        size: 11
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const index = context.dataIndex;
                        const successful = stageSuccessful[index];
                        const total = stageTotals[index];
                        const rate = context.parsed.r;
                        const label = context.label;
                        return `${label}: ${rate}% (${successful}/${total} successful)`;
                    }
                }
            }
        },
        scales: {
            r: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    display: false
                },
                grid: {
                    color: 'rgba(82, 100, 132, 0.1)'
                },
                angleLines: {
                    color: 'rgba(82, 100, 132, 0.1)'
                }
            }
        },
        elements: {
            arc: {
                borderWidth: 2
            }
        }
    }
});
@else
// Create empty chart container and add custom no-data message
const canvas = document.getElementById('stageSuccessChart');
const ctx = canvas.getContext('2d');

// Set canvas size
canvas.width = canvas.offsetWidth;
canvas.height = canvas.offsetHeight;

// Draw gradient background
const gradient = ctx.createRadialGradient(canvas.width/2, canvas.height/2, 0, canvas.width/2, canvas.height/2, Math.min(canvas.width, canvas.height)/2);
gradient.addColorStop(0, '#f8fafc');
gradient.addColorStop(1, '#e2e8f0');
ctx.fillStyle = gradient;
ctx.fillRect(0, 0, canvas.width, canvas.height);

// Draw circular placeholder
ctx.beginPath();
ctx.arc(canvas.width/2, canvas.height/2, 80, 0, 2 * Math.PI);
ctx.strokeStyle = '#cbd5e1';
ctx.lineWidth = 3;
ctx.setLineDash([10, 5]);
ctx.stroke();

// Add icon
ctx.font = '48px Arial';
ctx.fillStyle = '#94a3b8';
ctx.textAlign = 'center';
ctx.textBaseline = 'middle';
ctx.fillText('📊', canvas.width / 2, canvas.height / 2 - 20);

// Add text
ctx.font = 'bold 16px Arial';
ctx.fillStyle = '#475569';
ctx.fillText('No Pipeline Data', canvas.width / 2, canvas.height / 2 + 30);

ctx.font = '12px Arial';
ctx.fillStyle = '#64748b';
ctx.fillText('Deploy projects to see stage success rates', canvas.width / 2, canvas.height / 2 + 50);
@endif
</script>
@endsection