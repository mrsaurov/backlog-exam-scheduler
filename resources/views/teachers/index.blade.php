@extends('layouts.exam')

@section('title', 'Teachers')
@section('tab', 'teachers')

@section('exam-content')
@php
    $teachersByDepartment = $teachers->groupBy('department');
    $assignedTeachers = $teachers->filter(function($teacher) {
        return $teacher->courseAssignments->count() > 0;
    })->count();
    $assignedCourses = $assignments->pluck('course_id')->unique()->count();
    $designations = ['Professor', 'Associate Professor', 'Assistant Professor', 'Lecturer'];
    $departments = ['CSE', 'EEE', 'Mathematics', 'Physics', 'Chemistry', 'Humanities'];
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>The teacher was not saved.</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="toolbar">
    <div class="segmented" id="teacherTabs" role="tablist" aria-label="Teacher views">
        <button type="button" class="active" id="teachers-tab" data-bs-toggle="tab" data-bs-target="#teachers" role="tab" aria-controls="teachers" aria-selected="true">Teachers</button>
        <button type="button" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" role="tab" aria-controls="assignments" aria-selected="false">Course assignments</button>
    </div>
    <button type="button" class="btn btn-primary" id="addTeacherBtn"><i class="bi bi-plus-lg"></i> Add teacher</button>
</div>

<div class="tab-content" id="teacherTabsContent">
    <!-- Teachers Tab -->
    <div class="tab-pane fade show active" id="teachers" role="tabpanel" aria-labelledby="teachers-tab" tabindex="0">
        @if($teachers->count() > 0)
        <div class="panel panel-flush">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Teachers</h2>
                    <p class="section-note num">{{$teachers->count()}} {{ $teachers->count() == 1 ? 'teacher' : 'teachers' }}, {{$assignedTeachers}} with courses in this exam.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-clean table-wide">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Assigned courses</th>
                            <th class="cell-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teachersByDepartment as $department => $deptTeachers)
                            <tr class="table-group">
                                <th colspan="5">{{ $department ?: 'No department' }}</th>
                            </tr>
                            @foreach($deptTeachers as $teacher)
                            <tr>
                                <td>
                                    <span class="cell-main">{{$teacher->name}}</span>
                                    <span class="cell-sub">{{ucwords(str_replace('_', ' ', $teacher->designation))}}</span>
                                </td>
                                <td>{{$teacher->email}}</td>
                                <td class="num">
                                    @if($teacher->phone)
                                        {{$teacher->phone}}
                                    @else
                                        <span class="text-faint">Not given</span>
                                    @endif
                                </td>
                                <td>
                                    @if($teacher->courseAssignments->count() > 0)
                                        <div class="tag-list">
                                            @foreach($teacher->courseAssignments as $assignment)
                                                <span class="tag" title="{{$assignment->course->course_title}}">{{$assignment->course->course_code}}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-faint">None</span>
                                    @endif
                                </td>
                                <td class="cell-actions">
                                    <button type="button" class="btn-icon edit-teacher"
                                            data-teacher-id="{{$teacher->id}}"
                                            data-name="{{$teacher->name}}"
                                            data-email="{{$teacher->email}}"
                                            data-phone="{{$teacher->phone}}"
                                            data-designation="{{$teacher->designation}}"
                                            data-department="{{$teacher->department}}"
                                            title="Edit teacher" aria-label="Edit teacher">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn-icon is-danger delete-teacher"
                                            data-teacher-id="{{$teacher->id}}"
                                            data-teacher-name="{{$teacher->name}}"
                                            data-assignments-count="{{$teacher->courseAssignments->count()}}"
                                            title="Delete teacher" aria-label="Delete teacher">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="empty-state">
            <h2>No teachers yet</h2>
            <p>Add teachers so they can be assigned to courses and receive mail for this exam.</p>
        </div>
        @endif
    </div>

    <!-- Course Assignments Tab -->
    <div class="tab-pane fade" id="assignments" role="tabpanel" aria-labelledby="assignments-tab" tabindex="0">
        <div id="assignment-alerts"></div>
        @if($courses->count() > 0)
        <div class="panel panel-flush">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Course assignments</h2>
                    <p class="section-note num">{{$assignedCourses}} of {{$courses->count()}} courses have a teacher. Up to two teachers per course; only courses with verified students are listed.</p>
                </div>
                <a href="/export/teacher-assignments/{{$exam->id}}" class="btn btn-quiet btn-sm">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-clean table-wide">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Teacher 1</th>
                            <th>Teacher 2</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $course)
                            @php
                                $courseTeachers = $assignments->where('course_id', $course->id)->sortBy('id');
                                $teacher1 = $courseTeachers->first();
                                $teacher2 = $courseTeachers->skip(1)->first();
                            @endphp
                            <tr>
                                <td>
                                    <span class="cell-main num">{{$course->course_code}}</span>
                                    <span class="cell-sub">{{$course->course_title}}</span>
                                </td>
                                @foreach([1 => $teacher1, 2 => $teacher2] as $position => $assigned)
                                <td>
                                    <select class="form-select form-select-sm teacher-select" style="min-width: 13rem;"
                                            aria-label="Teacher {{$position}} for {{$course->course_code}}"
                                            data-course-id="{{$course->id}}"
                                            data-position="{{$position}}"
                                            data-original="{{$assigned ? $assigned->teacher_id : ''}}">
                                        <option value="">No teacher</option>
                                        @foreach($teachersByDepartment as $department => $deptTeachers)
                                            <optgroup label="{{$department ?: 'No department'}}">
                                                @foreach($deptTeachers as $teacher)
                                                    <option value="{{$teacher->id}}"
                                                            {{$assigned && $assigned->teacher_id == $teacher->id ? 'selected' : ''}}>
                                                        {{$teacher->name}} ({{$teacher->designation}})
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </td>
                                @endforeach
                                <td class="cell-actions">
                                    <span class="assignment-status" data-course-id="{{$course->id}}"></span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sticky-bar" id="assignmentSaveBar" hidden>
            <span class="sticky-note" id="assignmentChangeNote"></span>
            <button type="button" class="btn btn-quiet" id="discardAssignments">Discard</button>
            <button type="button" class="btn btn-primary" id="saveAllAssignments">Save changes</button>
        </div>
        @else
        <div class="empty-state">
            <h2>No courses to assign yet</h2>
            <p>A course appears here once at least one verified student has registered for it.</p>
        </div>
        @endif
    </div>
</div>

<!-- Add / Edit Teacher Modal -->
<div class="modal fade" id="teacherModal" tabindex="-1" aria-labelledby="teacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="teacherModalLabel">Add teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="teacherForm" method="POST" action="/teachers">
                @csrf
                <input type="hidden" name="submit" id="teacher_submit" value="create">
                <input type="hidden" name="exam_id" value="{{$exam->id}}">
                <input type="hidden" name="teacher_id" id="teacher_id">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="teacher_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="teacher_name" name="name" maxlength="255" required>
                        </div>
                        <div class="col-12">
                            <label for="teacher_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="teacher_email" name="email" maxlength="255" required>
                            <div class="form-hint">Exam mail for this teacher's courses is sent to this address.</div>
                        </div>
                        <div class="col-12">
                            <label for="teacher_phone" class="form-label">Phone <span class="optional">(optional)</span></label>
                            <input type="text" class="form-control num" id="teacher_phone" name="phone" maxlength="20">
                        </div>
                        <div class="col-sm-6">
                            <label for="teacher_designation" class="form-label">Designation</label>
                            <select class="form-select" id="teacher_designation" name="designation" required>
                                <option value="">Choose</option>
                                @foreach($designations as $designation)
                                    <option value="{{$designation}}">{{$designation}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="teacher_department" class="form-label">Department</label>
                            <select class="form-select" id="teacher_department" name="department" required>
                                <option value="">Choose</option>
                                @foreach($departments as $department)
                                    <option value="{{$department}}">{{$department}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-quiet" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="teacherSubmitBtn">Add teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for deleting a teacher -->
<form id="deleteTeacherForm" method="POST" action="/teachers" hidden>
    @csrf
    <input type="hidden" name="submit" value="delete">
    <input type="hidden" name="exam_id" value="{{$exam->id}}">
    <input type="hidden" name="teacher_id" id="delete_teacher_id">
</form>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // ---- Add / edit / delete teacher ----
        const teacherModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('teacherModal'));

        function openTeacherModal(teacher) {
            const editing = !!teacher;

            $('#teacherModalLabel').text(editing ? 'Edit teacher' : 'Add teacher');
            $('#teacherSubmitBtn').text(editing ? 'Save changes' : 'Add teacher');
            $('#teacher_submit').val(editing ? 'update' : 'create');
            $('#teacher_id').val(editing ? teacher.id : '');
            $('#teacher_name').val(editing ? teacher.name : '');
            $('#teacher_email').val(editing ? teacher.email : '');
            $('#teacher_phone').val(editing ? (teacher.phone || '') : '');
            $('#teacher_designation').val(editing ? teacher.designation : '');
            $('#teacher_department').val(editing ? (teacher.department || '') : '');

            teacherModal.show();
        }

        $('#addTeacherBtn').on('click', function() {
            openTeacherModal(null);
        });

        // Handle edit teacher button
        $('.edit-teacher').on('click', function() {
            openTeacherModal({
                id: $(this).data('teacher-id'),
                name: $(this).data('name'),
                email: $(this).data('email'),
                phone: $(this).data('phone'),
                designation: $(this).data('designation'),
                department: $(this).data('department')
            });
        });

        // Handle delete teacher button
        $('.delete-teacher').on('click', function() {
            const teacherId = $(this).data('teacher-id');
            const teacherName = $(this).data('teacher-name');
            const assignmentsCount = $(this).data('assignments-count');

            let message = teacherName + ' will be removed from the teacher list for every exam.';
            if (assignmentsCount > 0) {
                message += ' Their ' + assignmentsCount + ' course ' + (assignmentsCount == 1 ? 'assignment' : 'assignments') + ' in this exam will be removed too.';
            }
            message += ' This cannot be undone.';

            confirmAction({
                title: 'Delete this teacher?',
                message: message,
                confirmLabel: 'Delete teacher',
                danger: true
            }, function() {
                $('#delete_teacher_id').val(teacherId);
                submitForm(document.getElementById('deleteTeacherForm'));
            });
        });

        // ---- Keep the open view across reloads ----
        document.querySelectorAll('#teacherTabs [data-bs-toggle="tab"]').forEach(function(tabButton) {
            tabButton.addEventListener('shown.bs.tab', function() {
                history.replaceState(null, '', tabButton.dataset.bsTarget === '#assignments' ? '#assignments' : location.pathname);
            });
        });

        function showViewFromHash() {
            const tabId = location.hash === '#assignments' ? 'assignments-tab' : 'teachers-tab';
            bootstrap.Tab.getOrCreateInstance(document.getElementById(tabId)).show();
        }

        if (location.hash === '#assignments') {
            showViewFromHash();
        }
        window.addEventListener('hashchange', showViewFromHash);

        // ---- Course assignments ----
        function changedSelects() {
            return $('.teacher-select').filter(function() {
                return $(this).val() != $(this).data('original');
            });
        }

        // Update course status indicator
        function updateCourseStatus(courseId) {
            const statusEl = $(`.assignment-status[data-course-id="${courseId}"]`);
            const hasChanges = $(`.teacher-select[data-course-id="${courseId}"]`).filter(function() {
                return $(this).val() != $(this).data('original');
            }).length > 0;

            statusEl.html(hasChanges ? '<span class="status status-warning">Not saved</span>' : '');
        }

        // Show the save bar while there are unsaved changes
        function updateSaveBar() {
            const count = changedSelects().length;
            $('#assignmentChangeNote').text(count + (count === 1 ? ' change' : ' changes') + ' not saved');
            $('#assignmentSaveBar').prop('hidden', count === 0);
        }

        // Validate that same teacher isn't selected for both positions
        function validateTeacherSelections(courseId) {
            const second = $(`.teacher-select[data-course-id="${courseId}"][data-position="2"]`);
            const teacher1 = $(`.teacher-select[data-course-id="${courseId}"][data-position="1"]`).val();
            const teacher2 = second.val();

            if (teacher1 && teacher2 && teacher1 === teacher2) {
                showAlert('error', 'Same teacher cannot be assigned to both positions for the same course!');
                // Reset the second selection
                second.val('');
            }
        }

        // Handle teacher selection change
        $('.teacher-select').on('change', function() {
            const courseId = $(this).data('course-id');

            validateTeacherSelections(courseId);
            updateCourseStatus(courseId);
            updateSaveBar();
        });

        // Prevent selection of same teacher for both positions in same course
        $('.teacher-select').on('focus', function() {
            const courseId = $(this).data('course-id');
            const currentPosition = $(this).data('position');
            const otherPosition = currentPosition == 1 ? 2 : 1;
            const otherSelectedTeacher = $(`.teacher-select[data-course-id="${courseId}"][data-position="${otherPosition}"]`).val();

            // Enable all options first
            $(this).find('option').prop('disabled', false);

            // Disable the option that's selected in the other position
            if (otherSelectedTeacher) {
                $(this).find(`option[value="${otherSelectedTeacher}"]`).prop('disabled', true);
            }
        });

        $('#discardAssignments').on('click', function() {
            $('.teacher-select').each(function() {
                $(this).val($(this).data('original'));
                updateCourseStatus($(this).data('course-id'));
            });
            updateSaveBar();
        });

        // Handle save all assignments
        $('#saveAllAssignments').on('click', function() {
            const saveBtn = $(this);
            const originalText = saveBtn.text();

            // Collect all changes
            const changes = [];
            changedSelects().each(function() {
                changes.push({
                    course_id: $(this).data('course-id'),
                    position: $(this).data('position'),
                    teacher_id: $(this).val()
                });
            });

            if (changes.length === 0) {
                showAlert('info', 'No changes to save.');
                return;
            }

            // Show loading state
            saveBtn.prop('disabled', true).text('Saving...');

            saveAllChanges(changes).then(function(success) {
                if (success) {
                    showAlert('success', 'All assignments saved successfully!');

                    // Reload page to refresh data
                    setTimeout(() => {
                        location.reload();
                    }, 1200);
                } else {
                    saveBtn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Save all changes function
        async function saveAllChanges(changes) {
            for (let change of changes) {
                try {
                    const response = await $.ajax({
                        url: '/teachers/assign-teacher',
                        method: 'POST',
                        data: {
                            exam_id: {{ $exam->id }},
                            course_id: change.course_id,
                            teacher_id: change.teacher_id,
                            position: change.position,
                            _token: '{{ csrf_token() }}'
                        }
                    });

                    if (!response.success) {
                        showAlert('error', `Error saving course assignment: ${response.message}`);
                        return false;
                    }
                } catch (error) {
                    showAlert('error', 'Error saving assignment. Please try again.');
                    return false;
                }
            }

            return true;
        }

        // Show alert messages above the assignments table
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' :
                               type === 'error' ? 'alert-danger' : 'alert-info';

            const alertEl = $('<div class="alert alert-dismissible fade show" role="alert"></div>').addClass(alertClass);
            alertEl.text(message);
            alertEl.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');

            $('#assignment-alerts').empty().append(alertEl);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (document.body.contains(alertEl[0])) {
                    bootstrap.Alert.getOrCreateInstance(alertEl[0]).close();
                }
            }, 5000);
        }
    });
</script>
@endsection
