@extends('layouts.master')
 
@section('title', 'admin')
 

@section('content')
    
    @if($exams && $exams->count() > 0) 
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
                    <strong>Deadline:</strong> <span class="text-danger">{{$exam->deadline}}</span>
                </div>
            </div>
            @if($exam->notice_count > 0)
                <div class="alert alert-info py-2 mb-3">
                    <i class="fas fa-bell"></i> <strong>{{$exam->notice_count}}</strong> total notices 
                    ({{$exam->active_notice_count}} active)
                </div>
            @endif
            <div class="btn-group-vertical d-block d-md-none mb-2">
                <a href="/exams/{{$exam->id}}" class="btn btn-primary mb-1">Edit/Delete</a>
                <a href="/students/{{$exam->id}}" class="btn btn-primary mb-1">View/Verify Students</a>
                <a href="/schedule/{{$exam->id}}" class="btn btn-primary mb-1">Schedule Exams</a>
                <a href="/notices/{{$exam->id}}" class="btn btn-info mb-1">
                    Manage Notices
                    @if($exam->notice_count > 0)
                        <span class="badge badge-light ml-1">{{$exam->notice_count}}</span>
                    @endif
                </a>
                <a href="/teachers/{{$exam->id}}" class="btn btn-success mb-1">Manage Teachers</a>
                <a href="/mail/{{$exam->id}}" class="btn btn-warning">Manage Mails</a>
            </div>
            <div class="d-none d-md-block">
                <a href="/exams/{{$exam->id}}" class="btn btn-primary">Edit/Delete</a>
                <a href="/students/{{$exam->id}}" class="btn btn-primary">View/Verify Students</a>
                <a href="/schedule/{{$exam->id}}" class="btn btn-primary">Schedule Exams</a>
                <a href="/notices/{{$exam->id}}" class="btn btn-info">
                    Manage Notices
                    @if($exam->notice_count > 0)
                        <span class="badge badge-light ml-1">{{$exam->notice_count}}</span>
                    @endif
                </a>
                <a href="/teachers/{{$exam->id}}" class="btn btn-success">Manage Teachers</a>
                <a href="/mail/{{$exam->id}}" class="btn btn-warning">Manage Mails</a>
            </div>
        </div>
    </div>
    <br>
    <br>
    @endforeach
    @else
    <h2>No exams found.</h2>
    <a class="btn btn-success btn-lg" href="/exams/0">Create New Exam</a>
    @endif
    
    
@stop