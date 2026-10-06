@php
    $exam = $template->exam;
@endphp
@extends('layouts.exam')

@section('title', 'Template preview')
@section('tab', 'mail')

@section('exam-content')
<div class="subpage-head">
    <a href="/mail/{{$template->exam_id}}" class="back-link"><i class="bi bi-arrow-left"></i> Back to mail</a>
    <div class="toolbar mb-0">
        <div>
            <div class="workspace-title">
                <h2>{{$template->name}}</h2>
                @if($template->type === 'general')
                    <span class="status status-accent">General</span>
                @else
                    <span class="status status-neutral">Customized</span>
                @endif
            </div>
            <p class="section-note num">
                Created {{$template->created_at->format('j M Y, g:i A')}}@if($template->updated_at != $template->created_at), last changed {{$template->updated_at->format('j M Y, g:i A')}}@endif.
            </p>
        </div>
        <div class="toolbar-group">
            @if($template->type === 'general')
                <a href="/mail/{{$template->exam_id}}?use={{$template->id}}" class="btn btn-primary">
                    <i class="bi bi-send"></i> Use in a general mail
                </a>
            @endif
            <a href="/mail/{{$template->exam_id}}/edit/{{$template->id}}" class="btn btn-quiet">
                <i class="bi bi-pencil"></i> Edit template
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Mail Content Preview -->
    <div class="col-lg-8">
        <div class="panel">
            <h3 class="panel-title">Email preview</h3>
            <div class="mail-preview mail-preview-tall">
                <p class="mail-preview-subject">{{$template->subject}}</p>
                {!! nl2br(e($template->content)) !!}
            </div>

            @if($template->type === 'customized' && $template->assigned_courses)
                <p class="form-label mt-3 mb-1">Courses</p>
                <div class="tag-list">
                    @foreach($template->assigned_courses as $courseId)
                        @php
                            $course = App\Models\Course::find($courseId);
                        @endphp
                        @if($course)
                            <span class="tag course-chip" data-bs-toggle="tooltip" title="{{$course->course_title}}">{{$course->course_code}}</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Recipients List -->
    <div class="col-lg-4">
        <div class="panel">
            <h3 class="panel-title">Recipients <span class="text-faint fw-normal num">{{$recipients->count()}}</span></h3>
            @if($recipients->count() > 0)
                <ul class="person-list">
                    @foreach($recipients as $recipient)
                        <li>
                            <div class="fw-semibold">{{$recipient->name}}</div>
                            <div class="text-muted small">{{ucwords(str_replace('_', ' ', $recipient->designation))}}</div>
                            <div class="small">{{$recipient->email}}</div>

                            @if($template->type === 'customized' && $recipient->courseAssignments->count() > 0)
                                <div class="tag-list mt-1">
                                    @foreach($recipient->courseAssignments as $assignment)
                                        <span class="tag course-chip" data-bs-toggle="tooltip" title="{{$assignment->course->course_title}}">{{$assignment->course->course_code}}</span>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted mb-0">
                    No one would receive this yet.
                    @if($template->type === 'general')
                        Assign teachers to courses in this exam first.
                    @else
                        Assign teachers to the selected courses first.
                    @endif
                </p>
            @endif
        </div>

        @if($template->type === 'customized')
            <div class="callout mt-3">
                Saved templates for specific courses cannot be sent from here yet. To email course teachers now, use one of the ready-made requests on the Mail page.
            </div>
        @endif
    </div>
</div>
@endsection
