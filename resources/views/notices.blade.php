@extends('layouts.exam')
 
@section('title', 'Notices')
@section('tab', 'notices')
 
@section('exam-content')
@php
    $visibleCount = $notices->where('is_active', true)->count();
@endphp
<div class="toolbar">
    <span class="toolbar-note num">
        @if(count($notices) > 0)
            {{count($notices)}} {{ count($notices) == 1 ? 'notice' : 'notices' }}, {{$visibleCount}} visible to students
        @endif
    </span>
    <a href="/notices/{{$exam->id}}/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New notice</a>
</div>

@if(count($notices) > 0)
        @foreach($notices as $notice)
        <article class="panel notice-item">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h3>{{$notice->title}}</h3>
                    <p class="notice-date">
                        Created {{$notice->created_at->format('j M Y, g:i A')}}
                        @if($notice->updated_at != $notice->created_at)
                            (updated {{$notice->updated_at->format('j M Y, g:i A')}})
                        @endif
                    </p>
                </div>
                <div class="toolbar-group">
                    @if($notice->is_active)
                        <span class="status status-success">Visible to students</span>
                    @else
                        <span class="status status-neutral">Hidden</span>
                    @endif
                    <a href="/notices/{{$exam->id}}/edit/{{$notice->id}}" class="btn-icon" title="Edit notice" aria-label="Edit notice"><i class="bi bi-pencil"></i></a>
                    <form action="/notices" method="POST" class="d-inline"
                          data-confirm="Students will no longer see this notice or its attachment. This cannot be undone."
                          data-confirm-title="Delete this notice?"
                          data-confirm-button="Delete notice">
                        @csrf
                        <input type="hidden" name="notice_id" value="{{$notice->id}}">
                        <input type="hidden" name="exam_id" value="{{$exam->id}}">
                        <button type="submit" name="submit" value="delete" class="btn-icon is-danger" title="Delete notice" aria-label="Delete notice"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
            <div class="notice-content">{!! nl2br(strip_tags($notice->content, '<b><i><br>')) !!}</div>

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
        <p>Post a notice to tell students about dates, rooms or changes for this exam.</p>
        <a href="/notices/{{$exam->id}}/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New notice</a>
    </div>
@endif

@endsection
