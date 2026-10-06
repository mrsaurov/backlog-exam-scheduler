@extends('layouts.master')
 
@section('title', 'Manage exams')
 

@section('content')
<div class="page-narrow">
    <div class="toolbar">
        <div class="page-head mb-0">
            <h1>Exams</h1>
            <p>Open an exam to manage its students, schedule, notices, teachers and mail.</p>
        </div>
        <a class="btn btn-primary" href="/exams/0"><i class="bi bi-plus-lg"></i> New exam</a>
    </div>

    @if($exams && $exams->count() > 0) 
    @php
        $today = date('Y-m-d');
        $groups = [
            'Open for registration' => $exams->filter(function($exam) use ($today) { return $exam->deadline >= $today; }),
            'Closed' => $exams->filter(function($exam) use ($today) { return $exam->deadline < $today; }),
        ];
    @endphp
    @foreach($groups as $groupTitle => $groupExams)
    @if($groupExams->count() > 0)
    <h2 class="exam-group-title">{{ $groupTitle }}</h2>
    @foreach($groupExams as $exam)
    @php
        $deadline = \Carbon\Carbon::parse($exam->deadline);
        $open = $exam->deadline >= $today;
    @endphp
    <article class="exam-slip {{ $open ? '' : 'is-closed' }}">
        <div class="exam-date">
            <span class="exam-date-label">{{ $open ? 'Closes' : 'Closed' }}</span>
            <span class="exam-date-day">{{ $deadline->format('j') }}</span>
            <span class="exam-date-month">{{ $deadline->format('M Y') }}</span>
        </div>
        <div class="exam-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h3 class="exam-title"><a href="/students/{{$exam->id}}">{{$exam->exam_name}}</a></h3>
                <p class="exam-meta">{{$exam->department}}, series {{$exam->series}}</p>
                <p class="exam-meta num">
                    {{$exam->student_count}} registered, {{$exam->verified_count}} verified.
                    @if($exam->notice_count > 0)
                        {{$exam->notice_count}} {{ $exam->notice_count == 1 ? 'notice' : 'notices' }} ({{$exam->active_notice_count}} active).
                    @endif
                </p>
            </div>
            <a href="/students/{{$exam->id}}" class="btn btn-quiet">Manage <i class="bi bi-chevron-right"></i></a>
        </div>
    </article>
    @endforeach
    @endif
    @endforeach
    @else
    <div class="empty-state">
        <h2>No exams yet</h2>
        <p>Create the first exam to open registration for students.</p>
        <a class="btn btn-primary" href="/exams/0"><i class="bi bi-plus-lg"></i> New exam</a>
    </div>
    @endif
</div>
@stop
