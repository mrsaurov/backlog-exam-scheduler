<!-- Stored in resources/views/layouts/master.blade.php -->
 
<html>
    <head>
        @if(Session::has('download.in.the.next.request'))
         <meta http-equiv="refresh" content="5;url={{ Session::get('download.in.the.next.request') }}">
        @endif
        <title>Backlog Registration - @yield('title')</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
        <script src="/js/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
        <script src="/js/fancyTable.min.js"></script>
        <style>
            /* Reduce modal animation duration */
            .modal.fade .modal-dialog {
                transition: transform 0.15s ease-out, -webkit-transform 0.15s ease-out;
            }
            
            /* Reduce flasher notification animation duration */
            .flasher-notification {
                animation-duration: 0.3s !important;
                transition: all 0.3s ease !important;
            }
            
            .flasher-notification.show {
                animation-duration: 0.3s !important;
            }
            
            .flasher-notification.hide {
                animation-duration: 0.2s !important;
            }

            /* Navbar tweaks */
            .navbar {
                font-weight: 500;
            }
            .navbar-brand img {
                height: 44px;
                width: 44px;
            }
            .navbar-brand .brand-text {
                line-height: 1;
            }
            .navbar-brand .brand-text small {
                font-size: 0.75rem;
            }
            .navbar-nav .nav-link {
                border-radius: .25rem;
                padding: .35rem .75rem;
            }
            .navbar-nav .nav-link:hover {
                background: rgba(0,0,0,.03);
            }
            .navbar-nav .btn {
                padding: .35rem .75rem;
            }
        </style>
        @yield('scripts')
    </head>
    <body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                <img src="/images/RUET_logo.svg" alt="RUET Logo" class="d-inline-block align-top mr-2">
                <span class="brand-text d-flex flex-column">
                    <span class="h5 mb-0 font-weight-bold">Backlog Registration</span>
                    <small class="text-muted d-none d-sm-block">RUET</small>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ml-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    @if(!\Illuminate\Support\Facades\Session::get('name'))
                    <li class="nav-item">
                        <a class="nav-link" href="/login">Login</a>
                    </li>
                    @else
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">Admin Panel</a>
                    </li>
                    <li class="nav-item ml-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-sm btn-primary" href="/exams/0">Create Exam</a>
                    </li>
                    <li class="nav-item ml-lg-2 mt-2 mt-lg-0">
                        <a class="nav-link" href="/logout">Logout</a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
    <br>
    <br>

        <div class="container">
            @yield('content')
        </div>
    </body>
</html>