@extends('layouts.master')
 
@section('title', 'Schedule')
 

@section('content')
<div>
    Courses:
    <table class="table">
        <thead>
            <td>Course</td>
            <td>No. of Student(s)</td>
            <td>Student Rolls (Ascending Order)</td>
        </thead>
        <tbody>
            @foreach($vertexcount as $v=>$count)
                <tr>
                    <td>{{$coursemap[$v]}}</td>
                    <td>{{$count}}</td>
                    <td>
                        @if(isset($courseStudents[$v]))
                            {{ implode(', ', $courseStudents[$v]) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<br>
<br>
<div>
    Dependencies on courses:
    <table class="table">
        <thead>
                <td>Course</td>
                <td>Dependencies</td>
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
<div>
    <br>
    <br>
Day wise exam:
<table class="table">
    <thead>
        <td>Date no.</td>
        <td>Exam(s)</td>
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

@endsection