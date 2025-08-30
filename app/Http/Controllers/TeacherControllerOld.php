<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Course;
use App\Models\AvailableExam;
use App\Models\CourseTeacherAssignment;
use App\Models\CourseExamMapping;
use Illuminate\Http\Request;
use Exception;

class TeacherController extends Controller
{
    /**
     * Display teachers and course assignments for an exam
     */
    public function index($examid)
    {
        $exam = AvailableExam::findOrFail($examid);
        
        // Get courses mapped to this exam
        $examCourseIds = CourseExamMapping::where('examid', $examid)->pluck('courseid')->toArray();
        $courses = Course::whereIn('id', $examCourseIds)->get();
        
        // Get all teachers
        $teachers = Teacher::with(['courseAssignments' => function($query) use ($examid) {
            $query->where('exam_id', $examid)->with('course');
        }])->orderBy('name')->get();
        
        // Get all course-teacher assignments for this exam
        $assignments = CourseTeacherAssignment::with(['course', 'teacher'])
            ->where('exam_id', $examid)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('teachers.index')->with([
            'exam' => $exam,
            'courses' => $courses,
            'assignments' => $assignments,
            'teachers' => $teachers
        ]);
    }

    /**
     * Store a new teacher or handle teacher operations
     */
    public function store(Request $request)
    {
        $operation = $request->input('submit');
        $examId = $request->input('exam_id');

        try {
            if ($operation === 'delete') {
                $request->validate([
                    'teacher_id' => 'required|integer|exists:teachers,id',
                    'exam_id' => 'required|integer|exists:available_exams,id',
                ]);

                $teacher = Teacher::findOrFail($request->teacher_id);
                
                // Remove all course assignments for this teacher in this exam
                CourseTeacherAssignment::where('exam_id', $examId)
                                     ->where('teacher_id', $request->teacher_id)
                                     ->delete();
                
                $teacher->delete();
                return redirect('/teachers/' . $examId)->with('success', 'Teacher deleted successfully!');
                
            } elseif ($operation === 'update') {
                $request->validate([
                    'teacher_id' => 'required|integer|exists:teachers,id',
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|max:255',
                    'phone' => 'nullable|string|max:20',
                    'designation' => 'required|in:professor,associate_professor,assistant_professor,lecturer,senior_lecturer',
                    'department' => 'nullable|string|max:255',
                    'exam_id' => 'required|integer|exists:available_exams,id',
                ]);

                $teacher = Teacher::findOrFail($request->teacher_id);
                $teacher->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'designation' => $request->designation,
                    'department' => $request->department,
                ]);

                return redirect('/teachers/' . $examId)->with('success', 'Teacher updated successfully!');
                
            } else { // create
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|max:255|unique:teachers,email',
                    'phone' => 'nullable|string|max:20',
                    'designation' => 'required|in:professor,associate_professor,assistant_professor,lecturer,senior_lecturer',
                    'department' => 'nullable|string|max:255',
                    'exam_id' => 'required|integer|exists:available_exams,id',
                ]);

                Teacher::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'designation' => $request->designation,
                    'department' => $request->department,
                ]);

                return redirect('/teachers/' . $examId)->with('success', 'Teacher added successfully!');
            }
        } catch (Exception $e) {
            return redirect('/teachers/' . $examId)->with('error', 'Error processing teacher: ' . $e->getMessage());
        }
    }
            'department' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email',
        ]);

        try {
            Teacher::create([
                'name' => $request->name,
                'designation' => $request->designation,
                'department' => $request->department,
                'email' => $request->email,
            ]);

            return response()->json(['success' => true, 'message' => 'Teacher added successfully!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error adding teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign teacher to course for an exam
     */
    public function assignCourse(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:available_exams,id',
            'course_id' => 'required|exists:courses,id',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        try {
            // Check if assignment already exists
            $existingAssignment = CourseTeacherAssignment::where([
                'exam_id' => $request->exam_id,
                'course_id' => $request->course_id,
                'teacher_id' => $request->teacher_id,
            ])->first();

            if ($existingAssignment) {
                return response()->json(['success' => false, 'message' => 'This teacher is already assigned to this course!']);
            }

            CourseTeacherAssignment::create([
                'exam_id' => $request->exam_id,
                'course_id' => $request->course_id,
                'teacher_id' => $request->teacher_id,
            ]);

            return response()->json(['success' => true, 'message' => 'Teacher assigned successfully!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error assigning teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove teacher assignment from course
     */
    public function removeAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:course_teacher_assignments,id',
        ]);

        try {
            $assignment = CourseTeacherAssignment::findOrFail($request->assignment_id);
            $assignment->delete();

            return response()->json(['success' => true, 'message' => 'Teacher assignment removed successfully!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error removing assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Get teachers for AJAX requests
     */
    public function getTeachers()
    {
        $teachers = Teacher::orderBy('name')->get();
        return response()->json($teachers);
    }
}
