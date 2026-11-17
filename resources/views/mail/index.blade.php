@extends('layouts.exam')

@section('title', 'Mail')
@section('tab', 'mail')

@section('exam-content')
@php
    // Saved general templates, keyed by ID, for the "Use in a general mail" action
    $savedGeneralTemplates = $mailTemplates->where('type', 'general')->keyBy('id')->map(function($template) {
        return ['subject' => $template->subject, 'content' => $template->content];
    });

    // After a rejected send, reopen the dialog that was submitted, with its values and the reasons
    $failedForm = $errors->any() ? old('form') : null;
    $generalAudience = $failedForm === 'general' ? old('recipients', 'both') : 'both';
    $oldCourses = $failedForm === 'customized' ? array_map('strval', (array) old('courses', [])) : [];
    $audiences = [
        'both' => 'Both teachers',
        'internal' => 'Internal (Teacher 1)',
        'external' => 'External (Teacher 2)',
    ];
@endphp

@if($errors->any() && !in_array($failedForm, ['general', 'customized']))
    <div class="alert alert-danger">
        <strong>The mail was not sent.</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
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
                                            <span class="tag course-chip" data-bs-toggle="tooltip" title="{{$course->course_title}}">{{$course->course_code}}</span>
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
            <form method="POST" action="/mail/{{$exam->id}}/send-general" enctype="multipart/form-data" id="generalMailForm">
                @csrf
                <input type="hidden" name="form" value="general">
                <div class="modal-body">
                    @if($failedForm === 'general')
                        <div class="alert alert-danger" data-server-errors>
                            <strong>The mail was not sent.</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <div class="mt-1">If you attached files, choose them again before sending.</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <span class="form-label d-block">Send to</span>
                        <div class="segmented" role="radiogroup" aria-label="Send to">
                            @foreach($audiences as $value => $label)
                                <input type="radio" class="btn-check" name="recipients" id="recipients_{{$value}}" value="{{$value}}" {{ $generalAudience === $value ? 'checked' : '' }}>
                                <label for="recipients_{{$value}}">{{$label}} <span class="seg-count">{{$recipientCounts[$value]}}</span></label>
                            @endforeach
                        </div>
                        <div class="form-hint" id="generalRecipientNote"></div>
                    </div>

                    <div class="mb-3">
                        <label for="general_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="general_subject" name="subject" maxlength="255" value="{{ $failedForm === 'general' ? old('subject') : '' }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="general_content" class="form-label">Message</label>
                        <textarea class="form-control" id="general_content" name="content" rows="8" required>{{ $failedForm === 'general' ? old('content') : '' }}</textarea>
                        <div class="form-hint">
                            These are filled in for each teacher: <span class="tag">[Teacher's Name]</span> <span class="tag">[Exam Name]</span> <span class="tag">[Deadline Date]</span> <span class="tag">[Course List]</span>.
                            The course list shows the courses the mail goes to that teacher for. Name, exam and deadline also work in the subject.
                            The message is sent as HTML, so wrap each paragraph in <span class="tag">&lt;p&gt;...&lt;/p&gt;</span>; plain line breaks are not kept.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="general_deadline" class="form-label">Deadline <span class="optional" id="generalDeadlineOptional">(optional)</span></label>
                            <input type="date" class="form-control" id="general_deadline" name="deadline" value="{{ $failedForm === 'general' ? old('deadline') : '' }}">
                            <div class="form-hint">Needed when the subject or message uses [Deadline Date].</div>
                        </div>
                        <div class="col-md-7">
                            <label for="general_attachments" class="form-label">Attachments <span class="optional">(optional)</span></label>
                            <input type="file" class="form-control" id="general_attachments" name="attachments[]" multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png">
                            <div class="form-hint">PDF, Word, Excel, PowerPoint, text, JPG or PNG. Up to {{$attachmentLimits['count']}} files, {{$attachmentLimits['fileLabel']}} each, {{$attachmentLimits['totalLabel']}} in total.</div>
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
                <input type="hidden" name="form" value="customized">
                <div class="modal-body">
                    @if($failedForm === 'customized')
                        <div class="alert alert-danger" data-server-errors>
                            <strong>The mail was not sent.</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="template_type" class="form-label">Request</label>
                            <select class="form-select" id="template_type" name="template_type" required>
                                <option value="">Choose a request</option>
                                @foreach($predefinedTemplates as $key => $template)
                                    <option value="{{$key}}" {{ $failedForm === 'customized' && old('template_type') === $key ? 'selected' : '' }}>{{$template['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="customized_deadline" class="form-label">
                                Deadline
                                <span class="optional deadline-optional">(optional)</span>
                            </label>
                            <input type="date" class="form-control" id="customized_deadline" name="deadline" value="{{ $failedForm === 'customized' ? old('deadline') : '' }}">
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
                                       data-course-code="{{$course->course_code}}" {{ in_array((string) $course->id, $oldCourses, true) ? 'checked' : '' }}>
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
            $('#general_content').val(saved.content).trigger('input');
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

        // ---- General mail ----
        const recipientCounts = @json($recipientCounts);
        const attachmentLimits = @json($attachmentLimits);
        const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];
        const audienceNotes = {
            both: 'every teacher assigned to a course that has verified students in this exam',
            internal: 'the internal teacher (Teacher 1) of each course that has verified students in this exam',
            external: 'the external teacher (Teacher 2) of each course that has verified students in this exam'
        };

        function selectedAudience() {
            return $('input[name="recipients"]:checked').val() || 'both';
        }

        function updateRecipientNote() {
            const audience = selectedAudience();
            const count = recipientCounts[audience] || 0;
            const note = count === 0
                ? 'Nobody matches this choice yet, so nothing can be sent. Assign teachers on the Teachers tab first.'
                : 'Goes to ' + count + (count === 1 ? ' teacher: ' : ' teachers: ') + audienceNotes[audience] + '. Each teacher gets one email.';
            $('#generalRecipientNote').text(note).toggleClass('text-danger', count === 0);
            $('#generalSendButton').prop('disabled', count === 0);
        }

        $('input[name="recipients"]').on('change', updateRecipientNote);

        function usesDeadline() {
            return ($('#general_subject').val() + $('#general_content').val()).indexOf('[Deadline Date]') !== -1;
        }

        function updateDeadlineRequirement() {
            const required = usesDeadline();
            $('#general_deadline').prop('required', required);
            $('#generalDeadlineOptional').prop('hidden', required);
        }

        $('#general_subject, #general_content').on('input', updateDeadlineRequirement);

        function formatSize(bytes) {
            return bytes >= 1024 * 1024
                ? (bytes / 1024 / 1024).toFixed(bytes % (1024 * 1024) === 0 ? 0 : 1) + ' MB'
                : Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

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

            if (files.length > attachmentLimits.count) {
                showFormError('#generalMailError', `Attach at most ${attachmentLimits.count} files.`);
                resetAttachments(this);
                return;
            }

            let totalSize = 0;
            for (const file of files) {
                totalSize += file.size;
                const extension = file.name.split('.').pop().toLowerCase();

                if (!allowedExtensions.includes(extension)) {
                    showFormError('#generalMailError', `"${file.name}" cannot be attached. Use PDF, Word, Excel, PowerPoint, text, JPG or PNG files.`);
                    resetAttachments(this);
                    return;
                }

                if (file.size > attachmentLimits.file) {
                    showFormError('#generalMailError', `"${file.name}" is ${formatSize(file.size)}. Each attachment must be ${attachmentLimits.fileLabel} or smaller.`);
                    resetAttachments(this);
                    return;
                }
            }

            if (totalSize > attachmentLimits.total) {
                showFormError('#generalMailError', `These files add up to ${formatSize(totalSize)}. Together they must be ${attachmentLimits.totalLabel} or smaller.`);
                resetAttachments(this);
                return;
            }

            const list = $('<div class="tag-list"></div>');
            files.forEach(function(file) {
                list.append($('<span class="tag"></span>').text(file.name + ' (' + formatSize(file.size) + ')'));
            });
            previewContainer.append(list);

            $('#generalSendButton span').text('Send mail with ' + files.length + (files.length === 1 ? ' attachment' : ' attachments'));
        });

        $('#generalMailForm').on('submit', function(e) {
            showFormError('#generalMailError', '');

            if ((recipientCounts[selectedAudience()] || 0) === 0) {
                e.preventDefault();
                showFormError('#generalMailError', 'Nobody matches this choice, so nothing can be sent.');
                return;
            }

            if (usesDeadline() && !$('#general_deadline').val()) {
                e.preventDefault();
                showFormError('#generalMailError', 'The mail uses [Deadline Date], so choose a deadline.');
                $('#general_deadline').focus();
            }
        });

        // Clear the general mail dialog when it closes
        $('#generalMailModal').on('hidden.bs.modal', function() {
            $('#general_subject').val('');
            $('#general_content').val('');
            $('#general_deadline').val('');
            $('#recipients_both').prop('checked', true);
            resetAttachments(document.getElementById('general_attachments'));
            $(this).find('[data-server-errors]').remove();
            showFormError('#generalMailError', '');
            updateRecipientNote();
            updateDeadlineRequirement();
        });

        $('#customizedMailModal').on('hidden.bs.modal', function() {
            $(this).find('[data-server-errors]').remove();
        });

        updateRecipientNote();
        updateDeadlineRequirement();

        // A send the server rejected: reopen that dialog with the reasons
        const failedForm = @json($failedForm);
        if (failedForm === 'general') {
            generalModal.show();
        } else if (failedForm === 'customized') {
            $('#template_type').trigger('change');
            customizedModal.show();
        }
    });
</script>
@endsection
