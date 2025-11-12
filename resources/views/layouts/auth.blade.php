<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="js">

<head>
    <base href="/">
    <meta charset="utf-8">
    <meta name="author" content="Rhymes Platform">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Rhymes Author Platform - Submit your books to Rovingheights for stocking consideration">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Fav Icon -->
    <link rel="shortcut icon" href="./images/favicon.png">
    
    <!-- Page Title -->
    <title>@yield('title', 'Rhymes Author Platform')</title>
    
    <!-- StyleSheets -->
    <link rel="stylesheet" href="./assets/css/dashlite.css?ver=3.2.3">
    <link id="skin-default" rel="stylesheet" href="./assets/css/theme.css?ver=3.2.3">
    
    @stack('styles')
</head>

<body class="nk-body ui-rounder npc-general pg-auth">
    <div class="nk-app-root">
        <!-- main @s -->
        <div class="nk-main">
            <!-- wrap @s -->
            <div class="nk-wrap nk-wrap-nosidebar">
                <!-- content @s -->
                <div class="nk-content">
                    <div class="nk-block nk-block-middle nk-auth-body wide-xs">
                        <!-- Brand Logo -->
                        <div class="brand-logo pb-4 text-center">
                            <a href="{{ route('dashboard') }}" class="logo-link">
                                <img class="logo-light logo-img logo-img-lg" src="./images/logo.png" srcset="./images/logo2x.png 2x" alt="Rhymes Platform">
                                <img class="logo-dark logo-img logo-img-lg" src="./images/logo-dark.png" srcset="./images/logo-dark2x.png 2x" alt="Rhymes Platform">
                            </a>
                        </div>

                        <!-- Auth Card -->
                        <div class="card">
                            <div class="card-inner card-inner-lg">
                                <!-- Page Header -->
                                <div class="nk-block-head">
                                    <div class="nk-block-head-content">
                                        <h4 class="nk-block-title">@yield('page-title')</h4>
                                        <div class="nk-block-des">
                                            <p>@yield('page-description')</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Flash Messages -->
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible">
                                        <button class="close" data-bs-dismiss="alert"></button>
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible">
                                        <button class="close" data-bs-dismiss="alert"></button>
                                        {{ session('error') }}
                                    </div>
                                @endif

                                @if($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Main Content -->
                                @yield('content')

                                <!-- Auth Links -->
                                @hasSection('auth-links')
                                    <div class="form-note-s2 text-center pt-4">
                                        @yield('auth-links')
                                    </div>
                                @endif

                                <!-- Social Login (Optional) -->
                                @hasSection('social-login')
                                    <div class="text-center pt-4 pb-3">
                                        <h6 class="overline-title overline-title-sap"><span>OR</span></h6>
                                    </div>
                                    <ul class="nav justify-center gx-8">
                                        @yield('social-login')
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="nk-footer nk-auth-footer-full">
                        <div class="container wide-lg">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="nk-block-content text-center text-lg-left">
                                        <p class="text-soft">&copy; {{ date('Y') }} Rhymes Platform. All Rights Reserved.</p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="nk-block-content text-center text-lg-right">
                                        <ul class="nk-footer-links">
                                            <li><a href="#">Privacy Policy</a></li>
                                            <li><a href="#">Terms of Service</a></li>
                                            <li><a href="#">Help Center</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- content @e -->
            </div>
            <!-- wrap @e -->
        </div>
        <!-- main @e -->
    </div>
    <!-- app-root @e -->

    <!-- JavaScript -->
    <script src="./assets/js/bundle.js?ver=3.2.3"></script>
    <script src="./assets/js/scripts.js?ver=3.2.3"></script>
    
    @stack('scripts')
</body>

</html>
