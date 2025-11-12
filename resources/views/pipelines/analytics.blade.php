@extends('layouts.app')

@section('content')
<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Pipeline Analytics</h3>
                            <div class="nk-block-des text-soft">
                                <p>Comprehensive analytics and metrics for deployment pipelines</p>
                            </div>
                        </div>
                        <div class="nk-block-head-content">
                            <div class="toggle-wrap nk-block-tools-toggle">
                                <div class="toggle-expand-content" data-content="pageMenu">
                                    <ul class="nk-block-tools g-3">
                                        <li>
                                            <div class="form-control-wrap">
                                                <select class="form-select" id="timeRange" onchange="updateAnalytics()">
                                                    <option value="7">Last 7 Days</option>
                                                    <option value="30" selected>Last 30 Days</option>
                                                    <option value="90">Last 90 Days</option>
                                                    <option value="365">Last Year</option>
                                                </select>
                                            </div>
                                        </li>
                                        <li>
                                            <button class="btn btn-primary" onclick="refreshAnalytics()">
                                                <em class="icon ni ni-reload"></em>
                                                <span>Refresh</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Overview -->
                <div class="row g-gs">
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
                                            <div class="amount">{{ $metrics['total_pipelines'] }}</div>
                                        </div>
                                        <div class="info">
                                            <span class="change up text-success">
                                                <em class="icon ni ni-trending-up"></em>All Time
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
                                            <div class="amount">{{ $metrics['success_rate'] }}%</div>
                                        </div>
                                        <div class="info">
                                            <span class="change up text-{{ $metrics['success_rate'] >= 80 ? 'success' : ($metrics['success_rate'] >= 60 ? 'warning' : 'danger') }}">
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
                                            <h6 class="title">Active Pipelines</h6>
                                        </div>
                                    </div>
                                    <div class="data">
                                        <div class="data-group">
                                            <div class="amount">{{ $metrics['active_pipelines'] }}</div>
                                        </div>
                                        <div class="info">
                                            <span class="change up text-warning">
                                                <em class="icon ni ni-activity"></em>Running
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
                                            <h6 class="title">Avg Duration</h6>
                                        </div>
                                    </div>
                                    <div class="data">
                                        <div class="data-group">
                                            <div class="amount">
                                                @php
                                                    $minutes = floor($metrics['average_pipeline_duration'] / 60);
                                                    $seconds = $metrics['average_pipeline_duration'] % 60;
                                                @endphp
                                                {{ $minutes > 0 ? $minutes . 'm ' : '' }}{{ $seconds }}s
                                            </div>
                                        </div>
                                        <div class="info">
                                            <span class="change up text-info">
                                                <em class="icon ni ni-clock"></em>Average
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipeline Trends Chart -->
                <div class="row g-gs">
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

                <!-- Failure Analysis -->
                <div class="row g-gs">
                    <div class="col-lg-6">
                        <div class="card card-bordered h-100">
                            <div class="card-inner">
                                <div class="card-title-group">
                                    <div class="card-title">
                                        <h6 class="title">Most Common Failures</h6>
                                    </div>
                                </div>
                                <div class="nk-tb-list nk-tb-ulist">
                                    @forelse($metrics['most_common_failures'] as $stage => $count)
                                    <div class="nk-tb-item">
                                        <div class="nk-tb-col">
                                            <div class="user-card">
                                                <div class="user-info">
                                                    <span class="tb-lead">{{ ucwords(str_replace('_', ' ', $stage)) }}</span>
                                                    <span class="fs-12px text-muted">Pipeline Stage</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="nk-tb-col tb-col-md">
                                            <span class="badge badge-sm badge-dot has-bg bg-danger d-none d-md-inline-flex">{{ $count }} Failures</span>
                                        </div>
                                        <div class="nk-tb-col">
                                            <div class="progress progress-sm">
                                                @php
                                                    $maxFailures = max(array_values($metrics['most_common_failures']));
                                                    $percentage = $maxFailures > 0 ? ($count / $maxFailures) * 100 : 0;
                                                @endphp
                                                <div class="progress-bar bg-danger" style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center py-4">
                                        <em class="icon icon-lg ni ni-check-circle text-success"></em>
                                        <p class="text-muted mt-2">No failures recorded</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card card-bordered h-100">
                            <div class="card-inner">
                                <div class="card-title-group">
                                    <div class="card-title">
                                        <h6 class="title">Stage Success Rates</h6>
                                    </div>
                                </div>
                                <div class="nk-ck-sm">
                                    <canvas class="doughnut-chart" id="stageSuccessChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Analytics -->
                @if(isset($projectAnalytics) && !empty($projectAnalytics))
                <div class="row g-gs">
                    <div class="col-12">
                        <div class="card card-bordered">
                            <div class="card-inner">
                                <div class="card-title-group">
                                    <div class="card-title">
                                        <h6 class="title">Project Performance</h6>
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
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Pipeline Trends Chart
const trendsCtx = document.getElementById('pipelineTrendsChart').getContext('2d');
const trendsData = @json($metrics['pipeline_trends']);

const trendsLabels = Object.keys(trendsData);
const successData = trendsLabels.map(date => trendsData[date].successful);
const failureData = trendsLabels.map(date => trendsData[date].failed);
const totalData = trendsLabels.map(date => trendsData[date].total);

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

// Stage Success Chart (if we have stage data)
@if(isset($stageSuccessRates) && !empty($stageSuccessRates))
const stageCtx = document.getElementById('stageSuccessChart').getContext('2d');
const stageData = @json($stageSuccessRates);

const stageLabels = Object.keys(stageData);
const stageRates = stageLabels.map(stage => stageData[stage].rate);
const stageColors = ['#1ee0ac', '#f4bd0e', '#e85347', '#8094ae', '#526484'];

new Chart(stageCtx, {
    type: 'doughnut',
    data: {
        labels: stageLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)),
        datasets: [{
            data: stageRates,
            backgroundColor: stageColors.slice(0, stageLabels.length),
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        }
    }
});
@endif

function updateAnalytics() {
    const timeRange = document.getElementById('timeRange').value;
    // Implement AJAX call to update analytics based on time range
    toastr.info('Updating analytics for ' + timeRange + ' days...');
}

function refreshAnalytics() {
    location.reload();
}
</script>
@endsection
