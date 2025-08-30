<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$isNew ? 'Create' : 'Edit'}} Mail Template - {{$exam->examname}}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container-fluid">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Header -->
        <div class="row bg-primary text-white p-3 mb-4">
            <div class="col">
                <h2>
                    <i class="bi bi-envelope-fill"></i> 
                    {{$isNew ? 'Create New' : 'Edit'}} Mail Template
                </h2>
                <p class="mb-0">Exam: {{$exam->examname}} ({{$exam->year}})</p>
            </div>
            <div class="col-auto">
                <a href="/mail/{{$exam->id}}" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Mail List
                </a>
            </div>
        </div>

        <!-- Form -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-{{$isNew ? 'success' : 'warning'}} text-{{$isNew ? 'white' : 'dark'}}">
                        <h5>
                            <i class="bi bi-{{$isNew ? 'plus-circle' : 'pencil'}}"></i> 
                            Mail Template Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/mail">
                            @csrf
                            <input type="hidden" name="exam_id" value="{{$exam->id}}">
                            @if(!$isNew)
                                <input type="hidden" name="template_id" value="{{$template->id}}">
                            @endif

                            <!-- Template Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{old('name', $template->name)}}" required>
                                <div class="form-text">Give a descriptive name for this mail template</div>
                            </div>

                            <!-- Template Type -->
                            <div class="mb-3">
                                <label for="type" class="form-label">Template Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="general" {{old('type', $template->type) == 'general' ? 'selected' : ''}}>
                                        General (All Teachers)
                                    </option>
                                    <option value="customized" {{old('type', $template->type) == 'customized' ? 'selected' : ''}}>
                                        Customized (Specific Courses)
                                    </option>
                                </select>
                                <div class="form-text">
                                    <strong>General:</strong> Send to all teachers assigned to this exam<br>
                                    <strong>Customized:</strong> Send only to teachers assigned to specific courses
                                </div>
                            </div>

                            <!-- Course Selection (for customized type) -->
                            <div class="mb-3" id="courseSelection" style="display: none;">
                                <label for="assigned_courses" class="form-label">Select Courses <span class="text-danger">*</span></label>
                                <div class="row">
                                    @foreach($courses as $course)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="assigned_courses[]" value="{{$course->id}}" 
                                                       id="course_{{$course->id}}"
                                                       {{in_array($course->id, old('assigned_courses', $template->assigned_courses ?? [])) ? 'checked' : ''}}>
                                                <label class="form-check-label" for="course_{{$course->id}}">
                                                    <strong>{{$course->course_code}}</strong> - {{$course->course_title}}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text">Select the courses whose teachers will receive this mail</div>
                            </div>

                            <!-- Subject -->
                            <div class="mb-3">
                                <label for="subject" class="form-label">Mail Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" 
                                       value="{{old('subject', $template->subject)}}" required>
                                <div class="form-text">Enter the email subject line</div>
                            </div>

                            <!-- Content -->
                            <div class="mb-4">
                                <label for="content" class="form-label">Mail Content <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="10" required>{{old('content', $template->content)}}</textarea>
                                <div class="form-text">
                                    Enter the mail content. You can use HTML formatting if needed.
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <a href="/mail/{{$exam->id}}" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                                
                                <div>
                                    @if(!$isNew)
                                        <button type="submit" name="submit" value="delete" class="btn btn-danger me-2"
                                                onclick="return confirm('Are you sure you want to delete this template?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    @endif
                                    
                                    <button type="submit" name="submit" value="{{$isNew ? 'create' : 'update'}}" class="btn btn-{{$isNew ? 'success' : 'warning'}}">
                                        <i class="bi bi-{{$isNew ? 'check-circle' : 'save'}}"></i> 
                                        {{$isNew ? 'Create Template' : 'Update Template'}}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preview Card -->
                @if(!$isNew)
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6><i class="bi bi-eye"></i> Preview Template</h6>
                    </div>
                    <div class="card-body">
                        <a href="/mail/preview/{{$template->id}}" class="btn btn-info" target="_blank">
                            <i class="bi bi-eye"></i> Preview with Recipients
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle type change
            function toggleCourseSelection() {
                const type = $('#type').val();
                if (type === 'customized') {
                    $('#courseSelection').show();
                    $('#courseSelection input[type="checkbox"]').prop('required', true);
                } else {
                    $('#courseSelection').hide();
                    $('#courseSelection input[type="checkbox"]').prop('required', false);
                }
            }

            // Initial toggle
            toggleCourseSelection();

            // On type change
            $('#type').on('change', toggleCourseSelection);

            // Form validation
            $('form').on('submit', function(e) {
                const type = $('#type').val();
                if (type === 'customized') {
                    const checkedCourses = $('#courseSelection input[type="checkbox"]:checked').length;
                    if (checkedCourses === 0) {
                        e.preventDefault();
                        alert('Please select at least one course for customized mail template.');
                        return false;
                    }
                }
            });
        });
    </script>
</body>
</html>
