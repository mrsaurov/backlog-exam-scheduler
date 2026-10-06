@extends('layouts.exam')

@section('title', 'Students')
@section('tab', 'students')

@section('exam-content')
@php
    $verifiedTotal = collect($students)->filter(function($student) { return $student['verified']; })->count();
    $sortedByRoll = isset($currentSort) && $currentSort === 'roll';
    // Registrations that include a course no longer offered in this exam, or a deleted course
    $hasUnofferedCourses = collect($students)->contains(function($student) {
        return collect($student['course_states'])->contains(function($state) { return $state !== 'offered'; });
    });
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>The registration was not saved.</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(count($students) > 0)
<div class="toolbar">
    <div class="toolbar-group">
        <div class="search-field">
            <i class="bi bi-search"></i>
            <input type="search" id="studentSearch" class="form-control form-control-sm" placeholder="Search roll or name" aria-label="Search students">
        </div>
        <span class="toolbar-note num"><span id="verifiedCount">{{$verifiedTotal}}</span> of <span id="totalCount">{{count($students)}}</span> verified</span>
    </div>
    <div class="toolbar-group">
        <div class="segmented" role="group" aria-label="Sort order">
            <a href="/students/{{$exam->id}}" class="{{ $sortedByRoll ? '' : 'is-active' }}">Registration order</a>
            <a href="/students/{{$exam->id}}?sort=roll" class="{{ $sortedByRoll ? 'is-active' : '' }}">Roll number</a>
        </div>
        <button type="button" class="btn btn-quiet btn-sm" id="verifyAllBtn">Verify all</button>
        <button type="button" class="btn btn-quiet btn-sm" id="unverifyAllBtn">Unverify all</button>
    </div>
</div>

@if($hasUnofferedCourses)
    <div class="callout mb-3">
        <strong>Some registrations include courses that are no longer offered in this exam.</strong>
        They are highlighted below and kept as the student submitted them. Edit a registration to move the student to another course.
    </div>
@endif

<form action="/students" method="POST">
    @csrf
    <input type="hidden" name="examid" value="{{$exam->id}}"/>

    <div class="panel panel-flush">
        <div class="table-responsive">
            <table id="studentTable" class="table table-clean table-xwide">
                <thead>
                    <tr>
                        <th>Sl.</th>
                        <th>Roll</th>
                        <th>Name</th>
                        <th>Registration</th>
                        <th>Courses</th>
                        <th>Verified</th>
                        <th class="cell-actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr data-student-id="{{$student['id']}}" data-search="{{ strtolower($student['roll'] . ' ' . $student['name']) }}">
                            <td class="text-faint num">{{$loop->iteration}}</td>
                            <td class="cell-main num">{{$student['roll']}}</td>
                            <td>{{$student['name']}}</td>
                            <td class="num">{{$student['registration']}}</td>
                            <td>
                                <div class="tag-list">
                                    @foreach(['course1', 'course2', 'course3', 'course4', 'course5'] as $courseField)
                                        @if($student[$courseField])
                                            @php
                                                $courseState = $student['course_states'][$courseField] ?? 'offered';
                                                $courseTitle = $student['course_titles'][$courseField] ?? '';
                                            @endphp
                                            @if($courseState === 'removed')
                                                <span class="tag tag-warning course-chip" data-bs-toggle="tooltip" title="{{$courseTitle}} (no longer offered in this exam)">{{$student[$courseField]}}</span>
                                            @elseif($courseState === 'deleted')
                                                <span class="tag tag-warning course-chip" data-bs-toggle="tooltip" title="This course has been deleted">{{$student[$courseField]}}</span>
                                            @else
                                                <span class="tag course-chip" data-bs-toggle="tooltip" title="{{$courseTitle}}">{{$student[$courseField]}}</span>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input verify-check" type="checkbox" role="switch" value="{{$student['id']}}" name="verification[]"
                                           data-initial="{{ $student['verified'] ? '1' : '0' }}" aria-label="Verified"
                                    @if($student["verified"]== true)
                                    checked
                                    @endif
                                    >
                                </div>
                            </td>
                            <td class="cell-actions">
                                <button type="button" class="btn-icon" data-student-action="view" title="View details" aria-label="View details">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn-icon" data-student-action="edit" title="Edit registration" aria-label="Edit registration">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn-icon is-danger" data-student-action="delete" title="Delete registration" aria-label="Delete registration">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="empty-state" id="studentNoMatch" hidden>
            <h3>No student matches</h3>
            <p>Check the roll number or name you typed.</p>
        </div>
    </div>

    <div class="sticky-bar" id="verifySaveBar" hidden>
        <span class="sticky-note" id="verifyChangeNote"></span>
        <button type="button" class="btn btn-quiet" id="discardChangesBtn">Discard</button>
        <button class="btn btn-primary" type="submit" name="submit">Save changes</button>
    </div>
</form>
@else
<div class="empty-state">
    <h2>No one has registered yet</h2>
    <p>Students who register for this exam will be listed here for verification.</p>
</div>
@endif

<!-- Hidden form for delete operations -->
<form id="deleteForm" action="/students/delete" method="POST" hidden>
    @csrf
    @method('DELETE')
    <input type="hidden" name="student_id" id="deleteStudentId">
    <input type="hidden" name="examid" value="{{$exam->id}}">
</form>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editStudentForm" action="/students/edit" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="editStudentIdInput">
                    <input type="hidden" name="examid" value="{{$exam->id}}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editStudentName" class="form-label">Full name</label>
                            <input type="text" class="form-control" id="editStudentName" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentRoll" class="form-label">Roll number</label>
                            <input type="number" class="form-control num" id="editStudentRoll" name="roll" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentRegistration" class="form-label">Registration number</label>
                            <input type="number" class="form-control num" id="editStudentRegistration" name="registration" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentContactNo" class="form-label">Contact No. <span class="optional">(optional)</span></label>
                            <input type="tel" class="form-control num" id="editStudentContactNo" name="contact_no" placeholder="ex: 01712345678" pattern="(\+?88)?01[3-9][0-9]{8}" maxlength="14" title="Enter a valid mobile number, e.g. 01712345678">
                        </div>
                        <div class="col-md-6">
                            <label for="editCourse1" class="form-label">Course 1</label>
                            <select class="form-select" id="editCourse1" name="course1" required>
                                @foreach($courses as $course)
                                    <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}}</option>
                                @endforeach
                                @foreach($removedCourses as $course)
                                    <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}} (no longer offered)</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach([2, 3, 4, 5] as $courseNumber)
                        <div class="col-md-6">
                            <label for="editCourse{{$courseNumber}}" class="form-label">Course {{$courseNumber}} <span class="optional">(optional)</span></label>
                            <select class="form-select" id="editCourse{{$courseNumber}}" name="course{{$courseNumber}}">
                                <option value="0">None</option>
                                @foreach($courses as $course)
                                    <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}}</option>
                                @endforeach
                                @foreach($removedCourses as $course)
                                    <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}} (no longer offered)</option>
                                @endforeach
                            </select>
                        </div>
                        @endforeach
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="editVerified" name="verified" value="1">
                                <label class="form-check-label" for="editVerified">Verified</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-error" id="editStudentError" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-quiet" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentDetailsModalLabel">Student additional information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="facts">
                    <dt>Student</dt>
                    <dd><span id="detailStudentName"></span> <span class="text-muted fw-normal num">(roll <span id="detailStudentRoll"></span>)</span></dd>
                    <dt>Contact No.</dt>
                    <dd id="detailContactNo" class="num"></dd>
                    <dt>Last appeared exam</dt>
                    <dd id="detailLastExam"></dd>
                    <dt>Backlogged subjects</dt>
                    <dd id="detailBackloggedSubjects"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-quiet" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    // Student data from the backend, looked up by ID when a row action is used
    const students = @json($students);
    const studentsById = {};
    students.forEach(function(student) {
        studentsById[student.id] = student;
    });

    const table = document.getElementById('studentTable');
    if (!table) {
        return;
    }

    function viewStudentDetails(student) {
        document.getElementById('detailStudentName').textContent = student.name;
        document.getElementById('detailStudentRoll').textContent = student.roll;
        document.getElementById('detailContactNo').textContent = student.contact_no || 'Not specified';
        document.getElementById('detailLastExam').textContent = student.last_appeared_exam || 'Not specified';
        document.getElementById('detailBackloggedSubjects').textContent = student.backlogged_subjects || 'Not specified';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('studentDetailsModal')).show();
    }

    function deleteStudent(student) {
        confirmAction({
            title: 'Delete this registration?',
            message: student.name + ' (roll ' + student.roll + ') will be removed from this exam. This cannot be undone.',
            confirmLabel: 'Delete registration',
            danger: true
        }, function() {
            document.getElementById('deleteStudentId').value = student.id;
            submitForm(document.getElementById('deleteForm'));
        });
    }

    // A stored course that no longer exists gets a placeholder option, so opening and
    // saving the dialog never swaps it for another course without the admin choosing one.
    function setEditCourse(selectId, courseId, emptyValue) {
        const select = document.getElementById(selectId);
        Array.from(select.querySelectorAll('option[data-placeholder]')).forEach(function(option) {
            option.remove();
        });

        if (courseId && !select.querySelector('option[value="' + courseId + '"]')) {
            const placeholder = new Option('Deleted course (choose another)', courseId);
            placeholder.dataset.placeholder = '1';
            select.add(placeholder);
        }

        select.value = courseId || emptyValue;
    }

    function editStudent(student) {
        // Set form values
        document.getElementById('editStudentIdInput').value = student.id;
        document.getElementById('editStudentName').value = student.name;
        document.getElementById('editStudentRoll').value = student.roll;
        document.getElementById('editStudentRegistration').value = student.registration;
        document.getElementById('editStudentContactNo').value = student.contact_no || '';
        document.getElementById('editVerified').checked = !!Number(student.verified);

        // Set course selections using the stored course IDs
        setEditCourse('editCourse1', student.course1_id, '');
        setEditCourse('editCourse2', student.course2_id, '0');
        setEditCourse('editCourse3', student.course3_id, '0');
        setEditCourse('editCourse4', student.course4_id, '0');
        setEditCourse('editCourse5', student.course5_id, '0');

        showEditError('');
        updateEditAvailableOptions();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editStudentModal')).show();
    }

    table.addEventListener('click', function(e) {
        const button = e.target.closest('[data-student-action]');
        if (!button) {
            return;
        }

        const student = studentsById[button.closest('tr').dataset.studentId];
        if (!student) {
            return;
        }

        if (button.dataset.studentAction === 'view') {
            viewStudentDetails(student);
        } else if (button.dataset.studentAction === 'edit') {
            editStudent(student);
        } else if (button.dataset.studentAction === 'delete') {
            deleteStudent(student);
        }
    });

    // Course duplicate prevention for edit form
    const editCourseSelects = ['editCourse1', 'editCourse2', 'editCourse3', 'editCourse4', 'editCourse5'];

    function updateEditAvailableOptions() {
        const selectedValues = [];

        editCourseSelects.forEach(function(selectId) {
            const value = document.getElementById(selectId).value;
            if (value !== '0') {
                selectedValues.push(value);
            }
        });

        editCourseSelects.forEach(function(selectId) {
            const select = document.getElementById(selectId);
            const currentValue = select.value;

            Array.from(select.options).forEach(function(option) {
                option.disabled = option.value !== currentValue && selectedValues.includes(option.value);
            });
        });
    }

    editCourseSelects.forEach(function(selectId) {
        document.getElementById(selectId).addEventListener('change', updateEditAvailableOptions);
    });

    function showEditError(message) {
        const error = document.getElementById('editStudentError');
        error.textContent = message;
        error.classList.toggle('is-shown', !!message);
    }

    // Form validation for edit form
    document.getElementById('editStudentForm').addEventListener('submit', function(e) {
        const rollInput = document.getElementById('editStudentRoll');
        const registrationInput = document.getElementById('editStudentRegistration');

        if (!(parseInt(rollInput.value) >= 1)) {
            e.preventDefault();
            showEditError('Roll number must be a positive integer.');
            rollInput.focus();
            return;
        }

        if (!(parseInt(registrationInput.value) >= 1)) {
            e.preventDefault();
            showEditError('Registration number must be a positive integer.');
            registrationInput.focus();
            return;
        }

        const selectedCourses = [];
        let duplicateFound = false;
        editCourseSelects.forEach(function(selectId) {
            const value = document.getElementById(selectId).value;
            if (value !== '0') {
                if (selectedCourses.includes(value)) {
                    duplicateFound = true;
                } else {
                    selectedCourses.push(value);
                }
            }
        });

        if (duplicateFound) {
            e.preventDefault();
            showEditError('You cannot select the same course multiple times. Please choose different courses.');
        }
    });

    // Verification: ticks are only stored when "Save changes" is pressed
    const verifyChecks = Array.from(document.querySelectorAll('input[name="verification[]"]'));
    const saveBar = document.getElementById('verifySaveBar');

    function updateVerificationState() {
        const verifiedCount = verifyChecks.filter(function(checkbox) { return checkbox.checked; }).length;
        document.getElementById('verifiedCount').textContent = verifiedCount;

        const changes = verifyChecks.filter(function(checkbox) {
            return checkbox.checked !== (checkbox.dataset.initial === '1');
        }).length;
        document.getElementById('verifyChangeNote').textContent = changes + (changes === 1 ? ' change' : ' changes') + ' not saved';
        saveBar.hidden = changes === 0;
    }

    function setAllVerified(verified) {
        verifyChecks.forEach(function(checkbox) {
            checkbox.checked = verified;
        });
        updateVerificationState();
    }

    document.getElementById('verifyAllBtn').addEventListener('click', function() {
        setAllVerified(true);
    });

    document.getElementById('unverifyAllBtn').addEventListener('click', function() {
        setAllVerified(false);
    });

    document.getElementById('discardChangesBtn').addEventListener('click', function() {
        verifyChecks.forEach(function(checkbox) {
            checkbox.checked = checkbox.dataset.initial === '1';
        });
        updateVerificationState();
    });

    table.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'verification[]') {
            updateVerificationState();
        }
    });

    // Search by roll or name
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    document.getElementById('studentSearch').addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        let shown = 0;
        rows.forEach(function(row) {
            row.hidden = !!query && row.dataset.search.indexOf(query) === -1;
            if (!row.hidden) {
                shown++;
            }
        });
        document.getElementById('studentNoMatch').hidden = shown > 0;
    });

    updateVerificationState();
})();
</script>
@endsection
