<div class="nk-sidebar nk-sidebar-fixed is-light " data-content="sidebarMenu">
    <div class="nk-sidebar-element nk-sidebar-head">
        <div class="nk-sidebar-brand">
            <a href="{{ route('dashboard') }}" class="logo-link nk-sidebar-logo">
                <img class="logo-light logo-img" src="{{ asset('images/logo.png') }}" srcset="{{ asset('images/logo2x.png 2x') }}" alt="logo">
                <img class="logo-dark logo-img" src="{{ asset('images/logo-dark.png') }}" srcset="{{ asset('images/logo-dark2x.png 2x') }}" alt="logo-dark">
                <img class="logo-small logo-img logo-img-small" src="{{ asset('images/logo-small.png') }}" srcset="{{ asset('images/logo-small2x.png 2x') }}" alt="logo-small">
            </a>
        </div>
        <div class="nk-menu-trigger me-n2">
            <a href="#" class="nk-nav-toggle nk-quick-nav-icon d-xl-none" data-target="sidebarMenu"><em class="icon ni ni-arrow-left"></em></a>
            <a href="#" class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex" data-target="sidebarMenu"><em class="icon ni ni-menu"></em></a>
        </div>
    </div><!-- .nk-sidebar-element -->
    <div class="nk-sidebar-element">
        <div class="nk-sidebar-content">
            <div class="nk-sidebar-menu" data-simplebar>
                <ul class="nk-menu">
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Deployment Manager</h6>
                    </li><!-- .nk-menu-heading -->
                    
                    <li class="nk-menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-dashboard-fill"></em></span>
                            <span class="nk-menu-text">Dashboard</span>
                        </a>
                    </li><!-- .nk-menu-item -->
                    
                    @can('viewAny', App\Models\Project::class)
                    <li class="nk-menu-item {{ request()->routeIs('deployments.index') || request()->routeIs('deployments.create') || request()->routeIs('deployments.show') || request()->routeIs('deployments.edit') ? 'active' : '' }}">
                        <a href="{{ route('deployments.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-package-fill"></em></span>
                            <span class="nk-menu-text">
                                @if(auth()->user()->hasRole('admin'))
                                    All Projects
                                @else
                                    My Projects
                                @endif
                            </span>
                        </a>
                    </li><!-- .nk-menu-item -->
                    @endcan
                    
                    @can('create', App\Models\Project::class)
                    <li class="nk-menu-item {{ request()->routeIs('deployments.create') ? 'active' : '' }}">
                        <a href="{{ route('deployments.create') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-plus-circle-fill"></em></span>
                            <span class="nk-menu-text">Add Project</span>
                        </a>
                    </li><!-- .nk-menu-item -->
                    @endcan
                    
                    @can('viewAny', App\Models\ScheduledDeployment::class)
                    <li class="nk-menu-item {{ request()->routeIs('scheduled-deployments.index') || request()->routeIs('scheduled-deployments.create') ? 'active' : '' }}">
                        <a href="{{ route('scheduled-deployments.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-calendar-fill"></em></span>
                            <span class="nk-menu-text">
                                @if(auth()->user()->roles->contains('name', 'admin'))
                                    All Scheduled Deployments
                                @else
                                    My Scheduled Deployments
                                @endif
                            </span>
                        </a>
                    </li><!-- .nk-menu-item -->
                    @endcan
                    
                  
                    
                    <li class="nk-menu-item {{ request()->routeIs('monitoring') ? 'active' : '' }}">
                        <a href="{{ route('monitoring') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-activity-round-fill"></em></span>
                            <span class="nk-menu-text">Monitoring</span>
                        </a>
                    </li>



                    <li class="nk-menu-item {{ request()->routeIs('security.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('security.dashboard') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-shield-check"></em></span>
                            <span class="nk-menu-text">Security Dashboard</span>
                        </a>
                    </li>

                    <li class="nk-menu-item {{ request()->routeIs('admin.deployment-files.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.deployment-files.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-file-code"></em></span>
                            <span class="nk-menu-text">Deployment File Generator</span>
                        </a>
                    </li>
                    
                    {{-- @can('viewAny', App\Models\Project::class)
                    <li class="nk-menu-item {{ request()->routeIs('pipelines.*') ? 'active' : '' }}">
                        <a href="#" class="nk-menu-link nk-menu-toggle">
                            <span class="nk-menu-icon"><em class="icon ni ni-flow-fill"></em></span>
                            <span class="nk-menu-text">Pipeline Visualization</span>
                        </a>
                        <ul class="nk-menu-sub">
                            <li class="nk-menu-item">
                                <a href="{{ route('deployments.index') }}" class="nk-menu-link">
                                    <span class="nk-menu-text">View Pipelines</span>
                                </a>
                            </li>
                            <li class="nk-menu-item">
                                <a href="{{ route('pipelines.templates') }}" class="nk-menu-link">
                                    <span class="nk-menu-text">Pipeline Templates</span>
                                </a>
                            </li>
                            <li class="nk-menu-item">
                                <a href="{{ route('pipelines.analytics') }}" class="nk-menu-link">
                                    <span class="nk-menu-icon"><em class="icon ni ni-bar-chart"></em></span>
                                    <span class="nk-menu-text">Pipeline Analytics</span>
                                </a>
                            </li>
                            <li class="nk-menu-item">
                                <a href="{{ route('security.dashboard') }}" class="nk-menu-link">
                                    <span class="nk-menu-icon"><em class="icon ni ni-shield-check"></em></span>
                                    <span class="nk-menu-text">Security Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcan --}}
                    
                    @can('viewAny', App\Models\Deployment::class)
                    <li class="nk-menu-item {{ request()->routeIs('admin.deployments') ? 'active' : '' }}">
                        <a href="{{ route('admin.deployments') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-send-fill"></em></span>
                            <span class="nk-menu-text">All Deployments</span>
                        </a>
                    </li><!-- .nk-menu-item -->
                    @endcan
                    
                    @can('viewAny', App\Models\User::class)
                    <li class="nk-menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.users.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-users-fill"></em></span>
                            <span class="nk-menu-text">User Management</span>
                        </a>
                    </li><!-- .nk-menu-item -->
                    @endcan
                    
                    @can('viewAny', App\Models\Environment::class)
                    <li class="nk-menu-item {{ request()->routeIs('admin.environments.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.environments.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-server-fill"></em></span>
                            <span class="nk-menu-text">Environment Management</span>
                        </a>
                    </li><!-- .nk-menu-item -->
                    @endcan
                    
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Account</h6>
                    </li><!-- .nk-menu-heading -->
                    
                    <li class="nk-menu-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                        <a href="{{ route('profile.edit') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-user-fill"></em></span>
                            <span class="nk-menu-text">Profile</span>
                        </a>
                    </li>
                    
                  
                    
                    <li class="nk-menu-item">
                        <a href="{{ route('logout') }}" class="nk-menu-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <span class="nk-menu-icon"><em class="icon ni ni-signout"></em></span>
                            Logout
                            
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li><!-- .nk-menu-item -->
                </ul><!-- .nk-menu -->
            </div><!-- .nk-sidebar-menu -->
        </div><!-- .nk-sidebar-content -->
    </div><!-- .nk-sidebar-element -->
</div><!-- sidebar @e -->