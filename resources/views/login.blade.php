@extends('layouts.master')

@section('title', 'Admin sign in')


@section('content')

<div class="signin">
    <div class="panel">
        <img src="/images/RUET_logo.svg" alt="" class="signin-crest">
        <h1 class="h3 text-center mb-1">Admin sign in</h1>
        <p class="text-muted text-center mb-4">For department staff who manage backlog exams.</p>

        @if(!empty($loginFailed))
            <div class="alert alert-danger" role="alert">
                That email and password do not match. Check them and try again.
            </div>
        @endif

        <form action="/login" method="POST">
            @csrf
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" id="email" value="{{ $email ?? '' }}" autocomplete="username" required autofocus>
          </div>
          <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" name="password" id="password" autocomplete="current-password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
    </div>
    <p class="text-center mt-3 mb-0"><a href="/" class="back-link"><i class="bi bi-arrow-left"></i> Back to exams</a></p>
</div>
@stop
