@extends('layouts.exam')
 
@section('title', $new ? 'New exam' : 'Settings')
@section('tab', 'settings')
@section('heading', 'New exam')

@section('exam-content')
@php
    $selectedIds = collect($selected);
    $years = $courses->pluck('year')->unique()->sort()->values();
@endphp
    <form action="/exams" method="POST">
        @csrf
        @if(!$new)
            <input type="hidden" name="exam_id" value="{{$exam->id}}"/>
        @endif

        <div class="panel">
            <h2 class="panel-title">Exam details</h2>
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="exam_name" class="form-label">Exam name</label>
                    <input type="text" name="exam_name" class="form-control" id="exam_name" placeholder="ex: 1st Year Backlog Examination 2020" value="{{$exam->exam_name}}" required>
                </div>
                <div class="col-md-4">
                    <label for="deadline" class="form-label">Registration deadline</label>
                    <input type="date" class="form-control" name="deadline" id="deadline" value="{{$exam->deadline}}" required/>
                    <div class="form-hint">Students can register up to and including this day.</div>
                </div>
                <div class="col-md-8">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" name="department" id="department" placeholder="ex: Computer Science &amp; Engineering" value="{{$exam->department}}" required>
                </div>
                <div class="col-md-4">
                    <label for="series" class="form-label">Series</label>
                    <input type="text" class="form-control" name="series" id="series" placeholder="ex: 19" value="{{$exam->series}}" required>
                </div>
            </div>
        </div>

        <div class="panel panel-flush">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Courses offered</h2>
                    <p class="section-note"><span class="num" data-course-count>0</span> of {{count($courses)}} selected. Students can register only for the selected courses.</p>
                </div>
                <div class="toolbar-group">
                    <div class="search-field">
                        <i class="bi bi-search"></i>
                        <input type="search" id="courseSearch" class="form-control form-control-sm" placeholder="Search code or title" aria-label="Search courses">
                    </div>
                    <select id="courseYear" class="form-select form-select-sm w-auto" aria-label="Filter by year">
                        <option value="">All years</option>
                        @foreach($years as $year)
                            <option value="{{$year}}">Year {{$year}}</option>
                        @endforeach
                    </select>
                    <a class="btn btn-quiet btn-sm" href="/courses/0/{{$exam->id}}"><i class="bi bi-plus-lg"></i> Add course</a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="courseTable" class="table table-clean table-wide">
                    <thead>
                        <tr>
                            <th class="cell-check">
                                <input type="checkbox" class="form-check-input" id="courseSelectAll" aria-label="Select all courses shown">
                            </th>
                            <th>Course code</th>
                            <th>Course title</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Registered</th>
                            <th class="cell-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($courses as $course)
                        @php $registeredCount = $registeredCounts[$course->id] ?? 0; @endphp
                        <tr data-year="{{$course->year}}" data-search="{{ strtolower($course->course_code . ' ' . $course->course_title) }}"
                            data-code="{{$course->course_code}}" data-registered="{{$registeredCount}}">
                            <td class="cell-check">
                                <input type="checkbox" class="form-check-input course-check" name="assignedcourses[]" value="{{$course->id}}" id="course_{{$course->id}}" {{ $selectedIds->contains($course->id) ? 'checked' : '' }} />
                            </td>
                            <td><label for="course_{{$course->id}}" class="cell-main num mb-0">{{$course->course_code}}</label></td>
                            <td>{{$course->course_title}}</td>
                            <td>{{$course->department}}</td>
                            <td class="num">{{$course->year}}</td>
                            <td class="num">
                                @if($registeredCount > 0)
                                    {{$registeredCount}} {{ $registeredCount == 1 ? 'student' : 'students' }}
                                @endif
                            </td>
                            <td class="cell-actions">
                                <a class="btn-icon" href="/courses/{{$course->id}}/{{$exam->id}}" title="Edit course" aria-label="Edit {{$course->course_code}}"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="empty-state" id="courseNoMatch" hidden>
                <h3>No course matches</h3>
                <p>Change the search or the year filter, or add the course.</p>
            </div>
        </div>

        <div class="sticky-bar">
            <span class="sticky-note"><span class="num" data-course-count>0</span> courses selected</span>
            @if($new==true)
                <button type="submit" name="submit" value="create" class="btn btn-primary">Create exam</button>
            @else
                <button type="submit" name="submit" value="update" class="btn btn-primary order-2">Save changes</button>
                <button type="submit" name="submit" value="delete" class="btn btn-danger-quiet order-1" formnovalidate
                        data-confirm="{{$exam->exam_name}} will be removed from the admin panel and the student site. This cannot be undone."
                        data-confirm-title="Delete this exam?"
                        data-confirm-button="Delete exam">Delete exam</button>
            @endif
        </div>
    </form>
@endsection

@section('scripts')
<script>
(function() {
    var rows = Array.prototype.slice.call(document.querySelectorAll('#courseTable tbody tr'));
    var search = document.getElementById('courseSearch');
    var year = document.getElementById('courseYear');
    var selectAll = document.getElementById('courseSelectAll');
    var noMatch = document.getElementById('courseNoMatch');

    function shownRows() {
        return rows.filter(function(row) { return !row.hidden; });
    }

    function refreshCount() {
        var total = document.querySelectorAll('.course-check:checked').length;
        document.querySelectorAll('[data-course-count]').forEach(function(el) {
            el.textContent = total;
        });

        var shown = shownRows();
        var checkedShown = shown.filter(function(row) {
            return row.querySelector('.course-check').checked;
        }).length;
        selectAll.checked = shown.length > 0 && checkedShown === shown.length;
        selectAll.indeterminate = checkedShown > 0 && checkedShown < shown.length;
    }

    function applyFilter() {
        var query = search.value.trim().toLowerCase();
        rows.forEach(function(row) {
            var matchesSearch = !query || row.dataset.search.indexOf(query) !== -1;
            var matchesYear = !year.value || row.dataset.year === year.value;
            row.hidden = !(matchesSearch && matchesYear);
        });
        noMatch.hidden = shownRows().length > 0;
        refreshCount();
    }

    search.addEventListener('input', applyFilter);
    year.addEventListener('change', applyFilter);

    // Enter in the search box should not submit the exam form
    search.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    selectAll.addEventListener('change', function() {
        shownRows().forEach(function(row) {
            row.querySelector('.course-check').checked = selectAll.checked;
        });
        refreshCount();
    });

    document.getElementById('courseTable').addEventListener('change', function(e) {
        if (e.target.classList.contains('course-check')) {
            refreshCount();
        }
    });

    // Removing a course that students already registered for keeps their registrations,
    // but it is easy to do by accident (for example with "select all"), so ask first.
    var saveButton = document.querySelector('button[name="submit"][value="update"]');
    if (saveButton) {
        saveButton.addEventListener('click', function(e) {
            var form = saveButton.form;
            var dropped = rows.filter(function(row) {
                var box = row.querySelector('.course-check');
                return box.defaultChecked && !box.checked && Number(row.dataset.registered) > 0;
            });

            if (dropped.length === 0 || !form.checkValidity()) {
                return;
            }

            e.preventDefault();
            var list = dropped.map(function(row) {
                return row.dataset.code + ' (' + row.dataset.registered + ')';
            }).join(', ');

            confirmAction({
                title: 'Remove courses students registered for?',
                message: 'Students are already registered for: ' + list + '. They keep their registration, but new students will not be able to choose ' + (dropped.length === 1 ? 'this course' : 'these courses') + '.',
                confirmLabel: 'Save anyway',
                danger: false
            }, function() {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'submit';
                hidden.value = 'update';
                form.appendChild(hidden);
                submitForm(form);
            });
        });
    }

    refreshCount();
})();
</script>
@endsection
