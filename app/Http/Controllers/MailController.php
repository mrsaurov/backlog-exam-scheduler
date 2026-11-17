<?php

namespace App\Http\Controllers;

use App\Mail\ExamNotificationMail;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\AvailableExam;
use App\Models\Course;
use App\Models\CourseExamMapping;
use App\Models\CourseTeacherAssignment;
use App\Models\RegisteredStudent;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class MailController extends Controller
{
    /**
     * Get predefined templates
     */
    private function getPredefinedTemplates()
    {
        return [
            'ct_marks' => [
                'name' => 'Submit CT Marks',
                'subject' => 'Submission of Class Test Marks for [Exam Name]',
                'content' => '<p>Dear [Teacher\'s Name],</p>

<p>You are kindly requested to submit the Class Test marks for the following course(s) in <strong>[Exam Name]</strong>:</p>

<div class="course-list">
[Course List]
</div>

<p>Please ensure that the marks are submitted to the department office on or before <strong>[Deadline Date]</strong>. Timely submission is essential for smooth progress of academic activities.</p>

<p>Your cooperation in this regard will be highly appreciated.</p>

<p>Sincerely,<br>
<strong>Head</strong><br>
Department of Computer Science & Engineering<br>
Rajshahi University of Engineering & Technology (RUET)</p>'
            ],
            'sessional_marks' => [
                'name' => 'Submit Sessional Marks',
                'subject' => 'Submission of Sessional Marks for [Exam Name]',
                'content' => '<p>Dear [Teacher\'s Name],</p>

<p>You are kindly requested to submit the Sessional marks for the following course(s) in <strong>[Exam Name]</strong>:</p>

<div class="course-list">
[Course List]
</div>

<p>Please ensure that the marks are submitted to the department office on or before <strong>[Deadline Date]</strong>. Timely submission is essential for smooth progress of academic activities.</p>

<p>Your cooperation in this regard will be highly appreciated.</p>

<p>Sincerely,<br>
<strong>Head</strong><br>
Department of Computer Science & Engineering<br>
Rajshahi University of Engineering & Technology (RUET)</p>'
            ],
            'question_manuscript' => [
                'name' => 'Submit Question Manuscript',
                'subject' => 'Submission of Question Manuscript for [Exam Name]',
                'content' => '<p>Dear [Teacher\'s Name],</p>

<p>You are kindly requested to submit the Question Manuscript for the following course(s) in <strong>[Exam Name]</strong>:</p>

<div class="course-list">
[Course List]
</div>

<p>Please ensure that the manuscripts are submitted to the department office on or before <strong>[Deadline Date]</strong>. Timely submission is essential for smooth progress of academic activities.</p>

<p>Your cooperation in this regard will be highly appreciated.</p>

<p>Sincerely,<br>
<strong>Head</strong><br>
Department of Computer Science & Engineering<br>
Rajshahi University of Engineering & Technology (RUET)</p>'
            ],
            'answer_script' => [
                'name' => 'Submit Answer Script',
                'subject' => 'Submission of Evaluated Answer Scripts for [Exam Name]',
                'content' => '<p>Dear [Teacher\'s Name],</p>

<p>You are kindly requested to submit the evaluated answer scripts for the following course(s) in <strong>[Exam Name]</strong>:</p>

<div class="course-list">
[Course List]
</div>

<p>Please ensure that the evaluated answer scripts are submitted to the department office on or before <strong>[Deadline Date]</strong>. Timely submission is essential for smooth completion of examination process.</p>

<p>Your cooperation in this regard will be highly appreciated.</p>

<p>Sincerely,<br>
<strong>Head</strong><br>
Department of Computer Science & Engineering<br>
Rajshahi University of Engineering & Technology (RUET)</p>'
            ]
        ];
    }

    /**
     * Get IDs of courses that have verified student registrations in an exam
     */
    private function activeCourseIds($examid)
    {
        $registeredStudents = RegisteredStudent::where('examid', $examid)
            ->where('verified', true)
            ->get();

        $coursesWithStudents = [];
        foreach($registeredStudents as $student) {
            if($student->course1) $coursesWithStudents[] = $student->course1;
            if($student->course2) $coursesWithStudents[] = $student->course2;
            if($student->course3) $coursesWithStudents[] = $student->course3;
            if($student->course4) $coursesWithStudents[] = $student->course4;
            if($student->course5) $coursesWithStudents[] = $student->course5;
        }

        return array_values(array_unique($coursesWithStudents));
    }

    /**
     * Teachers who receive a general mail, with the courses they receive it for.
     *
     * $audience is "both", "internal" (Teacher 1 of a course) or "external" (Teacher 2).
     * Only courses with verified students in this exam count.
     * Returns [teacher_id => ['teacher' => Teacher, 'courses' => Collection of Course]].
     */
    private function generalRecipients($examid, $audience)
    {
        $assignments = CourseTeacherAssignment::with(['teacher', 'course'])
            ->where('exam_id', $examid)
            ->whereIn('course_id', $this->activeCourseIds($examid))
            ->orderBy('id')
            ->get();

        $recipients = [];
        foreach ($assignments->groupBy('course_id') as $courseAssignments) {
            // A course's first assignment is Teacher 1 (internal), the second Teacher 2 (external)
            foreach ($courseAssignments->values() as $index => $assignment) {
                $position = $index === 0 ? 'internal' : 'external';
                if (($audience !== 'both' && $audience !== $position) || !$assignment->teacher || !$assignment->course) {
                    continue;
                }

                $teacherId = $assignment->teacher_id;
                if (!isset($recipients[$teacherId])) {
                    $recipients[$teacherId] = ['teacher' => $assignment->teacher, 'courses' => collect()];
                }
                $recipients[$teacherId]['courses']->push($assignment->course);
            }
        }

        // Alphabetical, so the tracker lists them in a predictable order
        uasort($recipients, function ($a, $b) {
            return strcmp($a['teacher']->name, $b['teacher']->name);
        });

        return $recipients;
    }

    /**
     * Label for a general mail audience, shown in the tracker
     */
    private function audienceLabel($audience)
    {
        $labels = [
            'internal' => 'Internal (Teacher 1) only',
            'external' => 'External (Teacher 2) only',
        ];

        return $labels[$audience] ?? null;
    }

    /**
     * The [Course List] block used in mail bodies
     */
    private function courseListHtml($courses)
    {
        return $courses->map(function ($course) {
            return '<div style="margin-bottom: 8px;"><strong>' . e($course->course_code) . '</strong> – <strong>' . e($course->course_title) . '</strong></div>';
        })->implode('');
    }

    /**
     * Attachment limits the server will actually accept. PHP's upload settings can be
     * lower than the 10 MB per file and 50 MB per mail that the form allows.
     */
    private function attachmentLimits()
    {
        $toBytes = function ($value) {
            $value = trim((string) $value);
            if ($value === '') {
                return 0;
            }
            $number = (float) $value;
            switch (strtolower(substr($value, -1))) {
                case 'g': return (int) ($number * 1024 * 1024 * 1024);
                case 'm': return (int) ($number * 1024 * 1024);
                case 'k': return (int) ($number * 1024);
                default: return (int) $number;
            }
        };

        $perFile = 10 * 1024 * 1024;
        $uploadMax = $toBytes(ini_get('upload_max_filesize'));
        if ($uploadMax > 0) {
            $perFile = min($perFile, $uploadMax);
        }

        $total = 50 * 1024 * 1024;
        $postMax = $toBytes(ini_get('post_max_size'));
        if ($postMax > 0) {
            // Leave room for the subject and message in the same request
            $total = min($total, max($postMax - 256 * 1024, 0));
        }

        $count = 10;
        $maxUploads = (int) ini_get('max_file_uploads');
        if ($maxUploads > 0) {
            $count = min($count, $maxUploads);
        }

        $label = function ($bytes) {
            $mb = $bytes / (1024 * 1024);
            return ($mb >= 1 ? rtrim(rtrim(number_format($mb, 1), '0'), '.') . ' MB' : round($bytes / 1024) . ' KB');
        };

        return [
            'file' => $perFile,
            'total' => $total,
            'count' => $count,
            'fileLabel' => $label($perFile),
            'totalLabel' => $label($total),
        ];
    }

    /**
     * Send a mail and record the attempt in the mail tracker
     */
    private function sendAndLog($examid, $recipient, $mailType, $templateName, $subject, $content, $attachmentPaths = [], $resentFromId = null)
    {
        $status = 'sent';
        $errorMessage = null;

        try {
            // Send email using Laravel Mail
            Mail::to($recipient['email'])->send(new ExamNotificationMail(
                $subject,
                $content,
                $attachmentPaths
            ));
        } catch (Exception $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            \Log::error('Failed to send email to ' . $recipient['email'] . ': ' . $errorMessage);
        }

        try {
            MailLog::create([
                'exam_id' => $examid,
                'teacher_id' => $recipient['teacher_id'] ?? null,
                'recipient_name' => $recipient['name'],
                'recipient_email' => $recipient['email'],
                'mail_type' => $mailType,
                'template_name' => $templateName,
                'subject' => $subject,
                'content' => $content,
                'attachment_names' => count($attachmentPaths) > 0 ? array_column($attachmentPaths, 'name') : null,
                'status' => $status,
                'error_message' => $errorMessage,
                'resent_from_id' => $resentFromId,
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to record mail log for ' . $recipient['email'] . ': ' . $e->getMessage());
        }

        return $status === 'sent';
    }

    /**
     * Display mail templates for an exam
     */
    public function index($examid)
    {
        $exam = AvailableExam::findOrFail($examid);
        $mailTemplates = MailTemplate::where('exam_id', $examid)->orderBy('created_at', 'desc')->get();
        
        // Get courses that have verified student registrations (same logic as TeacherController)
        $registeredStudents = RegisteredStudent::where('examid', $examid)
            ->where('verified', true)
            ->get();
        
        $coursesWithStudents = [];
        foreach($registeredStudents as $student) {
            if($student->course1) $coursesWithStudents[] = $student->course1;
            if($student->course2) $coursesWithStudents[] = $student->course2;
            if($student->course3) $coursesWithStudents[] = $student->course3;
            if($student->course4) $coursesWithStudents[] = $student->course4;
            if($student->course5) $coursesWithStudents[] = $student->course5;
        }
        
        // Get unique course IDs that have verified student registrations
        $courseIds = array_unique($coursesWithStudents);
        
        // Load only courses that have verified student registrations
        $courses = Course::whereIn('id', $courseIds)->orderBy('course_code')->get();
        
        // Get predefined templates
        $predefinedTemplates = $this->getPredefinedTemplates();

        // How many teachers a general mail would reach for each audience choice
        $recipientCounts = [];
        foreach (['both', 'internal', 'external'] as $audience) {
            $recipientCounts[$audience] = count($this->generalRecipients($examid, $audience));
        }
        
        return view('mail.index')->with([
            'exam' => $exam,
            'mailTemplates' => $mailTemplates,
            'courses' => $courses,
            'predefinedTemplates' => $predefinedTemplates,
            'recipientCounts' => $recipientCounts,
            'attachmentLimits' => $this->attachmentLimits()
        ]);
    }

    /**
     * Show form for creating/editing mail template
     */
    public function form($examid, $templateid = 0)
    {
        $exam = AvailableExam::findOrFail($examid);
        
        // Get courses that have verified student registrations (same logic as index method)
        $registeredStudents = RegisteredStudent::where('examid', $examid)
            ->where('verified', true)
            ->get();
        
        $coursesWithStudents = [];
        foreach($registeredStudents as $student) {
            if($student->course1) $coursesWithStudents[] = $student->course1;
            if($student->course2) $coursesWithStudents[] = $student->course2;
            if($student->course3) $coursesWithStudents[] = $student->course3;
            if($student->course4) $coursesWithStudents[] = $student->course4;
            if($student->course5) $coursesWithStudents[] = $student->course5;
        }
        
        // Get unique course IDs that have verified student registrations
        $courseIds = array_unique($coursesWithStudents);
        
        // Load only courses that have verified student registrations
        $courses = Course::whereIn('id', $courseIds)->orderBy('course_code')->get();
        
        if ($templateid == 0) {
            $template = (object)[
                'id' => 0,
                'name' => '',
                'type' => 'general',
                'subject' => '',
                'content' => '',
                'assigned_courses' => []
            ];
        } else {
            $template = MailTemplate::findOrFail($templateid);
        }
        
        return view('mail.form')->with([
            'exam' => $exam,
            'template' => $template,
            'courses' => $courses,
            'isNew' => $templateid == 0
        ]);
    }

    /**
     * Store or update mail template
     */
    public function store(Request $request)
    {
        $operation = $request->input('submit');
        $examId = $request->input('exam_id');
        $templateId = $request->input('template_id');

        try {
            if ($operation === 'delete') {
                $request->validate([
                    'exam_id' => 'required|integer|exists:available_exams,id',
                    'template_id' => 'required|integer|exists:mail_templates,id',
                ]);

                $template = MailTemplate::findOrFail($templateId);
                $template->delete();
                return redirect('/mail/' . $examId)->with('success', 'Mail template deleted successfully!');
            } else {
                // Validate for create/update
                $validationRules = [
                    'name' => 'required|string|max:255',
                    'type' => 'required|in:general,customized',
                    'subject' => 'required|string|max:500',
                    'content' => 'required|string',
                    'exam_id' => 'required|integer|exists:available_exams,id',
                ];
                
                if ($request->input('type') === 'customized') {
                    $validationRules['assigned_courses'] = 'required|array|min:1';
                    $validationRules['assigned_courses.*'] = 'exists:courses,id';
                }
                
                $request->validate($validationRules);

                $templateData = [
                    'exam_id' => $examId,
                    'name' => $request->input('name'),
                    'type' => $request->input('type'),
                    'subject' => $request->input('subject'),
                    'content' => $request->input('content'),
                    'assigned_courses' => $request->input('type') === 'customized' ? $request->input('assigned_courses') : null,
                ];

                if ($operation === 'create') {
                    MailTemplate::create($templateData);
                    return redirect('/mail/' . $examId)->with('success', 'Mail template created successfully!');
                } elseif ($operation === 'update') {
                    $request->validate([
                        'template_id' => 'required|integer|exists:mail_templates,id',
                    ]);
                    
                    $template = MailTemplate::findOrFail($templateId);
                    $template->update($templateData);
                    return redirect('/mail/' . $examId)->with('success', 'Mail template updated successfully!');
                }
            }
        } catch (Exception $e) {
            return redirect('/mail/' . $examId)->with('error', 'Error processing mail template: ' . $e->getMessage());
        }

        return redirect('/mail/' . $examId);
    }

    /**
     * Preview mail template with recipient information
     */
    public function preview($templateid)
    {
        $template = MailTemplate::with('exam')->findOrFail($templateid);
        
        $activeCourseIds = $this->activeCourseIds($template->exam_id);

        // Get recipients based on template type
        if ($template->type === 'general') {
            // Get all teachers assigned to courses with verified students in this exam
            $recipients = Teacher::whereHas('courseAssignments', function($query) use ($template, $activeCourseIds) {
                $query->where('exam_id', $template->exam_id)
                      ->whereIn('course_id', $activeCourseIds);
            })->get();
        } else {
            // Get teachers assigned to specific courses with verified students
            $templateCourseIds = array_intersect($template->assigned_courses ?? [], $activeCourseIds);
            $recipients = Teacher::whereHas('courseAssignments', function($query) use ($template, $templateCourseIds) {
                $query->where('exam_id', $template->exam_id)
                      ->whereIn('course_id', $templateCourseIds);
            })->with(['courseAssignments' => function($query) use ($template, $templateCourseIds) {
                $query->where('exam_id', $template->exam_id)
                      ->whereIn('course_id', $templateCourseIds)
                      ->with('course');
            }])->get();
        }
        
        return view('mail.preview')->with([
            'template' => $template,
            'recipients' => $recipients
        ]);
    }

    /**
     * Send general mail to all teachers
     */
    public function sendGeneral(Request $request, $examid)
    {
        $limits = $this->attachmentLimits();
        $usesDeadline = str_contains($request->input('subject', '') . $request->input('content', ''), '[Deadline Date]');

        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'deadline' => [$usesDeadline ? 'required' : 'nullable', 'date'],
            'recipients' => 'nullable|in:both,internal,external',
            'attachments' => 'nullable|array|max:' . $limits['count'],
            'attachments.*' => 'file|max:' . intdiv($limits['file'], 1024) . '|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png'
        ], [
            'deadline.required' => 'The mail uses [Deadline Date], so choose a deadline.',
            'attachments.max' => 'Attach at most :max files.',
            'attachments.*.uploaded' => 'An attachment could not be uploaded. Each file must be ' . $limits['fileLabel'] . ' or smaller.',
            'attachments.*.max' => 'Each attachment must be ' . $limits['fileLabel'] . ' or smaller.',
            'attachments.*.mimes' => 'Attachments must be PDF, Word, Excel, PowerPoint, text, JPG or PNG files.'
        ]);

        $audience = $request->input('recipients') ?: 'both';
        $attachmentPaths = [];

        try {
            $exam = AvailableExam::findOrFail($examid);

            $recipients = $this->generalRecipients($examid, $audience);
            if (count($recipients) === 0) {
                $reasons = [
                    'both' => 'no teacher is assigned to a course with verified students in this exam.',
                    'internal' => 'no course with verified students in this exam has an internal teacher (Teacher 1).',
                    'external' => 'no course with verified students in this exam has an external teacher (Teacher 2).',
                ];
                return redirect('/mail/' . $examid)->with('error', 'No email was sent: ' . $reasons[$audience]);
            }
            
            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $filename = time() . '_' . uniqid() . '_' . $originalName;
                    $path = $file->storeAs('mail_attachments', $filename);
                    
                    $attachmentPaths[] = [
                        'path' => storage_path('app/' . $path),
                        'name' => $originalName,
                        'mime' => $file->getMimeType()
                    ];
                }
            }

            $deadline = $request->deadline ? date('F j, Y', strtotime($request->deadline)) : null;
            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($recipients as $entry) {
                $teacher = $entry['teacher'];

                // Personalize the subject (plain text)
                $subjectReplacements = [
                    '[Teacher\'s Name]' => $teacher->name,
                    '[Exam Name]' => $exam->exam_name,
                ];
                if ($deadline) {
                    $subjectReplacements['[Deadline Date]'] = $deadline;
                }
                $personalizedSubject = strtr($request->subject, $subjectReplacements);

                // Personalize the content (HTML)
                $personalizedContent = $request->content;
                if ($deadline) {
                    $personalizedContent = str_replace('<strong>[Deadline Date]</strong>', '[Deadline Date]', $personalizedContent);
                }
                $personalizedContent = strtr($personalizedContent, [
                    '[Teacher\'s Name]' => e($teacher->name),
                    '[Exam Name]' => e($exam->exam_name),
                    '[Course List]' => $this->courseListHtml($entry['courses']),
                    '[Deadline Date]' => $deadline ? '<strong>' . $deadline . '</strong>' : '[Deadline Date]',
                ]);
                
                $recipient = ['teacher_id' => $teacher->id, 'name' => $teacher->name, 'email' => $teacher->email];
                if ($this->sendAndLog($examid, $recipient, 'general', $this->audienceLabel($audience), $personalizedSubject, $personalizedContent, $attachmentPaths)) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }

            // Clean up temporary attachment files
            foreach ($attachmentPaths as $attachment) {
                if (file_exists($attachment['path'])) {
                    unlink($attachment['path']);
                }
            }

            $teachers = function ($count) {
                return $count . ' ' . ($count === 1 ? 'teacher' : 'teachers');
            };
            if ($failedCount === 0) {
                return redirect('/mail/' . $examid)->with('success', 'General mail sent to ' . $teachers($sentCount) . '.');
            }
            if ($sentCount === 0) {
                return redirect('/mail/' . $examid)->with('error', 'General mail could not be sent to any of the ' . $teachers($failedCount) . '. The Tracker shows the reason.');
            }
            return redirect('/mail/' . $examid)->with('warning', 'General mail sent to ' . $teachers($sentCount) . ', but failed for ' . $failedCount . '. The Tracker shows the reason.');
        } catch (Exception $e) {
            // Clean up any uploaded files in case of error
            foreach ($attachmentPaths as $attachment) {
                if (file_exists($attachment['path'])) {
                    unlink($attachment['path']);
                }
            }
            return redirect('/mail/' . $examid)->with('error', 'Error sending mail: ' . $e->getMessage());
        }
    }

    /**
     * Send customized mail based on template and selected courses
     */
    public function sendCustomized(Request $request, $examid)
    {
        // Base validation rules
        $rules = [
            'template_type' => 'required|in:ct_marks,sessional_marks,question_manuscript,answer_script',
            'courses' => 'required|array|min:1',
            'courses.*' => 'exists:courses,id',
        ];
        
        // Make deadline required for specific templates
        $templatesRequiringDeadline = ['ct_marks', 'sessional_marks', 'question_manuscript', 'answer_script'];
        if (in_array($request->template_type, $templatesRequiringDeadline)) {
            $rules['deadline'] = 'required|date';
        } else {
            $rules['deadline'] = 'nullable|date';
        }
        
        $request->validate($rules, [
            'template_type.required' => 'Choose a request.',
            'courses.required' => 'Choose at least one course.',
            'deadline.required' => 'Choose a deadline for this request.',
        ]);

        try {
            $exam = AvailableExam::findOrFail($examid);
            $selectedCourses = Course::whereIn('id', $request->courses)->get();
            $predefinedTemplates = $this->getPredefinedTemplates();
            $template = $predefinedTemplates[$request->template_type];

            // Get courses that have verified student registrations (same logic as TeacherController)
            $registeredStudents = RegisteredStudent::where('examid', $examid)
                ->where('verified', true)
                ->get();
            
            $coursesWithStudents = [];
            foreach($registeredStudents as $student) {
                if($student->course1) $coursesWithStudents[] = $student->course1;
                if($student->course2) $coursesWithStudents[] = $student->course2;
                if($student->course3) $coursesWithStudents[] = $student->course3;
                if($student->course4) $coursesWithStudents[] = $student->course4;
                if($student->course5) $coursesWithStudents[] = $student->course5;
            }
            $activeCoursesIds = array_unique($coursesWithStudents);
            
            // Filter selected courses to only include those with verified student registrations
            $validCourseIds = array_intersect($request->courses, $activeCoursesIds);
            
            if (empty($validCourseIds)) {
                return redirect('/mail/' . $examid)->with('error', 'No valid courses selected. Selected courses must have verified student registrations.');
            }

            // Define which templates should only go to Teacher 1
            $teacher1OnlyTemplates = ['ct_marks', 'sessional_marks'];
            // Templates that go to both teachers: 'question_manuscript', 'answer_script'
            
            $teachers = collect();
            
            if (in_array($request->template_type, $teacher1OnlyTemplates)) {
                // Get only Teacher 1 (first assignment) for each course that has verified students
                $teacher1Assignments = \DB::table('course_teacher_assignments as cta1')
                    ->select('cta1.*')
                    ->where('cta1.exam_id', $examid)
                    ->whereIn('cta1.course_id', $validCourseIds)
                    ->whereNotExists(function($query) use ($examid) {
                        $query->select(\DB::raw(1))
                              ->from('course_teacher_assignments as cta2')
                              ->whereRaw('cta2.course_id = cta1.course_id')
                              ->where('cta2.exam_id', $examid)
                              ->whereRaw('cta2.id < cta1.id');
                    })
                    ->pluck('teacher_id')
                    ->unique();
                
                $teachers = Teacher::whereIn('id', $teacher1Assignments)
                    ->with(['courseAssignments' => function($query) use ($examid, $validCourseIds) {
                        $query->where('exam_id', $examid)
                              ->whereIn('course_id', $validCourseIds)
                              ->whereIn('id', function($subquery) use ($examid) {
                                  $subquery->select(\DB::raw('MIN(id)'))
                                           ->from('course_teacher_assignments')
                                           ->where('exam_id', $examid)
                                           ->groupBy('course_id');
                              })
                              ->with('course');
                    }])->get();
            } else {
                // Get both Teacher 1 and Teacher 2 for templates like question_manuscript and answer_script
                // but only for courses with verified student registrations
                $teachers = Teacher::whereHas('courseAssignments', function($query) use ($examid, $validCourseIds) {
                    $query->where('exam_id', $examid)
                          ->whereIn('course_id', $validCourseIds);
                })->with(['courseAssignments' => function($query) use ($examid, $validCourseIds) {
                    $query->where('exam_id', $examid)
                          ->whereIn('course_id', $validCourseIds)
                          ->with('course');
                }])->get();
            }

            if ($teachers->isEmpty()) {
                $who = in_array($request->template_type, $teacher1OnlyTemplates) ? 'an internal teacher (Teacher 1)' : 'a teacher';
                return redirect('/mail/' . $examid)->with('error', 'No email was sent: none of the selected courses has ' . $who . ' assigned.');
            }

            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($teachers as $teacher) {
                // Get courses assigned to this teacher
                $courseList = $this->courseListHtml($teacher->courseAssignments->pluck('course')->filter());
                
                // Personalize the subject
                $personalizedSubject = str_replace('[Exam Name]', $exam->exam_name, $template['subject']);
                
                // Personalize the content
                $personalizedContent = str_replace('[Teacher\'s Name]', $teacher->name, $template['content']);
                $personalizedContent = str_replace('[Exam Name]', $exam->exam_name, $personalizedContent);
                $personalizedContent = str_replace('[Course List]', $courseList, $personalizedContent);
                
                if ($request->deadline) {
                    $personalizedContent = str_replace('<strong>[Deadline Date]</strong>', '<strong>' . date('F j, Y', strtotime($request->deadline)) . '</strong>', $personalizedContent);
                }

                $recipient = ['teacher_id' => $teacher->id, 'name' => $teacher->name, 'email' => $teacher->email];
                if ($this->sendAndLog($examid, $recipient, 'customized', $template['name'], $personalizedSubject, $personalizedContent)) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }

            $templateName = $template['name'];
            $teachersLabel = function ($count) {
                return $count . ' ' . ($count === 1 ? 'teacher' : 'teachers');
            };
            if ($failedCount === 0) {
                return redirect('/mail/' . $examid)->with('success', "'{$templateName}' sent to " . $teachersLabel($sentCount) . '.');
            }
            if ($sentCount === 0) {
                return redirect('/mail/' . $examid)->with('error', "'{$templateName}' could not be sent to any of the " . $teachersLabel($failedCount) . '. The Tracker shows the reason.');
            }
            return redirect('/mail/' . $examid)->with('warning', "'{$templateName}' sent to " . $teachersLabel($sentCount) . ', but failed for ' . $failedCount . '. The Tracker shows the reason.');
        } catch (Exception $e) {
            return redirect('/mail/' . $examid)->with('error', 'Error sending customized mail: ' . $e->getMessage());
        }
    }

    /**
     * Display the mail tracker for an exam
     */
    public function log(Request $request, $examid)
    {
        $exam = AvailableExam::findOrFail($examid);
        $status = in_array($request->get('status'), ['sent', 'failed']) ? $request->get('status') : 'all';

        $query = MailLog::where('exam_id', $examid);
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $logs = $query->orderBy('id', 'desc')->paginate(50)->appends($request->only('status'));

        // Failed logs that already have a later attempt (latest attempt's status)
        $retriedIds = MailLog::where('exam_id', $examid)
            ->whereNotNull('resent_from_id')
            ->orderBy('id')
            ->pluck('status', 'resent_from_id')
            ->toArray();

        // Details shown in the "View Email" modal, keyed by log ID
        $mailData = $logs->getCollection()->keyBy('id')->map(function($log) {
            return [
                'to' => $log->recipient_name . ' <' . $log->recipient_email . '>',
                'subject' => $log->subject,
                'date' => $log->created_at->timezone('Asia/Dhaka')->format('j M Y, g:i A'),
                'status' => $log->status === 'sent' ? 'Sent' : 'Failed',
                'error' => $log->error_message,
                'content' => $log->content,
            ];
        });

        return view('mail.log')->with([
            'exam' => $exam,
            'logs' => $logs,
            'mailData' => $mailData,
            'status' => $status,
            'sentCount' => MailLog::where('exam_id', $examid)->where('status', 'sent')->count(),
            'failedCount' => MailLog::where('exam_id', $examid)->where('status', 'failed')->count(),
            'retriedIds' => $retriedIds,
        ]);
    }

    /**
     * Resend a failed mail from the mail tracker
     */
    public function resend($examid, $logid)
    {
        $log = MailLog::where('exam_id', $examid)->where('status', 'failed')->findOrFail($logid);

        $recipient = ['teacher_id' => $log->teacher_id, 'name' => $log->recipient_name, 'email' => $log->recipient_email];
        $sent = $this->sendAndLog($examid, $recipient, $log->mail_type, $log->template_name, $log->subject, $log->content, [], $log->id);

        if ($sent) {
            return redirect()->back()->with('success', 'Mail resent successfully to ' . $log->recipient_email . '.');
        }
        return redirect()->back()->with('error', 'Resend to ' . $log->recipient_email . ' failed again. See the newest entry for the reason.');
    }
}
