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
                <button type="button" class="btn btn-success mb-1" data-toggle="modal" data-target="#downloadModal{{$exam->id}}">
                    <i class="fas fa-download"></i> Download Application
                </button>
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
                <button type="button" class="btn btn-success ml-2" data-toggle="modal" data-target="#downloadModal{{$exam->id}}">
                    <i class="fas fa-download"></i> Download Application
                </button>
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
    
    <!-- Download Application Modals for each exam -->
    @foreach($exams as $exam)
    <div class="modal fade" id="downloadModal{{$exam->id}}" tabindex="-1" role="dialog" aria-labelledby="downloadModalLabel{{$exam->id}}" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="downloadModalLabel{{$exam->id}}">Download Application - {{$exam->exam_name}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="/check-registration/{{$exam->id}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="downloadRoll{{$exam->id}}">Enter your Roll Number:</label>
                            <input type="number" class="form-control" id="downloadRoll{{$exam->id}}" name="roll" min="1" placeholder="Enter your roll number" required>
                            <small class="form-text text-muted">Enter the roll number you used during registration to download your application form for {{$exam->exam_name}}.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download"></i> Download Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
    @else
    <h2>No scheduled exams found. Please comeback later</h2>
    @endif

@stop