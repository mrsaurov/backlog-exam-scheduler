@extends('layouts.master')
 
@section('title', 'Homepage')
 

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {!! session('success') !!}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('error') !!}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(count($exams) > 0) 
    @foreach($exams as $exam)
    <div class="card">
        <h5 class="card-header">{{$exam->exam_name}}</h5>
        <div class="card-body">
            <h5 class="card-title">Department: {{$exam->department}}</h5>
            <div class="row mb-2">
                <div class="col-sm-6">
                    <strong>Series:</strong> {{$exam->series}}
                </div>
                <div class="col-sm-6">
                    <strong>Deadline:</strong> 
                    <span class="{{ $exam->registration_open ? 'text-danger' : 'text-muted' }}">
                        {{$exam->deadline}}
                    </span>
                    @if(!$exam->registration_open)
                        <small class="text-muted">(Registration Closed)</small>
                    @endif
                </div>
            </div>
            @if($exam->notice_count > 0)
                <div class="alert alert-info py-2 mb-3">
                    <i class="fas fa-bullhorn"></i> <strong>{{$exam->notice_count}}</strong> notice(s) available for this exam
                </div>
            @endif
            <div class="btn-group-vertical d-block d-md-none mb-2">
                @if($exam->registration_open)
                    <a href="/register/{{$exam->id}}" class="btn btn-primary mb-1">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                @else
                    <div class="alert alert-warning py-2 mb-1">
                        <i class="fas fa-clock"></i> Registration deadline has passed
                    </div>
                @endif
                <a href="/exam/{{$exam->id}}/notices" class="btn btn-info">
                    <i class="fas fa-bullhorn"></i> View Notices
                    @if($exam->notice_count > 0)
                        <span class="badge badge-light ml-1">{{$exam->notice_count}}</span>
                    @endif
                </a>
            </div>
            <div class="d-none d-md-block">
                @if($exam->registration_open)
                    <a href="/register/{{$exam->id}}" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                @else
                    <div class="alert alert-warning py-2 d-inline-block mr-2">
                        <i class="fas fa-clock"></i> Registration deadline has passed
                    </div>
                @endif
                <a href="/exam/{{$exam->id}}/notices" class="btn btn-info ml-2">
                    <i class="fas fa-bullhorn"></i> View Notices
                    @if($exam->notice_count > 0)
                        <span class="badge badge-light ml-1">{{$exam->notice_count}}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
    <br>
    <br>
    @endforeach
    @else
    <h2>No scheduled exams found. Please comeback later</h2>
    @endif

@stop