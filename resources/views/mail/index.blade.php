<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Mail Templates - {{$exam->examname}}</title>
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
                <h2><i class="bi bi-envelope-fill"></i> Mail Management</h2>
                <p class="mb-0">Exam: {{$exam->exam_name}}</p>
            </div>
            <div class="col-auto">
                <a href="/teachers/{{$exam->id}}" class="btn btn-light me-2">
                    <i class="bi bi-people"></i> Teachers
                </a>
                <a href="/admin" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Admin
                </a>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col">
                <div class="btn-group me-3" role="group">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generalMailModal">
                        <i class="bi bi-envelope-plus"></i> Send General Mail
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customizedMailModal">
                        <i class="bi bi-envelope-check"></i> Send Customized Mail
                    </button>
                </div>
                <a href="/mail/{{$exam->id}}/create" class="btn btn-outline-secondary">
                    <i class="bi bi-plus-circle"></i> Create Custom Template
                </a>
            </div>
        </div>

        <!-- Quick Send Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Send Templates</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($predefinedTemplates as $key => $template)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">{{$template['name']}}</h6>
                                        <p class="card-text small text-muted">{{$template['subject']}}</p>
                                        <button class="btn btn-sm btn-outline-primary quick-send" 
                                                data-template="{{$key}}" 
                                                data-name="{{$template['name']}}">
                                            <i class="bi bi-send"></i> Quick Send
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mail Templates List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="bi bi-list"></i> Mail Templates</h5>
                    </div>
                    <div class="card-body">
                        @if($mailTemplates->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Template Name</th>
                                            <th>Type</th>
                                            <th>Subject</th>
                                            <th>Assigned Courses</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($mailTemplates as $template)
                                        <tr>
                                            <td>
                                                <strong>{{$template->name}}</strong>
                                            </td>
                                            <td>
                                                @if($template->type === 'general')
                                                    <span class="badge bg-primary">General</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Customized</span>
                                                @endif
                                            </td>
                                            <td>{{$template->subject}}</td>
                                            <td>
                                                @if($template->type === 'general')
                                                    <span class="text-muted">All Teachers</span>
                                                @else
                                                    @if($template->assigned_courses)
                                                        @foreach($template->assigned_courses as $courseId)
                                                            @php
                                                                $course = $courses->where('id', $courseId)->first();
                                                            @endphp
                                                            @if($course)
                                                                <span class="badge bg-secondary me-1">{{$course->course_code}}</span>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{$template->created_at->format('d M Y')}}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/mail/preview/{{$template->id}}" class="btn btn-sm btn-info" title="Preview">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="/mail/{{$exam->id}}/edit/{{$template->id}}" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger delete-template" 
                                                            data-template-id="{{$template->id}}" 
                                                            data-template-name="{{$template->name}}" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-envelope" style="font-size: 4rem; color: #ccc;"></i>
                                <h4 class="text-muted mt-3">No Mail Templates</h4>
                                <p class="text-muted">Create your first mail template to send notifications to teachers.</p>
                                <a href="/mail/{{$exam->id}}/create" class="btn btn-success">
                                    <i class="bi bi-plus-circle"></i> Create Mail Template
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Summary
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6><i class="bi bi-info-circle"></i> Available Courses for this Exam</h6>
                    </div>
                    <div class="card-body">
                        @if($courses->count() > 0)
                            <div class="row">
                                @foreach($courses as $course)
                                    <div class="col-md-3 mb-2">
                                        <span class="badge bg-light text-dark">{{$course->course_code}} - {{$course->course_title}}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">No courses mapped to this exam yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the mail template "<strong id="templateName"></strong>"?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="/mail" style="display: inline;">
                        @csrf
                        <input type="hidden" name="exam_id" value="{{$exam->id}}">
                        <input type="hidden" name="template_id" id="deleteTemplateId">
                        <input type="hidden" name="submit" value="delete">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- General Mail Modal -->
    <div class="modal fade" id="generalMailModal" tabindex="-1" aria-labelledby="generalMailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="generalMailModalLabel">
                        <i class="bi bi-envelope-plus"></i> Send General Mail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/mail/{{$exam->id}}/send-general" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            This will send mail to all teachers who have course assignments in this exam.
                        </div>
                        
                        <div class="mb-3">
                            <label for="general_subject" class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="general_subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="general_content" class="form-label">Message Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="general_content" name="content" rows="8" required 
                                      placeholder="Use [Teacher's Name], [Exam Name] for personalization and [Deadline Date] if deadline is set"></textarea>
                            <div class="form-text">
                                <small class="text-muted">
                                    Available placeholders: <code>[Teacher's Name]</code>, <code>[Exam Name]</code>, <code>[Deadline Date]</code>
                                </small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="general_deadline" class="form-label">Deadline (Optional)</label>
                            <input type="date" class="form-control" id="general_deadline" name="deadline">
                        </div>
                        
                        <div class="mb-3">
                            <label for="general_attachments" class="form-label">
                                <i class="bi bi-paperclip"></i> Attachments (Optional)
                            </label>
                            <input type="file" class="form-control" id="general_attachments" name="attachments[]" multiple 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png">
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> You can select multiple files. Supported formats: PDF, Word, Excel, PowerPoint, Text, Images (JPG, PNG). Maximum 10MB per file.
                                </small>
                            </div>
                            <div id="general_attachment_preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="generalSendButton">
                            <i class="bi bi-send"></i> Send Mail
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Customized Mail Modal -->
    <div class="modal fade" id="customizedMailModal" tabindex="-1" aria-labelledby="customizedMailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="customizedMailModalLabel">
                        <i class="bi bi-envelope-check"></i> Send Customized Mail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/mail/{{$exam->id}}/send-customized">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Select a template and courses. Mail will be sent only to teachers assigned to the selected courses.
                        </div>
                        
                        <div class="mb-3">
                            <label for="template_type" class="form-label">Template Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="template_type" name="template_type" required>
                                <option value="">Select Template</option>
                                @foreach($predefinedTemplates as $key => $template)
                                    <option value="{{$key}}">{{$template['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Courses <span class="text-danger">*</span></label>
                            
                            <!-- Course Selection Toggles -->
                            <div class="mb-3">
                                <div class="btn-group" role="group" aria-label="Course selection toggles">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllCourses">
                                        <i class="bi bi-check-all"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" id="selectTheoryCourses">
                                        <i class="bi bi-book"></i> Theory
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="selectSessionalCourses">
                                        <i class="bi bi-laptop"></i> Sessional
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllCourses">
                                        <i class="bi bi-x-circle"></i> Deselect All
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row">
                                @foreach($courses as $course)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input course-checkbox" type="checkbox" name="courses[]" 
                                               value="{{$course->id}}" id="course_{{$course->id}}" 
                                               data-course-code="{{$course->course_code}}">
                                        <label class="form-check-label" for="course_{{$course->id}}">
                                            <strong>{{$course->course_code}}</strong> - {{$course->course_title}}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customized_deadline" class="form-label">
                                Deadline 
                                <span class="text-danger deadline-required" style="display: none;">*</span>
                                <span class="text-muted deadline-optional">(Optional)</span>
                            </label>
                            <input type="date" class="form-control" id="customized_deadline" name="deadline">
                            <div class="form-text deadline-help" style="display: none;">
                                Deadline is required for CT Marks, Sessional Marks, and Question Manuscript templates.
                            </div>
                        </div>
                        
                        <div id="template_preview" class="mt-3" style="display: none;">
                            <label class="form-label">Template Preview:</label>
                            <div class="border p-3 bg-light">
                                <div id="preview_content"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send Mail
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Template preview functionality
            const templates = @json($predefinedTemplates);
            const templatesRequiringDeadline = ['ct_marks', 'sessional_marks', 'question_manuscript'];
            
            $('#template_type').on('change', function() {
                const templateKey = $(this).val();
                
                // Handle deadline requirement
                if (templateKey && templatesRequiringDeadline.includes(templateKey)) {
                    $('#customized_deadline').prop('required', true);
                    $('.deadline-required').show();
                    $('.deadline-optional').hide();
                    $('.deadline-help').show();
                } else {
                    $('#customized_deadline').prop('required', false);
                    $('.deadline-required').hide();
                    $('.deadline-optional').show();
                    $('.deadline-help').hide();
                }
                
                // Template preview
                if (templateKey && templates[templateKey]) {
                    const template = templates[templateKey];
                    const content = template.content.replace(/\n/g, '<br>');
                    $('#preview_content').html(`
                        <strong>Subject:</strong> ${template.subject}<br><br>
                        <strong>Content:</strong><br>${content}
                    `);
                    $('#template_preview').show();
                } else {
                    $('#template_preview').hide();
                }
            });
            
            // Course selection toggles
            $('#selectAllCourses').on('click', function() {
                $('.course-checkbox').prop('checked', true);
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
                setTimeout(() => {
                    $(this).removeClass('btn-primary').addClass('btn-outline-primary');
                }, 200);
            });
            
            $('#deselectAllCourses').on('click', function() {
                $('.course-checkbox').prop('checked', false);
                $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');
                setTimeout(() => {
                    $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');
                }, 200);
            });
            
            $('#selectTheoryCourses').on('click', function() {
                $('.course-checkbox').each(function() {
                    const courseCode = $(this).data('course-code');
                    // Extract numeric part and check if odd
                    const numericPart = courseCode.match(/\d+/);
                    if (numericPart && parseInt(numericPart[0]) % 2 === 1) {
                        $(this).prop('checked', true);
                    }
                });
                $(this).removeClass('btn-outline-info').addClass('btn-info');
                setTimeout(() => {
                    $(this).removeClass('btn-info').addClass('btn-outline-info');
                }, 200);
            });
            
            $('#selectSessionalCourses').on('click', function() {
                $('.course-checkbox').each(function() {
                    const courseCode = $(this).data('course-code');
                    // Extract numeric part and check if even
                    const numericPart = courseCode.match(/\d+/);
                    if (numericPart && parseInt(numericPart[0]) % 2 === 0) {
                        $(this).prop('checked', true);
                    }
                });
                $(this).removeClass('btn-outline-success').addClass('btn-success');
                setTimeout(() => {
                    $(this).removeClass('btn-success').addClass('btn-outline-success');
                }, 200);
            });
            
            // Quick send functionality
            $('.quick-send').on('click', function() {
                const templateKey = $(this).data('template');
                const templateName = $(this).data('name');
                
                // Set the template in customized modal
                $('#template_type').val(templateKey).trigger('change');
                $('#customizedMailModal').modal('show');
            });

            // Handle delete button click
            $('.delete-template').on('click', function() {
                const templateId = $(this).data('template-id');
                const templateName = $(this).data('template-name');
                
                $('#templateName').text(templateName);
                $('#deleteTemplateId').val(templateId);
                $('#deleteModal').modal('show');
            });
            
            // Form validation for customized mail
            $('form[action*="send-customized"]').on('submit', function(e) {
                const selectedCourses = $('.course-checkbox:checked').length;
                const templateType = $('#template_type').val();
                const deadline = $('#customized_deadline').val();
                
                if (selectedCourses === 0) {
                    e.preventDefault();
                    alert('Please select at least one course.');
                    return false;
                }
                
                if (templatesRequiringDeadline.includes(templateType) && !deadline) {
                    e.preventDefault();
                    alert('Deadline is required for the selected template.');
                    $('#customized_deadline').focus();
                    return false;
                }
            });
            
            // Handle file attachments preview
            $('#general_attachments').on('change', function() {
                const files = this.files;
                const previewContainer = $('#general_attachment_preview');
                const sendButton = $('#generalSendButton');
                previewContainer.empty();
                
                if (files.length > 0) {
                    let totalSize = 0;
                    const maxFileSize = 10 * 1024 * 1024; // 10MB
                    const maxTotalSize = 50 * 1024 * 1024; // 50MB
                    
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        totalSize += file.size;
                        
                        if (file.size > maxFileSize) {
                            alert(`File "${file.name}" is too large. Maximum file size is 10MB.`);
                            this.value = '';
                            previewContainer.empty();
                            sendButton.html('<i class="bi bi-send"></i> Send Mail');
                            return;
                        }
                    }
                    
                    if (totalSize > maxTotalSize) {
                        alert('Total file size exceeds 50MB. Please reduce the number or size of files.');
                        this.value = '';
                        previewContainer.empty();
                        sendButton.html('<i class="bi bi-send"></i> Send Mail');
                        return;
                    }
                    
                    const fileListHtml = '<div class="alert alert-light border">' +
                        '<strong><i class="bi bi-paperclip"></i> Selected Files:</strong><br>' +
                        Array.from(files).map(file => 
                            `<small class="d-block text-muted">• ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small>`
                        ).join('') +
                        '</div>';
                    
                    previewContainer.html(fileListHtml);
                    sendButton.html('<i class="bi bi-paperclip"></i> Send Mail with ' + files.length + ' Attachment(s)');
                } else {
                    sendButton.html('<i class="bi bi-send"></i> Send Mail');
                }
            });
            
            // Reset general mail modal when closed
            $('#generalMailModal').on('hidden.bs.modal', function() {
                $(this).find('form')[0].reset();
                $('#general_attachment_preview').empty();
                $('#generalSendButton').html('<i class="bi bi-send"></i> Send Mail');
            });
        });
    </script>
</body>
</html>
