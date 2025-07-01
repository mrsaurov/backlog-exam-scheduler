@extends('layouts.master')
 
@section('title', 'Students')
 

@section('content')

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
</style>

<form action="/students" method="POST">
    @csrf
    <input type="hidden" name="examid" value="{{$exam->id}}"/>
<label for="studentTable">Students that has registered for the examination:</label>
<table id="studentTable" class="table">
    <thead>
        <tr>
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
                    <button type="button" class="btn btn-primary btn-sm me-1" onclick="editStudent({{$student['id']}})">
                        Edit
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteStudent({{$student['id']}}, '{{$student['name']}}', {{$student['roll']}})">
                        Delete
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<button class="btn-primary btn" type="submit" name="submit">Save</button>
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
                                <input type="text" class="form-control" id="editStudentRoll" name="roll" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editStudentRegistration">Registration:</label>
                        <input type="text" class="form-control" id="editStudentRegistration" name="registration" required>
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

<script>
function deleteStudent(studentId, studentName, studentRoll) {
    if (confirm(`Are you sure you want to delete the registration for ${studentName} (Roll: ${studentRoll})? This action cannot be undone.`)) {
        document.getElementById('deleteStudentId').value = studentId;
        document.getElementById('deleteForm').submit();
    }
}

function editStudent(studentId) {
    // Find the student data from the table
    const studentRow = document.querySelector(`button[onclick="editStudent(${studentId})"]`).closest('tr');
    const cells = studentRow.cells;
    
    // Get student data from table cells
    const roll = cells[0].textContent;
    const name = cells[1].textContent;
    const registration = cells[2].textContent;
    const course1 = cells[3].textContent;
    const course2 = cells[4].textContent;
    const course3 = cells[5].textContent;
    const course4 = cells[6].textContent;
    const course5 = cells[7].textContent;
    const verified = cells[8].querySelector('input').checked;
    
    // Set form values
    document.getElementById('editStudentIdInput').value = studentId;
    document.getElementById('editStudentName').value = name;
    document.getElementById('editStudentRoll').value = roll;
    document.getElementById('editStudentRegistration').value = registration;
    document.getElementById('editVerified').value = verified ? '1' : '0';
    
    // Get course mappings to find course IDs
    const courses = @json($courses);
    const courseMap = {};
    courses.forEach(course => {
        courseMap[course.course_code] = course.id;
    });
    
    // Set course selections
    document.getElementById('editCourse1').value = courseMap[course1] || '';
    document.getElementById('editCourse2').value = course2 === '' ? '0' : (courseMap[course2] || '0');
    document.getElementById('editCourse3').value = course3 === '' ? '0' : (courseMap[course3] || '0');
    document.getElementById('editCourse4').value = course4 === '' ? '0' : (courseMap[course4] || '0');
    document.getElementById('editCourse5').value = course5 === '' ? '0' : (courseMap[course5] || '0');
    
    // Show modal
    $('#editStudentModal').modal('show');
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
});
</script>

@endsection