@extends('layouts.master')

@section('title', 'Notices')

@section('content')
@php
    $deadline = \Carbon\Carbon::parse($exam->deadline);
    $registrationOpen = $exam->deadline >= date('Y-m-d');
@endphp
<div class="page-narrow">
    <a href="/" class="back-link"><i class="bi bi-arrow-left"></i> All exams</a>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 page-head">
        <div>
            <h1>Notices</h1>
            <p>
                {{$exam->exam_name}}. {{$exam->department}}, series {{$exam->series}}.
                @if($registrationOpen)
                    Registration closes on {{ $deadline->format('j M Y') }}.
                @else
                    Registration closed on {{ $deadline->format('j M Y') }}.
                @endif
            </p>
        </div>
        @if($registrationOpen)
            <a href="/register/{{$exam->id}}" class="btn btn-primary">Register for this exam</a>
        @else
            <span class="status status-closed">Closed</span>
        @endif
    </div>

@if(count($notices) > 0)
        @foreach($notices as $notice)
        <article class="panel notice-item">
            <h2>{{$notice->title}}</h2>
            <p class="notice-date">
                Published {{$notice->created_at->format('j M Y, g:i A')}}
                @if($notice->updated_at != $notice->created_at)
                    (updated {{$notice->updated_at->format('j M Y, g:i A')}})
                @endif
            </p>
            <div class="notice-content">
                {!! nl2br(strip_tags($notice->content, '<b><i><br>')) !!}
            </div>

            @if($notice->file_name)
                <a href="/notice-file/{{$notice->id}}" target="_blank" class="file-chip">
                    <i class="bi bi-paperclip"></i>
                    <span>
                        {{$notice->file_name}}
                        <small class="d-block">
                            {{ strtoupper(pathinfo($notice->file_name, PATHINFO_EXTENSION)) }} file,
                            {{ number_format($notice->file_size / 1024, 1) }} KB
                        </small>
                    </span>
                </a>
            @endif
        </article>
        @endforeach
@else
    <div class="empty-state">
        <h2>No notices yet</h2>
        <p class="mb-0">Nothing has been published for this exam. Check back closer to the exam dates.</p>
    </div>
@endif
</div>

@endsection
