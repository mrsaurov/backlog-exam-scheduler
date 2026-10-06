<!DOCTYPE html>
<!-- Stored in resources/views/layouts/master.blade.php -->
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @if(Session::has('download.in.the.next.request'))
         <meta http-equiv="refresh" content="5;url={{ Session::get('download.in.the.next.request') }}">
        @endif
        <title>@yield('title') - Backlog Registration</title>
        <link rel="icon" href="/images/RUET_logo.svg">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        @php
            // Cache-busting version for the app's own assets; tolerate a missing file instead of failing the page
            $assetVersion = function($path) {
                $file = public_path($path);
                return is_file($file) ? filemtime($file) : '1';
            };
        @endphp
        <link rel="stylesheet" href="/css/app.css?v={{ $assetVersion('css/app.css') }}">
        <script src="/js/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        @yield('styles')
    </head>
    <body>
    @php
        $isAdmin = (bool) \Illuminate\Support\Facades\Session::get('name');
        $inAdminArea = request()->is('admin', 'exams/*', 'courses/*', 'students/*', 'schedule/*', 'notices/*', 'teachers/*', 'mail/*');
    @endphp
    <nav class="navbar navbar-expand-lg site-nav sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/images/RUET_logo.svg" alt="RUET logo">
                <span>
                    <span class="brand-name">Backlog Registration</span>
                    <span class="brand-sub">Department of CSE, RUET</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 mt-2 mt-lg-0">
                    @if(!$isAdmin)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="/">Exams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('login') ? 'active' : '' }}" href="/login">Admin sign in</a>
                    </li>
                    @else
                    <li class="nav-item">
                        <a class="nav-link {{ $inAdminArea ? 'active' : '' }}" href="/admin">Manage exams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="/">Student site</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout">Sign out</a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

        <main class="site-main">
            <div class="container">
                @yield('content')
            </div>
        </main>

        <footer class="site-footer">
            <div class="container">
                Department of Computer Science &amp; Engineering, Rajshahi University of Engineering &amp; Technology
            </div>
        </footer>

        <!-- Shared confirmation / notice dialog (see public/js/app.js) -->
        <div class="modal fade" id="appDialog" tabindex="-1" aria-labelledby="appDialogTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="appDialogTitle" data-dialog-title></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" data-dialog-message></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-quiet" data-bs-dismiss="modal" data-dialog-cancel>Cancel</button>
                        <button type="button" class="btn btn-primary" data-dialog-confirm>Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="/js/app.js?v={{ $assetVersion('js/app.js') }}"></script>
        @yield('scripts')
    </body>
</html>
