@extends('layouts.master')
 
@section('title', 'Schedule')
 

@section('content')
<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Courses</h5>
            <a href="/export/courses/{{ $examid }}" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export to CSV
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <th>Course</th>
                        <th>Course Title</th>
                        <th>No. of Student(s)</th>
                        <th>Student Rolls</th>
                    </thead>
                    <tbody>
                        @foreach($sortedAllCoursesData as $courseData)
                            <tr>
                                <td>{{$courseData['course_code']}}</td>
                                <td>{{$courseData['course_title']}}</td>
                                <td>{{$courseData['count']}}</td>
                                <td>
                                    @if(isset($courseData['students']) && count($courseData['students']) > 0)
                                        {{ implode(', ', $courseData['students']) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Dependencies on courses</h5>
            <a href="/export/dependencies/{{ $examid }}" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export to CSV
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <th>Course</th>
                        <th>Dependencies</th>
                    </thead>
                    <tbody>
                        @foreach($vertex as $v)
                            <tr>
                                <td>{{$coursemap[$v]}}</td>
                                <td>
                                    @foreach($edge->$v as $e)
                                    {{$coursemap[$e]}}&nbsp;&nbsp;
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Day wise exam</h5>
            <a href="/export/schedule/{{ $examid }}" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export to CSV
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <th>Date no.</th>
                        <th>Exam(s)</th>
                    </thead>
                    <tbody>
                        @foreach($result as $days)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>
                                    @foreach($days as $exam)
                                        {{$coursemap[$exam]}}&nbsp;&nbsp;
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="/admin" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Admin Panel
        </a>
    </div>
</div>

@endsection