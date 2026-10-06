@extends('layouts.exam')
 
@section('title', 'Schedule')
@section('tab', 'schedule')

@section('exam-content')
@if(count($sortedAllCoursesData) > 0)
<div class="toolbar">
    <div class="segmented" role="tablist" aria-label="Schedule views">
        <button type="button" class="active" id="days-tab" data-bs-toggle="tab" data-bs-target="#days-pane" role="tab" aria-controls="days-pane" aria-selected="true">Day-wise schedule</button>
        <button type="button" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses-pane" role="tab" aria-controls="courses-pane" aria-selected="false">Courses and students</button>
        <button type="button" id="clashes-tab" data-bs-toggle="tab" data-bs-target="#clashes-pane" role="tab" aria-controls="clashes-pane" aria-selected="false">Course clashes</button>
    </div>
    <span class="toolbar-note">Built from verified students only.</span>
</div>

<div class="tab-content">
    <div class="tab-pane fade show active" id="days-pane" role="tabpanel" aria-labelledby="days-tab" tabindex="0">
        <div class="panel panel-flush">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Day-wise schedule</h2>
                    <p class="section-note">{{count($result)}} exam {{ count($result) == 1 ? 'day' : 'days' }}. Courses that share a student are never placed on the same day. Only odd-numbered courses are scheduled.</p>
                </div>
                <a href="/export/schedule/{{ $examid }}" class="btn btn-quiet btn-sm">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
            @if(count($result) > 0)
            <div class="table-responsive">
                <table class="table table-clean" style="min-width: 560px;">
                    <thead>
                        <tr>
                            <th>Course code</th>
                            <th>Course title</th>
                            <th>Student rolls</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($result as $days)
                            <tr class="table-group">
                                <th colspan="3">Day {{$loop->iteration}}</th>
                            </tr>
                            @foreach($days as $dayCourse)
                                <tr>
                                    <td class="cell-main num text-nowrap">{{$coursemap[$dayCourse]}}</td>
                                    <td>{{$courseTitles[$dayCourse]}}</td>
                                    <td class="num">
                                        @if(isset($courseStudents[$dayCourse]))
                                            {{ implode(', ', $courseStudents[$dayCourse]) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty-state">
                <h3>No odd-numbered courses to schedule</h3>
                <p>Verified students have only registered for even-numbered courses so far.</p>
            </div>
            @endif
        </div>
    </div>

    <div class="tab-pane fade" id="courses-pane" role="tabpanel" aria-labelledby="courses-tab" tabindex="0">
        <div class="panel panel-flush">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Courses and students</h2>
                    <p class="section-note">Every course that at least one verified student registered for.</p>
                </div>
                <a href="/export/courses/{{ $examid }}" class="btn btn-quiet btn-sm">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-clean" style="min-width: 560px;">
                    <thead>
                        <tr>
                            <th>Course code</th>
                            <th>Course title</th>
                            <th>Students</th>
                            <th>Student rolls</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sortedAllCoursesData as $courseData)
                            <tr>
                                <td class="cell-main num text-nowrap">{{$courseData['course_code']}}</td>
                                <td>{{$courseData['course_title']}}</td>
                                <td class="num">{{$courseData['count']}}</td>
                                <td class="num">
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

    <div class="tab-pane fade" id="clashes-pane" role="tabpanel" aria-labelledby="clashes-tab" tabindex="0">
        <div class="panel panel-flush">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Course clashes</h2>
                    <p class="section-note">Two courses clash when the same student registered for both, so they need different days.</p>
                </div>
                <a href="/export/dependencies/{{ $examid }}" class="btn btn-quiet btn-sm">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-clean" style="min-width: 560px;">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Cannot share a day with</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vertex as $v)
                            <tr>
                                <td class="cell-main num text-nowrap">{{$coursemap[$v]}}</td>
                                <td>
                                    @if(count($edge->$v) > 0)
                                    <div class="tag-list">
                                        @foreach($edge->$v as $e)
                                            <span class="tag">{{$coursemap[$e]}}</span>
                                        @endforeach
                                    </div>
                                    @else
                                        <span class="text-faint">No clashes</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@else
<div class="empty-state">
    <h2>Nothing to schedule yet</h2>
    <p>The schedule is built from verified students. Verify registrations first, then come back here.</p>
    <a href="/students/{{ $examid }}" class="btn btn-primary">Go to students</a>
</div>
@endif
@endsection
