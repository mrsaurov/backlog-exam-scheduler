@extends('layouts.master')

@section('title', 'Exams')


@section('content')
<div class="page-narrow">
    @if(session('application'))
        @php $application = session('application'); @endphp
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-3" role="status">
            <div>
                <strong>{{ $application['already'] ? 'You have already registered for this exam.' : 'Registration submitted.' }}</strong>
                Download your application form for {{ $application['exam'] }} and keep a copy.
            </div>
            <a href="{{ $application['url'] }}" class="btn btn-primary btn-sm"><i class="bi bi-download"></i> Download application</a>
        </div>
    @endif

    <div class="page-head">
        <h1>Backlog exams</h1>
        <p>Register for a backlog exam before its deadline, then download your application form.</p>
    </div>

    @if(count($exams) > 0)
    @foreach($exams as $exam)
    @php
        $deadline = \Carbon\Carbon::parse($exam->deadline);
        $daysLeft = \Carbon\Carbon::today()->diffInDays($deadline, false);
    @endphp
    <article class="exam-slip {{ $exam->registration_open ? '' : 'is-closed' }}">
        <div class="exam-date">
            <span class="exam-date-label">{{ $exam->registration_open ? 'Closes' : 'Closed' }}</span>
            <span class="exam-date-day">{{ $deadline->format('j') }}</span>
            <span class="exam-date-month">{{ $deadline->format('M Y') }}</span>
        </div>
        <div class="exam-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <h2 class="exam-title">{{$exam->exam_name}}</h2>
                <span class="status {{ $exam->registration_open ? 'status-open' : 'status-closed' }}">{{ $exam->registration_open ? 'Open' : 'Closed' }}</span>
            </div>
            <p class="exam-meta">{{$exam->department}}, series {{$exam->series}}</p>
            <p class="exam-countdown {{ $exam->registration_open ? '' : 'text-muted' }}">
                @if(!$exam->registration_open)
                    Registration closed on {{ $deadline->format('j M Y') }}. You can still download a submitted application.
                @elseif($daysLeft == 0)
                    Registration closes today.
                @elseif($daysLeft == 1)
                    Registration closes tomorrow.
                @else
                    Registration closes in {{ $daysLeft }} days.
                @endif
            </p>
            <div class="exam-actions">
                @if($exam->registration_open)
                    <a href="/register/{{$exam->id}}" class="btn btn-primary">Register</a>
                @endif
                <button type="button" class="btn btn-quiet" data-bs-toggle="modal" data-bs-target="#downloadModal{{$exam->id}}">
                    <i class="bi bi-download"></i> Download application
                </button>
                <a href="/exam/{{$exam->id}}/notices" class="btn btn-quiet">
                    <i class="bi bi-megaphone"></i> Notices
                    @if($exam->notice_count > 0)
                        <span class="count-pill">{{$exam->notice_count}}</span>
                    @endif
                </a>
            </div>
        </div>
    </article>
    @endforeach

    <!-- Download Application Modals for each exam -->
    @foreach($exams as $exam)
    <div class="modal fade" id="downloadModal{{$exam->id}}" tabindex="-1" aria-labelledby="downloadModalLabel{{$exam->id}}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="downloadModalLabel{{$exam->id}}">Download application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/check-registration/{{$exam->id}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">{{$exam->exam_name}}</p>
                        <label for="downloadRoll{{$exam->id}}" class="form-label">Roll number</label>
                        <input type="number" class="form-control" id="downloadRoll{{$exam->id}}" name="roll" min="1" placeholder="The roll number you registered with" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-quiet" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-download"></i> Download application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
    @else
    <div class="empty-state">
        <h2>No exams are scheduled right now</h2>
        <p class="mb-0">When the department opens registration for a backlog exam, it will be listed here.</p>
    </div>
    @endif
</div>
@stop
