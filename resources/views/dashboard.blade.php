@extends('layouts.deployment')

@section('title', 'Deployment Manager Dashboard')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Deployment Manager Dashboard</h3>
                <div class="nk-block-des text-soft">
                    <p>Welcome back, {{ Auth::user()->name }}!</p>
                </div>
            </div><!-- .nk-block-head-content -->
        </div><!-- .nk-block-between -->
    </div><!-- .nk-block-head -->
    
    <div class="nk-block">
        <!-- Welcome Section -->
        <div class="card card-bordered mb-4">
            <div class="card-inner">
                <div class="nk-wg7">
                    <div class="nk-wg7-title">Laravel Deployment Manager</div>
                    <div class="nk-wg7-text">
                        <p>Manage and trigger deployments of Laravel projects to Windows servers connected via VPN.</p>
                    </div>
                    
                    <!-- Stats -->
                    <div class="row g-gs mt-3">
                        <div class="col-md-4">
                            <div class="card card-full">
                                <div class="card-inner p-4 text-center">
                                    <div class="h2 mb-1">{{ \App\Models\Project::count() }}</div>
                                    <div class="text-muted">Projects</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-full">
                                <div class="card-inner p-4 text-center">
                                    <div class="h2 mb-1">{{ \App\Models\Deployment::where('status', 'success')->count() }}</div>
                                    <div class="text-muted">Successful Deployments</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-full">
                                <div class="card-inner p-4 text-center">
                                    <div class="h2 mb-1">{{ \App\Models\Deployment::where('status', 'failed')->count() }}</div>
                                    <div class="text-muted">Failed Deployments</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works Section -->
        <div class="card card-bordered mb-4">
            <div class="card-inner">
                <div class="nk-wg7">
                    <div class="nk-wg7-title">How It Works</div>
                    <div class="row g-gs">
                        <div class="col-md-4">
                            <div class="card card-full">
                                <div class="card-inner text-center">
                                    <div class="icon icon-circle icon-lg bg-primary-dim mb-3">
                                        <em class="icon ni ni-laptop"></em>
                                    </div>
                                    <h6 class="title">1. Register Project</h6>
                                    <p class="text-sm">Add your Laravel project with repository URL and deployment endpoint.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card card-full">
                                <div class="card-inner text-center">
                                    <div class="icon icon-circle icon-lg bg-warning-dim mb-3">
                                        <em class="icon ni ni-send"></em>
                                    </div>
                                    <h6 class="title">2. Trigger Deployment</h6>
                                    <p class="text-sm">Click deploy to trigger the deployment script on the remote server.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card card-full">
                                <div class="card-inner text-center">
                                    <div class="icon icon-circle icon-lg bg-success-dim mb-3">
                                        <em class="icon ni ni-activity"></em>
                                    </div>
                                    <h6 class="title">3. Monitor Status</h6>
                                    <p class="text-sm">Track deployment progress and view logs for troubleshooting.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="card card-bordered">
            <div class="card-inner">
                <div class="between-center flex-wrap flex-md-nowrap g-3">
                    <div class="nk-block-text">
                        <h6>Ready to Manage Deployments?</h6>
                        <p class="text-soft">Get started by adding your first project</p>
                    </div>
                    <div class="nk-block-actions flex-shrink-sm-0">
                        <div class="justify-center">
                            <a href="{{ route('deployments.create') }}" class="btn btn-primary">
                                <em class="icon ni ni-plus"></em>
                                <span>Add Project</span>
                            </a>
                            <a href="{{ route('deployments.index') }}" class="btn btn-light ms-2">
                                <em class="icon ni ni-eye"></em>
                                <span>View All Projects</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="card card-bordered mt-4">
            <div class="card-inner">
                <div class="nk-wg7">
                    <div class="nk-wg7-title">Frequently Asked Questions</div>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    How do I add a new project?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Click the "Add Project" button and fill in the project details including the repository URL, deployment endpoint, and access token.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    What is a deployment endpoint?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>The deployment endpoint is the URL to the deploy.php script on your remote Windows server. This script handles the actual deployment process.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    How secure is the deployment process?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>All communication with deployment endpoints is token-based and secured with HTTPS. Access tokens are stored securely in the database.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- .nk-block -->
</div>
@endsection