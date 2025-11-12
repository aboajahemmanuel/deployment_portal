<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="js">
<head>
    <base href="/">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fav Icon -->
    <link rel="shortcut icon" href="/images/favicon.png">
    
    <!-- DashLite Styles -->
    <link rel="stylesheet" href="/assets/css/dashlite.css?ver=3.2.3">
    <link id="skin-default" rel="stylesheet" href="/assets/css/theme.css?ver=3.2.3">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="/css/custom.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="nk-body bg-lighter npc-default has-sidebar">
    <div class="nk-app-root">
        <div class="nk-main">
            <!-- Sidebar -->
            @include('components.deployment-sidebar')
            
            <!-- Wrap -->
            <div class="nk-wrap">
                <!-- Main Header -->
                @if (auth()->check())
                <div class="nk-header is-light nk-header-fixed is-light">
                    <div class="container-xl wide-xl">
                        <div class="nk-header-wrap">
                            <div class="nk-header-brand d-xl-none">
                                <a href="{{ route('dashboard') }}" class="logo-link">
                                    <img class="logo-light logo-img" src="/images/logo.png" srcset="/images/logo2x.png 2x" alt="logo">
                                </a>
                            </div>
                           
                            <div class="nk-header-tools">
                                <ul class="nk-quick-nav">
                                    @if (auth()->check())
                                    <!-- Dark Mode Toggle -->
                                    <li class="dropdown">
                                        <a href="#" id="darkModeToggle" class="nk-quick-nav-icon">
                                            <div class="quick-icon">
                                                <em id="darkModeIcon" class="icon ni ni-moon"></em>
                                            </div>
                                        </a>
                                    </li>
                                   
                                    <li class="dropdown notification-dropdown">
                                        <a href="#" class="dropdown-toggle nk-quick-nav-icon" data-bs-toggle="dropdown">
                                            <div class="icon-status icon-status-info">
                                                <em class="icon ni ni-bell"></em>
                                                <span class="notification-badge" style="display: none; position: absolute; top: -5px; right: -5px; background: #e85347; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center;">0</span>
                                            </div>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-xl dropdown-menu-end">
                                            <div class="dropdown-head">
                                                <span class="sub-title nk-dropdown-title">Notifications</span>
                                                <a href="#" id="markAllAsRead">Mark All as Read</a>
                                            </div>
                                            <div class="dropdown-body">
                                                <div class="nk-notification" id="notificationsList">
                                                    <div class="nk-notification-item text-center py-4">
                                                        <div class="nk-notification-content">
                                                            <div class="nk-notification-text text-muted">Loading notifications...</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown-foot center">
                                                <a href="#">View All</a>
                                            </div>
                                        </div>
                                    </li>

                                    <li class="dropdown user-dropdown">
                                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                                            <div class="user-toggle">
                                                <div class="user-avatar sm">
                                                    <em class="icon ni ni-user-alt"></em>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-md dropdown-menu-end">
                                            <div class="dropdown-inner user-card-wrap bg-lighter d-none d-md-block">
                                                <div class="user-card">
                                                    <div class="user-avatar">
                                                        @if(Auth::user()->avatar)
                                                            <img src="{{ asset('storage/images/avatar/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                                                        @else
                                                            <span>{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="user-info">
                                                        <span class="lead-text">{{ Auth::user()->name }}</span>
                                                        <span class="sub-text">{{ Auth::user()->email }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown-inner">
                                                <ul class="link-list">
                                                    <li><a href="{{ route('profile.edit') }}"><em class="icon ni ni-user-alt"></em><span>View Profile</span></a></li>
                                                    <li><a href="{{ route('profile.edit') }}"><em class="icon ni ni-setting-alt"></em><span>Account Settings</span></a></li>
                                                    <li><a href="#" id="loginActivityLink"><em class="icon ni ni-activity-alt"></em><span>Login Activity</span></a></li>
                                                    <li><a href="#" id="darkModeToggleProfile"><em class="icon ni ni-moon"></em><span>Dark Mode</span></a></li>
                                                </ul>
                                            </div>
                                            <div class="dropdown-inner">
                                                <ul class="link-list">
                                                    <li>
                                                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                                            @csrf
                                                            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                                <em class="icon ni ni-signout"></em><span>Sign out</span>
                                                            </a>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <!-- Main Header End -->

                <!-- Main Content -->
                <div class="nk-content">
                    <div class="container-xl wide-xl">
                        <div class="nk-content-body">
                            @yield('content')
                        </div>
                    </div>
                </div>
                <!-- Main Content End -->

                <!-- Footer -->
                <div class="nk-footer">
                    <div class="container-xl wide-xl">
                        <div class="nk-footer-wrap">
                            <div class="nk-footer-copyright"> &copy; 2025 Laravel Deployment Manager
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Footer End -->
            </div>
            <!-- Wrap End -->
        </div>
    </div>
    
    <!-- DashLite Scripts -->
    <script src="/assets/js/bundle.js?ver=3.2.3"></script>
    <script src="/assets/js/scripts.js?ver=3.2.3"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Notifications Script -->
    <script src="{{ asset('js/notifications.js') }}"></script>
    
    @stack('scripts')
</body>
</html>