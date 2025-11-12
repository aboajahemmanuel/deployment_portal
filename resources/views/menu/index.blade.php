@extends('layouts.deployment')

@section('title', 'Menu List | Deployment Manager')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Menu List</h3>
                <div class="nk-block-des text-soft">
                    <p>Complete list of available menu items</p>
                </div>
            </div><!-- .nk-block-head-content -->
        </div><!-- .nk-block-between -->
    </div><!-- .nk-block-head -->
    
    <div class="card card-bordered">
        <div class="card-inner">
            <div class="row g-gs">
                <div class="col-lg-6">
                    <div class="card card-full">
                        <div class="card-inner">
                            <h5 class="card-title">Main Navigation</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-dashboard-fill text-primary"></em>
                                            <span class="ms-2">Dashboard</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-package-fill text-primary"></em>
                                            <span class="ms-2">Projects</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-plus-circle-fill text-primary"></em>
                                            <span class="ms-2">Add Project</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-send-fill text-primary"></em>
                                            <span class="ms-2">All Deployments</span>
                                        </div>
                                        @can('viewAny', App\Models\Deployment::class)
                                            <span class="badge bg-success">Admin Only</span>
                                        @else
                                            <span class="badge bg-light text-dark">Admin Only</span>
                                        @endcan
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-users-fill text-primary"></em>
                                            <span class="ms-2">User Management</span>
                                        </div>
                                        @can('viewAny', App\Models\User::class)
                                            <span class="badge bg-success">Admin Only</span>
                                        @else
                                            <span class="badge bg-light text-dark">Admin Only</span>
                                        @endcan
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card card-full">
                        <div class="card-inner">
                            <h5 class="card-title">Account & Settings</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-user-fill text-primary"></em>
                                            <span class="ms-2">Profile</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-setting-fill text-primary"></em>
                                            <span class="ms-2">Account Settings</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-signout-fill text-primary"></em>
                                            <span class="ms-2">Logout</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                            </ul>
                            
                            <h5 class="card-title mt-4">Project Actions</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-eye-fill text-primary"></em>
                                            <span class="ms-2">View Project Details</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-edit-fill text-primary"></em>
                                            <span class="ms-2">Edit Project</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-send-fill text-primary"></em>
                                            <span class="ms-2">Deploy Project</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <em class="icon ni ni-trash-fill text-primary"></em>
                                            <span class="ms-2">Delete Project</span>
                                        </div>
                                        <span class="badge bg-light text-dark">Available</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection