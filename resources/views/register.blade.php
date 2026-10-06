@extends('layouts.master')

@section('title', 'Register')
 

@section('content')
@php
    $deadline = \Carbon\Carbon::parse($exam->deadline);
@endphp

    <a href="/" class="back-link"><i class="bi bi-arrow-left"></i> All exams</a>
    <div class="page-head">
        <h1>Register for {{$exam->exam_name}}</h1>
        <p>{{$exam->department}}, series {{$exam->series}}. Registration closes on {{ $deadline->format('j M Y') }}.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Your registration was not submitted.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <div class="row g-4">
        <div class="col-lg-8">
            <form action="/register" method="POST">
                @csrf
                <input type="text" name="examid" value="{{$exam->id}}" hidden/>

                <div class="panel">
                    <h2 class="panel-title">Your details</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Full name</label>
                            <input type="text" name="name" class="form-control" id="name" value="{{ old('name') }}" autocomplete="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="roll" class="form-label">Roll number</label>
                            <input type="number" class="form-control num" name="roll" id="roll" placeholder="ex: 1903150" value="{{ old('roll') }}" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="registration" class="form-label">Registration number</label>
                            <input type="number" class="form-control num" name="registration" id="registration" value="{{ old('registration') }}" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_no" class="form-label">Contact No.</label>
                            <input type="tel" class="form-control num" name="contact_no" id="contact_no" placeholder="ex: 01712345678" value="{{ old('contact_no') }}" pattern="(\+?88)?01[3-9][0-9]{8}" maxlength="14" title="Enter a valid mobile number, e.g. 01712345678" autocomplete="tel" required>
                            <div class="form-hint">Your mobile number, so the department can reach you.</div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h2 class="panel-title">Exam history</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="last_appeared_exam" class="form-label">Last appeared exam</label>
                            <input type="text" class="form-control" name="last_appeared_exam" id="last_appeared_exam" placeholder="ex: 4th Year Backlog 2023" value="{{ old('last_appeared_exam') }}" required>
                        </div>
                        <div class="col-12">
                            <label for="backlogged_subjects" class="form-label">All your backlogged subjects</label>
                            <input type="text" class="form-control" name="backlogged_subjects" id="backlogged_subjects" placeholder="ex: CSE 2201, Math 1213" value="{{ old('backlogged_subjects') }}" required>
                            <div class="form-hint">List every subject you have a backlog in, separated by commas.</div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h2 class="panel-title">Courses for this exam</h2>
                    <p class="text-muted">Choose up to five courses. Each course can be chosen once.</p>
                    <div class="row g-3">
                    <div class="col-md-6">
                        <label for="course1" class="form-label">Course 1</label>
                        <select class="form-select" name="course1" id="course1" required>
                            @foreach($courses as $course)
                                <option value="{{$course->id}}" {{ old('course1') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="course2" class="form-label">Course 2 <span class="optional">(optional)</span></label>
                        <select class="form-select" name="course2" id="course2">
                            <option value="0" {{ old('course2') == '0' || old('course2') == '' ? 'selected' : '' }}>None</option>
                            @foreach($courses as $course)
                                <option value="{{$course->id}}" {{ old('course2') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="course3" class="form-label">Course 3 <span class="optional">(optional)</span></label>
                        <select class="form-select" name="course3" id="course3">
                            <option value="0" {{ old('course3') == '0' || old('course3') == '' ? 'selected' : '' }}>None</option>
                            @foreach($courses as $course)
                                <option value="{{$course->id}}" {{ old('course3') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="course4" class="form-label">Course 4 <span class="optional">(optional)</span></label>
                        <select class="form-select" name="course4" id="course4">
                            <option value="0" {{ old('course4') == '0' || old('course4') == '' ? 'selected' : '' }}>None</option>
                            @foreach($courses as $course)
                                <option value="{{$course->id}}" {{ old('course4') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="course5" class="form-label">Course 5 <span class="optional">(optional)</span></label>
                        <select class="form-select" name="course5" id="course5">
                            <option value="0" {{ old('course5') == '0' || old('course5') == '' ? 'selected' : '' }}>None</option>
                            @foreach($courses as $course)
                                <option value="{{$course->id}}" {{ old('course5') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                            @endforeach
                        </select>
                    </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">Submit registration</button>
                    <a href="/" class="btn btn-quiet">Cancel</a>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <h2 class="panel-title">After you submit</h2>
                <p class="mb-0">You can download your application form as a PDF straight away, and again later from the exams page using your roll number.</p>
            </div>

            @if(isset($notices) && count($notices) > 0)
            <div class="panel">
                <h2 class="panel-title">Latest notices</h2>
                @foreach($notices as $notice)
                    <div class="mini-notice">
                        <div class="fw-semibold">{{$notice->title}}</div>
                        <div class="text-muted">{{$notice->created_at->format('j M Y')}}</div>
                    </div>
                @endforeach
                <a href="/exam/{{$exam->id}}/notices" class="d-inline-block mt-2">Read all notices</a>
            </div>
            @endif
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelects = ['course1', 'course2', 'course3', 'course4', 'course5'];
    
    // Function to update available options for all selects
    function updateAvailableOptions() {
        const selectedValues = [];
        
        // Get all currently selected values (excluding 0 which means "None")
        courseSelects.forEach(function(selectId) {
            const select = document.getElementById(selectId);
            const value = select.value;
            if (value !== '0') {
                selectedValues.push(value);
            }
        });
        
        // Update each select to disable already selected options
        courseSelects.forEach(function(selectId) {
            const select = document.getElementById(selectId);
            const currentValue = select.value;
            
            // Enable all options first
            Array.from(select.options).forEach(function(option) {
                option.disabled = false;
                option.style.color = '';
            });
            
            // Disable options that are selected in other selects
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
    
    // Add event listeners to all course selects
    courseSelects.forEach(function(selectId) {
        const select = document.getElementById(selectId);
        select.addEventListener('change', function() {
            updateAvailableOptions();
        });
    });
    
    // Initial update
    updateAvailableOptions();
    
    // Form validation before submit
    document.querySelector('form').addEventListener('submit', function(e) {
        const selectedCourses = [];
        let duplicateFound = false;
        
        // Validate roll and registration numbers
        const rollInput = document.getElementById('roll');
        const registrationInput = document.getElementById('registration');
        
        const rollValue = parseInt(rollInput.value);
        const registrationValue = parseInt(registrationInput.value);
        
        if (!rollValue || rollValue < 1) {
            e.preventDefault();
            showNotice('Roll number must be a positive integer.');
            rollInput.focus();
            return false;
        }
        
        if (!registrationValue || registrationValue < 1) {
            e.preventDefault();
            showNotice('Registration number must be a positive integer.');
            registrationInput.focus();
            return false;
        }
        
        courseSelects.forEach(function(selectId) {
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
            showNotice('You cannot select the same course multiple times. Please choose different courses.');
            return false;
        }
        
        return true;
    });
});
</script>

@stop