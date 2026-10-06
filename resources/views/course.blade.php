@extends('layouts.exam')
 
@section('title', $course->id==0 ? 'New course' : 'Edit course')
@section('tab', 'settings')

@section('exam-content')
<div class="page-form">
    <div class="subpage-head">
        <a href="/exams/{{$examid}}" class="back-link"><i class="bi bi-arrow-left"></i> {{ !empty($exam) ? 'Back to settings' : 'Back to the new exam' }}</a>
        <h2>{{ $course->id==0 ? 'New course' : 'Edit course' }}</h2>
    </div>

    <form action="/course" method="POST">
        @csrf
        <input type="hidden" name="examid" value="{{$examid}}"/>
        <input type="hidden" name="id" value="{{$course->id}}"/>
        <div class="panel">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="course_code" class="form-label">Course code</label>
                    <input type="text" name="course_code" class="form-control" id="course_code" placeholder="ex: CSE 1101" value="{{$course->course_code}}" required>
                </div>
                <div class="col-md-8">
                    <label for="course_title" class="form-label">Course title</label>
                    <input type="text" name="course_title" class="form-control" id="course_title" value="{{$course->course_title}}" required>
                </div>
                <div class="col-md-8">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" id="department" placeholder="ex: CSE" value="{{$course->department}}" required>
                </div>
                <div class="col-md-4">
                    <label for="year" class="form-label">Year</label>
                    <input type="text" name="year" class="form-control" id="year" placeholder="ex: 1" value="{{$course->year}}" required>
                </div>
            </div>
        </div>
        <div class="form-actions">
            @if($course->id==0)
                <button type="submit" name="submit" value="create" class="btn btn-primary">Add course</button>
                <a href="/exams/{{$examid}}" class="btn btn-quiet">Cancel</a>
            @else
                <button type="submit" name="submit" value="update" class="btn btn-primary">Save changes</button>
                <a href="/exams/{{$examid}}" class="btn btn-quiet">Cancel</a>
                @if($registrationCount > 0)
                    <span class="toolbar-note push-end">Included in {{$registrationCount}} student {{ $registrationCount == 1 ? 'registration' : 'registrations' }}, so it cannot be deleted.</span>
                @else
                <button type="submit" name="submit" value="delete" class="btn btn-danger-quiet push-end" formnovalidate
                        data-confirm="{{$course->course_code}} will be removed from the course list of every exam. This cannot be undone."
                        data-confirm-title="Delete this course?"
                        data-confirm-button="Delete course">Delete course</button>
                @endif
            @endif
        </div>
    </form>
</div>
@endsection
