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
        </style>
        @yield('scripts')
    </head>
    <body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div>
            <img src="/images/RUET_logo.svg" height="64px" width="64px"/>
            <span>Backlog Registration</span>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="/">Home <span class="sr-only">(current)</span></a>
            </li>
            @if(!\Illuminate\Support\Facades\Session::get('name'))
            <li class="nav-item">
                <a class="nav-link" href="/login">Login</a>
            </li>
            @else
            <li class="nav-item">
                <a class="nav-link" href="/admin">Admin Panel</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/exams/0">Create Exam</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/logout">Logout</a>
            </li>
            @endif
            </ul>
            
        </div>
    </nav>
    <br>
    <br>

        <div class="container">
            @yield('content')
        </div>
    </body>
</html>