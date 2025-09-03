@extends('layouts.master')
 
@section('title', 'Students')
 
@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<style>
.table {
    border-collapse: collapse;
    width: 100%;
}

.table th, .table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.table th {
    background-color: #f2f2f2;
    font-weight: bold;
}

.table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.table tr:hover {
    background-color: #f5f5f5;
}

.text-center {
    text-align: center !important;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.sorting-controls {
    display: flex;
    align-items: center;
}

.sorting-controls label {
    margin-bottom: 0;
    font-weight: 500;
}

.table th:first-child,
.table td:first-child {
    width: 60px;
    text-align: center;
}

@media (max-width: 768px) {
    .mb-3.d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .sorting-controls {
        margin-top: 10px;
    }
}
</style>

<form action="/students" method="POST">
    @csrf
    <input type="hidden" name="examid" value="{{$exam->id}}"/>

<!-- Sorting Controls -->
<div class="mb-3 d-flex justify-content-between align-items-center">
    <label for="studentTable">Students that has registered for the examination:</label>
    <div class="sorting-controls">
        <label class="mr-2">Sort by:</label>
        <a href="/students/{{$exam->id}}" class="btn btn-sm {{(!isset($currentSort) || $currentSort === 'default') ? 'btn-primary' : 'btn-outline-primary'}} mr-1">
            Default Order
        </a>
        <a href="/students/{{$exam->id}}?sort=roll" class="btn btn-sm {{(isset($currentSort) && $currentSort === 'roll') ? 'btn-primary' : 'btn-outline-primary'}}">
            Roll Number
        </a>
    </div>
</div>

<!-- Bulk Verification Controls -->
<div class="mb-3 d-flex justify-content-between align-items-center">
    <div class="bulk-actions">
        <label class="mr-2 font-weight-bold">Bulk Actions:</label>
        <button type="button" class="btn btn-success btn-sm mr-2" id="verifyAllBtn" title="Verify All Students">
            <i class="bi bi-check-circle"></i> Verify All
        </button>
        <button type="button" class="btn btn-warning btn-sm" id="unverifyAllBtn" title="Unverify All Students">
            <i class="bi bi-x-circle"></i> Unverify All
        </button>
    </div>
    <div class="verification-status">
        <small class="text-muted">
            <span id="verifiedCount">0</span> verified / <span id="totalCount">{{count($students)}}</span> total students
        </small>
    </div>
</div>

<table id="studentTable" class="table">
    <thead>
        <tr>
            <th>Sl. No.</th>
            <th>Roll</th>
            <th>Name</th>
            <th>Registration</th>
            <th>Course 1</th>
            <th>Course 2</th>
            <th>Course 3</th>
            <th>Course 4</th>
            <th>Course 5</th>
            <th class="text-center">Verified</th>
            <th class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $student)
            <tr>
                <td class="text-center">{{$loop->iteration}}</td>
                <td>{{$student['roll']}}</td>
                <td>{{$student['name']}}</td>
                <td>{{$student['registration']}}</td>
                <td>{{$student['course1']}}</td>
                <td>{{$student['course2']}}</td>
                <td>{{$student['course3']}}</td>
                <td>{{$student['course4']}}</td>
                <td>{{$student['course5']}}</td>
                <td class="text-center">
                    <input class="form-check-input" type="checkbox" value="{{$student['id']}}" name="verification[]" 
                    @if($student["verified"]== true)
                    checked
                    @endif
                    >
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-info btn-sm me-1" onclick="viewStudentDetails({{$student['id']}}, '{{$student['name']}}', '{{addslashes($student['last_appeared_exam'] ?? 'Not specified')}}', '{{addslashes($student['backlogged_subjects'] ?? 'Not specified')}}')" title="View Additional Details">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm me-1" onclick="editStudent({{$student['id']}})" title="Edit Student">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteStudent({{$student['id']}}, '{{$student['name']}}', {{$student['roll']}})" title="Delete Student">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<button class="btn-primary btn" type="submit" name="submit">Save</button>
<a href="/admin" class="btn btn-secondary ml-2">Back to Admin Panel</a>
</form>

<!-- Hidden form for delete operations -->
<form id="deleteForm" action="/students/delete" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="student_id" id="deleteStudentId">
    <input type="hidden" name="examid" value="{{$exam->id}}">
</form>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Student Registration</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editStudentForm" action="/students/edit" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="editStudentIdInput">
                    <input type="hidden" name="examid" value="{{$exam->id}}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editStudentName">Name:</label>
                                <input type="text" class="form-control" id="editStudentName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editStudentRoll">Roll:</label>
                                <input type="number" class="form-control" id="editStudentRoll" name="roll" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editStudentRegistration">Registration:</label>
                        <input type="number" class="form-control" id="editStudentRegistration" name="registration" min="1" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editCourse1">Course 1:</label>
                                <select class="form-control" id="editCourse1" name="course1" required>
                                    @foreach($courses as $course)
                                        <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editCourse2">Course 2:</label>
                                <select class="form-control" id="editCourse2" name="course2">
                                    <option value="0">None</option>
                                    @foreach($courses as $course)
                                        <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editCourse3">Course 3:</label>
                                <select class="form-control" id="editCourse3" name="course3">
                                    <option value="0">None</option>
                                    @foreach($courses as $course)
                                        <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editCourse4">Course 4:</label>
                                <select class="form-control" id="editCourse4" name="course4">
                                    <option value="0">None</option>
                                    @foreach($courses as $course)
                                        <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editCourse5">Course 5:</label>
                                <select class="form-control" id="editCourse5" name="course5">
                                    <option value="0">None</option>
                                    @foreach($courses as $course)
                                        <option value="{{$course->id}}">{{$course->course_code}} - {{$course->course_title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editVerified">Verified:</label>
                                <select class="form-control" id="editVerified" name="verified">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1" role="dialog" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentDetailsModalLabel">Student Additional Information</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary">Student: <span id="detailStudentName"></span></h6>
                        <hr>
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Last Appeared Exam:</label>
                    <p id="detailLastExam" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">List of Backlogged Subjects:</label>
                    <p id="detailBackloggedSubjects" class="text-muted"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewStudentDetails(studentId, studentName, lastExam, backloggedSubjects) {
    // Set modal content
    document.getElementById('detailStudentName').textContent = studentName;
    document.getElementById('detailLastExam').textContent = lastExam || 'Not specified';
    document.getElementById('detailBackloggedSubjects').textContent = backloggedSubjects || 'Not specified';
    
    // Show modal
    $('#studentDetailsModal').modal('show');
}

function deleteStudent(studentId, studentName, studentRoll) {
    if (confirm(`Are you sure you want to delete the registration for ${studentName} (Roll: ${studentRoll})? This action cannot be undone.`)) {
        document.getElementById('deleteStudentId').value = studentId;
        document.getElementById('deleteForm').submit();
    }
}

function editStudent(studentId) {
    try {
        // Get student data from the students array passed from backend
        const students = @json($students);
        const student = students.find(s => s.id == studentId);
        
        if (!student) {
            alert('Student data not found!');
            return;
        }
    
        // Set form values
        document.getElementById('editStudentIdInput').value = studentId;
        document.getElementById('editStudentName').value = student.name;
        document.getElementById('editStudentRoll').value = student.roll;
        document.getElementById('editStudentRegistration').value = student.registration;
        document.getElementById('editVerified').value = student.verified ? '1' : '0';
        
        // Set course selections using the stored course IDs
        document.getElementById('editCourse1').value = student.course1_id || '';
        document.getElementById('editCourse2').value = student.course2_id || '0';
        document.getElementById('editCourse3').value = student.course3_id || '0';
        document.getElementById('editCourse4').value = student.course4_id || '0';
        document.getElementById('editCourse5').value = student.course5_id || '0';
        
        // Show modal
        $('#editStudentModal').modal('show');
    } catch (error) {
        console.error('Error in editStudent function:', error);
        alert('Error opening edit modal. Please try again.');
    }
}

// Add course duplicate prevention for edit form
document.addEventListener('DOMContentLoaded', function() {
    const editCourseSelects = ['editCourse1', 'editCourse2', 'editCourse3', 'editCourse4', 'editCourse5'];
    
    function updateEditAvailableOptions() {
        const selectedValues = [];
        
        editCourseSelects.forEach(function(selectId) {
            const select = document.getElementById(selectId);
            const value = select.value;
            if (value !== '0') {
                selectedValues.push(value);
            }
        });
        
        editCourseSelects.forEach(function(selectId) {
            const select = document.getElementById(selectId);
            const currentValue = select.value;
            
            Array.from(select.options).forEach(function(option) {
                option.disabled = false;
                option.style.color = '';
            });
            
            selectedValues.forEach(function(selectedValue) {
                if (selectedValue !== currentValue) {
                    const optionToDisable = select.querySelector('option[value="' + selectedValue + '"]');
                    if (optionToDisable) {
                        optionToDisable.disabled = true;
                        optionToDisable.style.color = '#ccc';
                    }
                }
            });
        });
    }
    
    editCourseSelects.forEach(function(selectId) {
        const select = document.getElementById(selectId);
        select.addEventListener('change', function() {
            updateEditAvailableOptions();
        });
    });
    
    // Form validation for edit form
    document.getElementById('editStudentForm').addEventListener('submit', function(e) {
        const selectedCourses = [];
        let duplicateFound = false;
        
        // Validate roll and registration numbers
        const rollInput = document.getElementById('editStudentRoll');
        const registrationInput = document.getElementById('editStudentRegistration');
        
        const rollValue = parseInt(rollInput.value);
        const registrationValue = parseInt(registrationInput.value);
        
        if (!rollValue || rollValue < 1) {
            e.preventDefault();
            alert('Roll number must be a positive integer.');
            rollInput.focus();
            return false;
        }
        
        if (!registrationValue || registrationValue < 1) {
            e.preventDefault();
            alert('Registration number must be a positive integer.');
            registrationInput.focus();
            return false;
        }
        
        editCourseSelects.forEach(function(selectId) {
            const select = document.getElementById(selectId);
            const value = select.value;
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
            alert('You cannot select the same course multiple times. Please choose different courses.');
            return false;
        }
        
        return true;
    });
    
    // Bulk verification functionality
    document.getElementById('verifyAllBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to verify all students?')) {
            const checkboxes = document.querySelectorAll('input[name="verification[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
            updateVerificationCount();
        }
    });
    
    document.getElementById('unverifyAllBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to unverify all students?')) {
            const checkboxes = document.querySelectorAll('input[name="verification[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            updateVerificationCount();
        }
    });
    
    // Update verification count display
    function updateVerificationCount() {
        const checkboxes = document.querySelectorAll('input[name="verification[]"]');
        const verifiedCount = document.querySelectorAll('input[name="verification[]"]:checked').length;
        document.getElementById('verifiedCount').textContent = verifiedCount;
    }
    
    // Update count on individual checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'verification[]') {
            updateVerificationCount();
        }
    });
    
    // Initialize verification count on page load
    updateVerificationCount();
});
</script>

@endsection