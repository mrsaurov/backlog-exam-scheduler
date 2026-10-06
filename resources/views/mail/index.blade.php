@extends('layouts.exam')

@section('title', 'Mail')
@section('tab', 'mail')

@section('exam-content')
@php
    // Saved general templates, keyed by ID, for the "Use in a general mail" action
    $savedGeneralTemplates = $mailTemplates->where('type', 'general')->keyBy('id')->map(function($template) {
        return ['subject' => $template->subject, 'content' => $template->content];
    });
@endphp
<div class="toolbar">
    <div class="segmented" aria-label="Mail views">
        <a href="/mail/{{$exam->id}}" class="is-active" aria-current="page">Send mail</a>
        <a href="/mail/{{$exam->id}}/log">Tracker</a>
    </div>
</div>

<!-- Ready-made requests (sent per course) -->
<div class="panel panel-flush">
    <div class="panel-head">
        <div>
            <h2 class="panel-title">Requests to course teachers</h2>
            <p class="section-note">Ready-made emails. You choose the courses, and each assigned teacher gets one email listing their courses.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-clean">
            <tbody>
                @foreach($predefinedTemplates as $key => $template)
                <tr>
                    <td>
                        <span class="cell-main">{{$template['name']}}</span>
                        <span class="cell-sub">{{$template['subject']}}</span>
                    </td>
                    <td class="cell-actions">
                        <button type="button" class="btn btn-quiet btn-sm quick-send"
                                data-template="{{$key}}"
                                data-name="{{$template['name']}}">
                            <i class="bi bi-send"></i> Send
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- General mail -->
<div class="panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="panel-title mb-0">General mail</h2>
            <p class="section-note">Your own message, with optional attachments, to every teacher who has a course in this exam.</p>
        </div>
        <button type="button" class="btn btn-quiet" data-bs-toggle="modal" data-bs-target="#generalMailModal">
            <i class="bi bi-pencil-square"></i> Write mail
        </button>
    </div>
</div>

<!-- Saved templates -->
<div class="panel panel-flush">
    <div class="panel-head">
        <div>
            <h2 class="panel-title">Saved templates</h2>
            <p class="section-note">Messages you have written and kept for this exam.</p>
        </div>
        <a href="/mail/{{$exam->id}}/create" class="btn btn-quiet btn-sm">
            <i class="bi bi-plus-lg"></i> New template
        </a>
    </div>
    @if($mailTemplates->count() > 0)
        <div class="table-responsive">
            <table class="table table-clean table-wide">
                <thead>
                    <tr>
                        <th>Template</th>
                        <th>Type</th>
                        <th>Goes to</th>
                        <th>Created</th>
                        <th class="cell-actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mailTemplates as $template)
                    <tr>
                        <td>
                            <span class="cell-main">{{$template->name}}</span>
                            <span class="cell-sub">{{$template->subject}}</span>
                        </td>
                        <td>
                            @if($template->type === 'general')
                                <span class="status status-accent">General</span>
                            @else
                                <span class="status status-neutral">Customized</span>
                            @endif
                        </td>
                        <td>
                            @if($template->type === 'general')
                                All teachers
                            @else
                                <div class="tag-list">
                                @if($template->assigned_courses)
                                    @foreach($template->assigned_courses as $courseId)
                                        @php
                                            $course = $courses->where('id', $courseId)->first();
                                        @endphp
                                        @if($course)
                                            <span class="tag">{{$course->course_code}}</span>
                                        @endif
                                    @endforeach
                                @endif
                                </div>
                            @endif
                        </td>
                        <td class="num text-nowrap">{{$template->created_at->format('j M Y')}}</td>
                        <td class="cell-actions">
                            @if($template->type === 'general')
                                <button type="button" class="btn-icon use-template" data-template-id="{{$template->id}}" title="Use in a general mail" aria-label="Use in a general mail">
                                    <i class="bi bi-send"></i>
                                </button>
                            @endif
                            <a href="/mail/preview/{{$template->id}}" class="btn-icon" title="Preview" aria-label="Preview template">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="/mail/{{$exam->id}}/edit/{{$template->id}}" class="btn-icon" title="Edit" aria-label="Edit template">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn-icon is-danger delete-template"
                                    data-template-id="{{$template->id}}"
                                    data-template-name="{{$template->name}}" title="Delete" aria-label="Delete template">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <h3>No saved templates</h3>
            <p>Save a message here when you expect to send it again.</p>
        </div>
    @endif
</div>

<!-- Hidden form for deleting a template -->
<form id="deleteForm" method="POST" action="/mail" hidden>
    @csrf
    <input type="hidden" name="exam_id" value="{{$exam->id}}">
    <input type="hidden" name="template_id" id="deleteTemplateId">
    <input type="hidden" name="submit" value="delete">
</form>

<!-- General Mail Modal -->
<div class="modal fade" id="generalMailModal" tabindex="-1" aria-labelledby="generalMailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generalMailModalLabel">Write general mail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/mail/{{$exam->id}}/send-general" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="callout mb-3">
                        Goes to every teacher assigned to a course that has verified students in this exam.
                    </div>

                    <div class="mb-3">
                        <label for="general_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="general_subject" name="subject" maxlength="255" required>
                    </div>

                    <div class="mb-3">
                        <label for="general_content" class="form-label">Message</label>
                        <textarea class="form-control" id="general_content" name="content" rows="8" required></textarea>
                        <div class="form-hint">
                            These are filled in for each teacher: <span class="tag">[Teacher's Name]</span> <span class="tag">[Exam Name]</span> <span class="tag">[Deadline Date]</span>.
                            The message is sent as HTML, so wrap each paragraph in <span class="tag">&lt;p&gt;...&lt;/p&gt;</span>; plain line breaks are not kept.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="general_deadline" class="form-label">Deadline <span class="optional">(optional)</span></label>
                            <input type="date" class="form-control" id="general_deadline" name="deadline">
                        </div>
                        <div class="col-md-7">
                            <label for="general_attachments" class="form-label">Attachments <span class="optional">(optional)</span></label>
                            <input type="file" class="form-control" id="general_attachments" name="attachments[]" multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png">
                            <div class="form-hint">PDF, Word, Excel, PowerPoint, text or images. Up to 10 files, 10 MB each.</div>
                        </div>
                    </div>
                    <div id="general_attachment_preview" class="mt-2"></div>
                    <div class="form-error" id="generalMailError" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-quiet" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="generalSendButton">
                        <i class="bi bi-send"></i> <span>Send mail</span>
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
            <div class="modal-header">
                <h5 class="modal-title" id="customizedMailModalLabel">Send to course teachers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/mail/{{$exam->id}}/send-customized">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="template_type" class="form-label">Request</label>
                            <select class="form-select" id="template_type" name="template_type" required>
                                <option value="">Choose a request</option>
                                @foreach($predefinedTemplates as $key => $template)
                                    <option value="{{$key}}">{{$template['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="customized_deadline" class="form-label">
                                Deadline
                                <span class="optional deadline-optional">(optional)</span>
                            </label>
                            <input type="date" class="form-control" id="customized_deadline" name="deadline">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
                            <label class="form-label mb-0">Courses</label>
                            <!-- Course Selection Toggles -->
                            <div class="toolbar-group" role="group" aria-label="Course selection shortcuts">
                                <button type="button" class="btn btn-quiet btn-sm" id="selectAllCourses">All</button>
                                <button type="button" class="btn btn-quiet btn-sm" id="selectTheoryCourses">Theory only</button>
                                <button type="button" class="btn btn-quiet btn-sm" id="selectSessionalCourses">Sessional only</button>
                                <button type="button" class="btn btn-quiet btn-sm" id="deselectAllCourses">None</button>
                            </div>
                        </div>

                        @if($courses->count() > 0)
                        <div class="check-grid">
                            @foreach($courses as $course)
                            <div class="form-check">
                                <input class="form-check-input course-checkbox" type="checkbox" name="courses[]"
                                       value="{{$course->id}}" id="course_{{$course->id}}"
                                       data-course-code="{{$course->course_code}}">
                                <label class="form-check-label" for="course_{{$course->id}}">
                                    <span class="fw-semibold num">{{$course->course_code}}</span> {{$course->course_title}}
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="callout">
                            No course has verified students yet, so there is no one to send to.
                        </div>
                        @endif
                    </div>

                    <div id="template_preview" hidden>
                        <label class="form-label">Email preview</label>
                        <div class="mail-preview" id="preview_content"></div>
                    </div>
                    <div class="form-error" id="customizedMailError" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-quiet" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" {{ $courses->count() > 0 ? '' : 'disabled' }}>
                        <i class="bi bi-send"></i> Send mail
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Template preview functionality
        const templates = @json($predefinedTemplates);
        const templatesRequiringDeadline = ['ct_marks', 'sessional_marks', 'question_manuscript', 'answer_script'];

        // Saved general templates that can be loaded into the general mail form
        const savedTemplates = @json($savedGeneralTemplates);

        const customizedModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('customizedMailModal'));
        const generalModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('generalMailModal'));

        function showFormError(selector, message) {
            $(selector).text(message).toggleClass('is-shown', !!message);
        }

        $('#template_type').on('change', function() {
            const templateKey = $(this).val();

            // Handle deadline requirement
            const deadlineRequired = !!templateKey && templatesRequiringDeadline.includes(templateKey);
            $('#customized_deadline').prop('required', deadlineRequired);
            $('.deadline-optional').prop('hidden', deadlineRequired);

            // Template preview
            if (templateKey && templates[templateKey]) {
                const template = templates[templateKey];
                $('#preview_content').html(`
                    <p class="mail-preview-subject"></p>
                    ${template.content}
                `);
                $('#preview_content .mail-preview-subject').text(template.subject);
                $('#template_preview').prop('hidden', false);
            } else {
                $('#template_preview').prop('hidden', true);
            }
        });

        // Course selection shortcuts
        function courseNumber(checkbox) {
            // Odd-numbered courses are theory, even-numbered are sessional
            const numericPart = String($(checkbox).data('course-code')).match(/\d+/);
            return numericPart ? parseInt(numericPart[0]) : null;
        }

        $('#selectAllCourses').on('click', function() {
            $('.course-checkbox').prop('checked', true);
        });

        $('#deselectAllCourses').on('click', function() {
            $('.course-checkbox').prop('checked', false);
        });

        $('#selectTheoryCourses').on('click', function() {
            $('.course-checkbox').each(function() {
                const number = courseNumber(this);
                $(this).prop('checked', number !== null && number % 2 === 1);
            });
        });

        $('#selectSessionalCourses').on('click', function() {
            $('.course-checkbox').each(function() {
                const number = courseNumber(this);
                $(this).prop('checked', number !== null && number % 2 === 0);
            });
        });

        // Quick send functionality
        $('.quick-send').on('click', function() {
            // Set the template in customized modal
            $('#template_type').val($(this).data('template')).trigger('change');
            showFormError('#customizedMailError', '');
            customizedModal.show();
        });

        // Load a saved general template into the general mail form
        function useSavedTemplate(templateId) {
            const saved = savedTemplates[templateId];
            if (!saved) {
                return;
            }
            $('#general_subject').val(saved.subject);
            $('#general_content').val(saved.content);
            generalModal.show();
        }

        $('.use-template').on('click', function() {
            useSavedTemplate($(this).data('template-id'));
        });

        const templateToUse = new URLSearchParams(location.search).get('use');
        if (templateToUse) {
            useSavedTemplate(templateToUse);
            // Drop the parameter so a reload does not reopen the dialog
            history.replaceState(null, '', location.pathname);
        }

        // Handle delete button click
        $('.delete-template').on('click', function() {
            const templateId = $(this).data('template-id');
            const templateName = $(this).data('template-name');

            confirmAction({
                title: 'Delete this template?',
                message: '"' + templateName + '" will be deleted. This cannot be undone.',
                confirmLabel: 'Delete template',
                danger: true
            }, function() {
                $('#deleteTemplateId').val(templateId);
                submitForm(document.getElementById('deleteForm'));
            });
        });

        // Form validation for customized mail
        $('form[action*="send-customized"]').on('submit', function(e) {
            const selectedCourses = $('.course-checkbox:checked').length;
            const templateType = $('#template_type').val();
            const deadline = $('#customized_deadline').val();

            if (selectedCourses === 0) {
                e.preventDefault();
                showFormError('#customizedMailError', 'Please select at least one course.');
                return false;
            }

            if (templatesRequiringDeadline.includes(templateType) && !deadline) {
                e.preventDefault();
                showFormError('#customizedMailError', 'Deadline is required for the selected template.');
                $('#customized_deadline').focus();
                return false;
            }

            showFormError('#customizedMailError', '');
        });

        // Handle file attachments preview
        function resetAttachments(input) {
            input.value = '';
            $('#general_attachment_preview').empty();
            $('#generalSendButton span').text('Send mail');
        }

        $('#general_attachments').on('change', function() {
            const files = Array.from(this.files);
            const previewContainer = $('#general_attachment_preview');
            previewContainer.empty();
            showFormError('#generalMailError', '');

            if (files.length === 0) {
                $('#generalSendButton span').text('Send mail');
                return;
            }

            const maxFileSize = 10 * 1024 * 1024; // 10MB
            const maxTotalSize = 50 * 1024 * 1024; // 50MB
            let totalSize = 0;

            for (const file of files) {
                totalSize += file.size;

                if (file.size > maxFileSize) {
                    showFormError('#generalMailError', `File "${file.name}" is too large. Maximum file size is 10MB.`);
                    resetAttachments(this);
                    return;
                }
            }

            if (totalSize > maxTotalSize) {
                showFormError('#generalMailError', 'Total file size exceeds 50MB. Please reduce the number or size of files.');
                resetAttachments(this);
                return;
            }

            const list = $('<div class="tag-list"></div>');
            files.forEach(function(file) {
                list.append($('<span class="tag"></span>').text(file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)'));
            });
            previewContainer.append(list);

            $('#generalSendButton span').text('Send mail with ' + files.length + (files.length === 1 ? ' attachment' : ' attachments'));
        });

        // Reset general mail modal when closed
        $('#generalMailModal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $('#general_attachment_preview').empty();
            $('#generalSendButton span').text('Send mail');
            showFormError('#generalMailError', '');
        });
    });
</script>
@endsection
