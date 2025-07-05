@extends('layouts.master')
 
@section('title', 'admin')
 

@section('content')
    
    @if($exams && $exams->count() > 0) 
    @foreach($exams as $exam)
    <div class="card">
        <h5 class="card-header">{{$exam->exam_name}}</h5>
        <div class="card-body">
            <h5 class="card-title">Department: {{$exam->department}}</h5>
            <p class="card-text">Series: {{$exam->series}}<hr/><span class="text-danger">Deadline: {{$exam->deadline}}</span></p>
            <a href="/exams/{{$exam->id}}" class="btn btn-primary">Edit/Delete</a>
            <a href="/students/{{$exam->id}}" class="btn btn-primary">View/Verify Students</a>
            <a href="/schedule/{{$exam->id}}" class="btn btn-primary">Schedule Exams</a>
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