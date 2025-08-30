<?php

namespace App\Http\Controllers;

use App\Mail\ExamNotificationMail;
use App\Models\MailTemplate;
use App\Models\AvailableExam;
use App\Models\Course;
use App\Models\CourseExamMapping;
use App\Models\CourseTeacherAssignment;
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
            ]
        ];
    }

    /**
     * Display mail templates for an exam
     */
    public function index($examid)
    {
        $exam = AvailableExam::findOrFail($examid);
        $mailTemplates = MailTemplate::where('exam_id', $examid)->orderBy('created_at', 'desc')->get();
        
        // Get courses mapped to this exam
        $examCourseIds = CourseExamMapping::where('examid', $examid)->pluck('courseid')->toArray();
        $courses = Course::whereIn('id', $examCourseIds)->get();
        
        // Get predefined templates
        $predefinedTemplates = $this->getPredefinedTemplates();
        
        return view('mail.index')->with([
            'exam' => $exam,
            'mailTemplates' => $mailTemplates,
            'courses' => $courses,
            'predefinedTemplates' => $predefinedTemplates
        ]);
    }

    /**
     * Show form for creating/editing mail template
     */
    public function form($examid, $templateid = 0)
    {
        $exam = AvailableExam::findOrFail($examid);
        
        // Get courses mapped to this exam
        $examCourseIds = CourseExamMapping::where('examid', $examid)->pluck('courseid')->toArray();
        $courses = Course::whereIn('id', $examCourseIds)->get();
        
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
        
        // Get recipients based on template type
        if ($template->type === 'general') {
            // Get all teachers assigned to any course in this exam
            $recipients = Teacher::whereHas('courseAssignments', function($query) use ($template) {
                $query->where('exam_id', $template->exam_id);
            })->get();
        } else {
            // Get teachers assigned to specific courses
            $recipients = Teacher::whereHas('courseAssignments', function($query) use ($template) {
                $query->where('exam_id', $template->exam_id)
                      ->whereIn('course_id', $template->assigned_courses);
            })->with(['courseAssignments' => function($query) use ($template) {
                $query->where('exam_id', $template->exam_id)
                      ->whereIn('course_id', $template->assigned_courses)
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
        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'deadline' => 'nullable|date',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png'
        ]);

        try {
            $exam = AvailableExam::findOrFail($examid);
            
            // Handle file attachments
            $attachmentPaths = [];
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
            
            // Get all teachers assigned to any course in this exam
            $teachers = Teacher::whereHas('courseAssignments', function($query) use ($examid) {
                $query->where('exam_id', $examid);
            })->get();

            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($teachers as $teacher) {
                // Personalize the subject
                $personalizedSubject = str_replace('[Exam Name]', $exam->exam_name, $request->subject);
                
                $personalizedContent = str_replace('[Teacher\'s Name]', $teacher->name, $request->content);
                $personalizedContent = str_replace('[Exam Name]', $exam->exam_name, $personalizedContent);
                if ($request->deadline) {
                    $formattedDeadline = '<strong>' . date('F j, Y', strtotime($request->deadline)) . '</strong>';
                    $personalizedContent = str_replace('<strong>[Deadline Date]</strong>', $formattedDeadline, $personalizedContent);
                    $personalizedContent = str_replace('[Deadline Date]', $formattedDeadline, $personalizedContent);
                }
                
                try {
                    // Send email using Laravel Mail
                    Mail::to($teacher->email)->send(new ExamNotificationMail(
                        $personalizedSubject,
                        $personalizedContent,
                        $attachmentPaths
                    ));
                    $sentCount++;
                } catch (Exception $e) {
                    $failedCount++;
                    \Log::error('Failed to send email to ' . $teacher->email . ': ' . $e->getMessage());
                }
            }

            // Clean up temporary attachment files
            foreach ($attachmentPaths as $attachment) {
                if (file_exists($attachment['path'])) {
                    unlink($attachment['path']);
                }
            }

            $message = "General mail sent successfully! Sent: {$sentCount}";
            if ($failedCount > 0) {
                $message .= ", Failed: {$failedCount}";
            }
            if (count($attachmentPaths) > 0) {
                $message .= " (with " . count($attachmentPaths) . " attachment(s))";
            }
            
            return redirect('/mail/' . $examid)->with('success', $message);
        } catch (Exception $e) {
            // Clean up any uploaded files in case of error
            if (isset($attachmentPaths)) {
                foreach ($attachmentPaths as $attachment) {
                    if (file_exists($attachment['path'])) {
                        unlink($attachment['path']);
                    }
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
            'template_type' => 'required|in:ct_marks,sessional_marks,question_manuscript',
            'courses' => 'required|array|min:1',
            'courses.*' => 'exists:courses,id',
        ];
        
        // Make deadline required for specific templates
        $templatesRequiringDeadline = ['ct_marks', 'sessional_marks', 'question_manuscript'];
        if (in_array($request->template_type, $templatesRequiringDeadline)) {
            $rules['deadline'] = 'required|date';
        } else {
            $rules['deadline'] = 'nullable|date';
        }
        
        $request->validate($rules);

        try {
            $exam = AvailableExam::findOrFail($examid);
            $selectedCourses = Course::whereIn('id', $request->courses)->get();
            $predefinedTemplates = $this->getPredefinedTemplates();
            $template = $predefinedTemplates[$request->template_type];

            // Get teachers assigned to the selected courses
            $teachers = Teacher::whereHas('courseAssignments', function($query) use ($examid, $request) {
                $query->where('exam_id', $examid)
                      ->whereIn('course_id', $request->courses);
            })->with(['courseAssignments' => function($query) use ($examid, $request) {
                $query->where('exam_id', $examid)
                      ->whereIn('course_id', $request->courses)
                      ->with('course');
            }])->get();

            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($teachers as $teacher) {
                // Get courses assigned to this teacher
                $teacherCourses = $teacher->courseAssignments->map(function($assignment) {
                    return '<div style="margin-bottom: 8px;"><strong>' . $assignment->course->course_code . '</strong> – <strong>' . $assignment->course->course_title . '</strong></div>';
                })->toArray();

                $courseList = implode("", $teacherCourses);
                
                // Personalize the subject
                $personalizedSubject = str_replace('[Exam Name]', $exam->exam_name, $template['subject']);
                
                // Personalize the content
                $personalizedContent = str_replace('[Teacher\'s Name]', $teacher->name, $template['content']);
                $personalizedContent = str_replace('[Exam Name]', $exam->exam_name, $personalizedContent);
                $personalizedContent = str_replace('[Course List]', $courseList, $personalizedContent);
                
                if ($request->deadline) {
                    $personalizedContent = str_replace('<strong>[Deadline Date]</strong>', '<strong>' . date('F j, Y', strtotime($request->deadline)) . '</strong>', $personalizedContent);
                }

                try {
                    // Send email using Laravel Mail
                    Mail::to($teacher->email)->send(new ExamNotificationMail(
                        $personalizedSubject,
                        $personalizedContent
                    ));
                    $sentCount++;
                } catch (Exception $e) {
                    $failedCount++;
                    \Log::error('Failed to send email to ' . $teacher->email . ': ' . $e->getMessage());
                }
            }

            $templateName = $template['name'];
            $message = "'{$templateName}' mail sent successfully! Sent: {$sentCount}";
            if ($failedCount > 0) {
                $message .= ", Failed: {$failedCount}";
            }
            
            return redirect('/mail/' . $examid)->with('success', $message);
        } catch (Exception $e) {
            return redirect('/mail/' . $examid)->with('error', 'Error sending customized mail: ' . $e->getMessage());
        }
    }
}
