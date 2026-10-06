{{--
    Admin workspace for one exam: exam name, status and section tabs.

    Child views set   @section('tab', 'students|schedule|notices|teachers|mail|settings')
    and put their page in   @section('exam-content').

    When there is no saved exam yet ($exam missing or id 0) only a plain heading is shown.
--}}
@extends('layouts.master')

@section('content')
@php
    $workspaceExam = (isset($exam) && $exam && !empty($exam->id)) ? $exam : null;
    $workspaceTab = trim($__env->yieldContent('tab'));
    $workspaceTabs = $workspaceExam ? [
        'students' => ['Students', '/students/' . $workspaceExam->id],
        'schedule' => ['Schedule', '/schedule/' . $workspaceExam->id],
        'notices' => ['Notices', '/notices/' . $workspaceExam->id],
        'teachers' => ['Teachers', '/teachers/' . $workspaceExam->id],
        'mail' => ['Mail', '/mail/' . $workspaceExam->id],
        'settings' => ['Settings', '/exams/' . $workspaceExam->id],
    ] : [];
@endphp

<div class="workspace-head">
    <a href="/admin" class="back-link"><i class="bi bi-arrow-left"></i> All exams</a>
    @if($workspaceExam)
        @php
            $workspaceDeadline = \Carbon\Carbon::parse($workspaceExam->deadline);
            $workspaceOpen = $workspaceExam->deadline >= date('Y-m-d');
            $workspaceVisible = $workspaceExam->is_visible ?? true;
        @endphp
        <div class="workspace-title">
            <h1>{{ $workspaceExam->exam_name }}</h1>
            @if(!$workspaceVisible)
                <span class="status status-warning">Hidden</span>
            @else
                <span class="status {{ $workspaceOpen ? 'status-open' : 'status-closed' }}">{{ $workspaceOpen ? 'Open' : 'Closed' }}</span>
            @endif
        </div>
        <p class="workspace-meta">
            {{ $workspaceExam->department }}, series {{ $workspaceExam->series }}.
            Registration {{ $workspaceOpen ? 'closes' : 'closed' }} on {{ $workspaceDeadline->format('j M Y') }}.
            @if(!$workspaceVisible)
                Hidden from students.
            @endif
        </p>
    @else
        <div class="workspace-title">
            <h1>@yield('heading', 'New exam')</h1>
        </div>
    @endif
</div>

@if($workspaceExam)
<nav class="workspace-tabs" aria-label="Exam sections">
    @foreach($workspaceTabs as $key => $tab)
        <a href="{{ $tab[1] }}" class="workspace-tab {{ $workspaceTab === $key ? 'is-active' : '' }}" @if($workspaceTab === $key) aria-current="page" @endif>{{ $tab[0] }}</a>
    @endforeach
</nav>
@endif

@yield('exam-content')
@endsection
