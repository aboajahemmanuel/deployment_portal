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
     <link rel="stylesheet" href="{{ asset('assets/css/dashlite.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">
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
    
    <!-- Full Page Deployment Loader Overlay -->
    <div id="pageDeploymentLoader" class="deployment-loader-overlay d-none">
        <div class="loader-content">
            <div class="tri-loader">
                <div></div>
                <div></div>
            </div>
            <h2 class="mt-4 text-white">Deployment in Progress</h2>
            <p class="text-light">Please wait while we deploy your project...</p>
            
            <!-- Progress Steps -->
            <div id="deploymentSteps" class="deployment-steps horizontal mt-4 w-75 mx-auto text-start">
                <div class="step" id="step-initializing">
                    <div class="step-icon">🕒</div>
                    <div class="step-content">
                        <div class="step-title">Initializing</div>
                        <div class="step-description">Setting up environment</div>
                    </div>
                </div>
                <div class="step" id="step-pulling">
                    <div class="step-icon">📥</div>
                    <div class="step-content">
                        <div class="step-title">Pulling Code</div>
                        <div class="step-description">Fetching updates</div>
                    </div>
                </div>
                <div class="step" id="step-dependencies">
                    <div class="step-icon">📦</div>
                    <div class="step-content">
                        <div class="step-title">Dependencies</div>
                        <div class="step-description">Installing packages</div>
                    </div>
                </div>
                <div class="step" id="step-caching">
                    <div class="step-icon">⚡</div>
                    <div class="step-content">
                        <div class="step-title">Optimizing</div>
                        <div class="step-description">Clearing caches</div>
                    </div>
                </div>
                <div class="step" id="step-migrations">
                    <div class="step-icon">📊</div>
                    <div class="step-content">
                        <div class="step-title">Migrations</div>
                        <div class="step-description">Updating database</div>
                    </div>
                </div>
                <div class="step" id="step-completing">
                    <div class="step-icon">✅</div>
                    <div class="step-content">
                        <div class="step-title">Finalizing</div>
                        <div class="step-description">Completing process</div>
                    </div>
                </div>
            </div>
            
            <div class="progress w-75 mx-auto mt-4">
                <div id="deploymentProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" 
                     aria-valuenow="0" 
                     aria-valuemin="0" 
                     aria-valuemax="100" 
                     style="width: 0%"></div>
            </div>
            <div id="progressPercentage" class="mt-2 text-white">0%</div>
        </div>
    </div>
    
    <!-- DashLite Scripts -->
   <script src="{{ asset('assets/js/bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.js') }}"></script>
    
    <!-- Notifications Script -->
    <script src="{{ asset('js/notifications.js') }}"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Display success message if session has 'success' key
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '{{ session('success') }}',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            @endif

            // Display error message if session has 'error' key
            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#e85347'
                });
            @endif

            // Display warning message if session has 'warning' key
            @if(session('warning'))
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning!',
                    text: '{{ session('warning') }}',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#f4bd0e'
                });
            @endif

            // Display info message if session has 'info' key
            @if(session('info'))
                Swal.fire({
                    icon: 'info',
                    title: 'Info',
                    text: '{{ session('info') }}',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            @endif
            
            // Global function to show SweetAlert messages
            window.showSuccessMessage = function(message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: message,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            };
            
            window.showErrorMessage = function(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#e85347'
                });
            };
            
            window.showWarningMessage = function(message) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning!',
                    text: message,
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#f4bd0e'
                });
            };
            
            window.showInfoMessage = function(message) {
                Swal.fire({
                    icon: 'info',
                    title: 'Info',
                    text: message,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            };
            
            // Confirm dialog function
            window.confirmAction = function(message, callback) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e85347',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, proceed!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        callback();
                    }
                });
            };
            
            // Global function to show page deployment loader
            window.showPageDeploymentLoader = function() {
                document.getElementById('pageDeploymentLoader').classList.remove('d-none');
                resetProgressSteps(); // Reset all steps to initial state
                simulateProgress(); // Start simulating progress
            };
            
            // Global function to hide page deployment loader
            window.hidePageDeploymentLoader = function() {
                document.getElementById('pageDeploymentLoader').classList.add('d-none');
                resetProgress(); // Reset progress bar
                resetProgressSteps(); // Reset all steps to initial state
            };
            
            // Reset all progress steps to initial state
            function resetProgressSteps() {
                const steps = document.querySelectorAll('.deployment-steps .step');
                steps.forEach(step => {
                    step.classList.remove('active', 'completed');
                });
            }
            
            // Update progress steps display
            window.updateDeploymentStep = function(stepId) {
                // Reset all steps
                resetProgressSteps();
                
                // Mark current and previous steps as completed
                const steps = document.querySelectorAll('.deployment-steps .step');
                let foundCurrent = false;
                
                steps.forEach(step => {
                    if (step.id === stepId) {
                        step.classList.add('active');
                        foundCurrent = true;
                    } else if (!foundCurrent) {
                        step.classList.add('completed');
                    }
                });
            };
            
            // Simulate progress (since we don't have real-time progress from the server)
            function simulateProgress() {
                const progressBar = document.getElementById('deploymentProgressBar');
                const progressText = document.getElementById('progressPercentage');
                let progress = 0;
                let currentStep = 0;
                
                // Define deployment steps with their corresponding progress ranges
                const steps = [
                    { id: 'step-initializing', start: 0, end: 10 },
                    { id: 'step-pulling', start: 10, end: 30 },
                    { id: 'step-dependencies', start: 30, end: 50 },
                    { id: 'step-caching', start: 50, end: 70 },
                    { id: 'step-migrations', start: 70, end: 90 },
                    { id: 'step-completing', start: 90, end: 100 }
                ];
                
                // Clear any existing interval
                if (window.progressInterval) {
                    clearInterval(window.progressInterval);
                }
                
                window.progressInterval = setInterval(() => {
                    // Move to next step when progress reaches the end of current step
                    if (currentStep < steps.length - 1 && progress >= steps[currentStep].end) {
                        currentStep++;
                    }
                    
                    // Update the current step display
                    updateDeploymentStep(steps[currentStep].id);
                    
                    // Increase progress within the current step range
                    const stepRange = steps[currentStep].end - steps[currentStep].start;
                    const increment = stepRange * 0.1; // 10% of step range per interval
                    
                    progress += increment;
                    if (progress >= 100) {
                        progress = 100;
                        // Don't clear interval here, let it continue until loader is hidden
                    }
                    
                    // Ensure progress doesn't exceed the current step's end
                    if (progress > steps[currentStep].end) {
                        progress = steps[currentStep].end;
                    }
                    
                    progressBar.style.width = progress + '%';
                    progressBar.setAttribute('aria-valuenow', progress);
                    progressText.textContent = Math.round(progress) + '%';
                }, 500);
            }
            
            // Reset progress bar to initial state
            function resetProgress() {
                const progressBar = document.getElementById('deploymentProgressBar');
                const progressText = document.getElementById('progressPercentage');
                
                // Clear the interval
                if (window.progressInterval) {
                    clearInterval(window.progressInterval);
                    window.progressInterval = null;
                }
                
                progressBar.style.width = '0%';
                progressBar.setAttribute('aria-valuenow', 0);
                progressText.textContent = '0%';
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>