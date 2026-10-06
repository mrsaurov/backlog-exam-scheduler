@extends('layouts.exam')

@section('title', $isNew ? 'New template' : 'Edit template')
@section('tab', 'mail')

@section('exam-content')
<div class="page-form">
    <div class="subpage-head">
        <a href="/mail/{{$exam->id}}" class="back-link"><i class="bi bi-arrow-left"></i> Back to mail</a>
        <h2>{{$isNew ? 'New template' : 'Edit template'}}</h2>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>The template was not saved.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/mail" id="templateForm">
        @csrf
        <input type="hidden" name="exam_id" value="{{$exam->id}}">
        @if(!$isNew)
            <input type="hidden" name="template_id" value="{{$template->id}}">
        @endif

        <div class="panel">
            <div class="row g-3">
                <!-- Template Name -->
                <div class="col-md-6">
                    <label for="name" class="form-label">Template name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="{{old('name', $template->name)}}" maxlength="255" required>
                    <div class="form-hint">Only you see this name; it is not part of the email.</div>
                </div>

                <!-- Template Type -->
                <div class="col-md-6">
                    <label for="type" class="form-label">Goes to</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">Choose</option>
                        <option value="general" {{old('type', $template->type) == 'general' ? 'selected' : ''}}>
                            All teachers
                        </option>
                        <option value="customized" {{old('type', $template->type) == 'customized' ? 'selected' : ''}}>
                            Teachers of chosen courses
                        </option>
                    </select>
                </div>

                <!-- Course Selection (for customized type) -->
                <div class="col-12" id="courseSelection" hidden>
                    <label class="form-label">Courses</label>
                    @if($courses->count() > 0)
                    <div class="check-grid">
                        @foreach($courses as $course)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="assigned_courses[]" value="{{$course->id}}" 
                                       id="course_{{$course->id}}"
                                       {{in_array($course->id, old('assigned_courses', $template->assigned_courses ?? [])) ? 'checked' : ''}}>
                                <label class="form-check-label" for="course_{{$course->id}}">
                                    <span class="fw-semibold num">{{$course->course_code}}</span> {{$course->course_title}}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-hint">Teachers assigned to these courses are the recipients.</div>
                    @else
                    <div class="callout">No course has verified students yet, so there are no courses to choose.</div>
                    @endif
                </div>

                <!-- Subject -->
                <div class="col-12">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" 
                           value="{{old('subject', $template->subject)}}" maxlength="500" required>
                </div>

                <!-- Content -->
                <div class="col-12">
                    <label for="content" class="form-label">Message</label>
                    <textarea class="form-control" id="content" name="content" rows="10" required>{{old('content', $template->content)}}</textarea>
                    <div class="form-hint">You can use HTML formatting if needed.</div>
                </div>
            </div>
            <div class="form-error" id="templateFormError" role="alert"></div>
        </div>

        <!-- Action Buttons -->
        <div class="form-actions">
            <button type="submit" name="submit" value="{{$isNew ? 'create' : 'update'}}" class="btn btn-primary">
                {{$isNew ? 'Save template' : 'Save changes'}}
            </button>
            <a href="/mail/{{$exam->id}}" class="btn btn-quiet">Cancel</a>
            @if(!$isNew)
                <a href="/mail/preview/{{$template->id}}" class="btn btn-quiet"><i class="bi bi-eye"></i> Preview</a>
                <button type="submit" name="submit" value="delete" class="btn btn-danger-quiet push-end" formnovalidate
                        data-confirm="&quot;{{$template->name}}&quot; will be deleted. This cannot be undone."
                        data-confirm-title="Delete this template?"
                        data-confirm-button="Delete template">Delete template</button>
            @endif
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Handle type change
        function toggleCourseSelection() {
            $('#courseSelection').prop('hidden', $('#type').val() !== 'customized');
        }

        // Initial toggle
        toggleCourseSelection();

        // On type change
        $('#type').on('change', toggleCourseSelection);

        // Form validation
        $('#templateForm').on('submit', function(e) {
            const error = $('#templateFormError');
            error.text('').removeClass('is-shown');

            if ($('#type').val() === 'customized') {
                const checkedCourses = $('#courseSelection input[type="checkbox"]:checked').length;
                if (checkedCourses === 0) {
                    e.preventDefault();
                    error.text('Please select at least one course for customized mail template.').addClass('is-shown');
                    return false;
                }
            }
        });
    });
</script>
@endsection
