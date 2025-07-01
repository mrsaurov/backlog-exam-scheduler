@extends('layouts.master')

@section('title', 'register')
 

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <form action="/register" method="POST">
        @csrf
        <input type="text" name="examid" value="{{$exam->id}}" hidden/>
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" name="name" class="form-control" id="name" aria-describedby="emailHelp" placeholder="Enter your name" value="{{ old('name') }}" required>
            <!-- <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small> -->
        </div>
        <div class="form-group">
            <label for="roll">Roll:</label>
            <input type="number" class="form-control" name="roll" id="roll" placeholder="Enter your roll number" value="{{ old('roll') }}" min="1" required>
        </div>
        <!-- <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="exampleCheck1">
            <label class="form-check-label" for="exampleCheck1">Check me out</label>
        </div> -->
        <div class="form-group">
            <label for="registration">Registration no:</label>
            <input type="number" class="form-control" name="registration" id="registration" placeholder="Enter your registration number" value="{{ old('registration') }}" min="1" required>
        </div>
        <div class="form-group">
            <label for="course1">Course 1:</label>
            <select class="form-select" name="course1" id="course1" placeholder="Select first subject" required>
                @foreach($courses as $course)
                    <option value="{{$course->id}}" {{ old('course1') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="course2">Course 2:</label>
            <select class="form-select" name="course2" id="course2" placeholder="Select second subject">
                <option value="0" {{ old('course2') == '0' || old('course2') == '' ? 'selected' : '' }}>None</option>
                @foreach($courses as $course)
                    <option value="{{$course->id}}" {{ old('course2') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="course3">Course 3:</label>
            <select class="form-select" name="course3" id="course3" placeholder="Select third subject">
                <option value="0" {{ old('course3') == '0' || old('course3') == '' ? 'selected' : '' }}>None</option>
                @foreach($courses as $course)
                    <option value="{{$course->id}}" {{ old('course3') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="course4">Course 4:</label>
            <select class="form-select" name="course4" id="course4" placeholder="Select fourth subject">
                <option value="0" {{ old('course4') == '0' || old('course4') == '' ? 'selected' : '' }}>None</option>
                @foreach($courses as $course)
                    <option value="{{$course->id}}" {{ old('course4') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="course5">Course 5:</label>
            <select class="form-select" name="course5" id="course5" placeholder="Select fifth subject">
                <option value="0" {{ old('course5') == '0' || old('course5') == '' ? 'selected' : '' }}>None</option>
                @foreach($courses as $course)
                    <option value="{{$course->id}}" {{ old('course5') == $course->id ? 'selected' : '' }}>{{$course->course_code}} - {{$course->course_title}}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

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
            alert('You cannot select the same course multiple times. Please choose different courses.');
            return false;
        }
        
        return true;
    });
});
</script>

@stop