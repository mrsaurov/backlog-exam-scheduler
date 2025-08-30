<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Course;
use App\Models\AvailableExam;
use App\Models\CourseTeacherAssignment;
use App\Models\CourseExamMapping;
use App\Models\RegisteredStudent;
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
        
        // Get courses that have verified student registrations for this exam
        // This matches the logic from AdminController::schedule()
        $registeredStudents = RegisteredStudent::where('examid', $examid)
            ->where('verified', true)
            ->get();
        
        $coursesWithStudents = [];
        foreach($registeredStudents as $student) {
            // Collect ALL courses (course1, course2, course3, course4, course5) 
            // from verified students - both odd and even numbered courses
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
        
        // Get all teachers sorted by department then by designation hierarchy then by name
        $teachers = Teacher::with(['courseAssignments' => function($query) use ($examid) {
            $query->where('exam_id', $examid)->with('course');
        }])->get();
        
        // Sort teachers by department, then by designation hierarchy, then by name
        $teachers = $teachers->sort(function ($a, $b) {
            // First sort by department
            $deptComparison = strcmp($a->department ?? '', $b->department ?? '');
            if ($deptComparison !== 0) {
                return $deptComparison;
            }
            
            // Then sort by designation hierarchy
            $designationOrder = [
                'Professor' => 1,
                'Associate Professor' => 2,
                'Assistant Professor' => 3,
                'Lecturer' => 4,
            ];
            
            $aOrder = $designationOrder[$a->designation] ?? 5;
            $bOrder = $designationOrder[$b->designation] ?? 5;
            
            if ($aOrder !== $bOrder) {
                return $aOrder - $bOrder;
            }
            
            // Finally sort by name
            return strcmp($a->name, $b->name);
        });
        
        // Get all course-teacher assignments for this exam
        $assignments = CourseTeacherAssignment::with(['course', 'teacher'])
            ->where('exam_id', $examid)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Group assignments by course for the new layout
        $courseAssignments = [];
        foreach ($courses as $course) {
            $courseAssignments[$course->id] = [
                'course' => $course,
                'teachers' => $assignments->where('course_id', $course->id)->pluck('teacher')->take(2)
            ];
        }
        
        return view('teachers.index')->with([
            'exam' => $exam,
            'courses' => $courses,
            'assignments' => $assignments,
            'teachers' => $teachers,
            'courseAssignments' => $courseAssignments
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
                    'designation' => 'required|in:Professor,Associate Professor,Assistant Professor,Lecturer',
                    'department' => 'required|in:CSE,EEE,Mathematics,Physics,Chemistry,Humanities',
                    'exam_id' => 'required|integer|exists:available_exams,id',
                ]);

                $teacher = Teacher::findOrFail($request->teacher_id);
                
                // Check if email already exists (excluding current teacher)
                $existingTeacher = Teacher::where('email', $request->email)
                                         ->where('id', '!=', $request->teacher_id)
                                         ->first();
                if ($existingTeacher) {
                    return redirect('/teachers/' . $examId)->with('error', 'A teacher with this email address already exists: ' . $existingTeacher->name);
                }
                
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
                    'email' => 'required|email|max:255',
                    'phone' => 'nullable|string|max:20',
                    'designation' => 'required|in:Professor,Associate Professor,Assistant Professor,Lecturer',
                    'department' => 'required|in:CSE,EEE,Mathematics,Physics,Chemistry,Humanities',
                    'exam_id' => 'required|integer|exists:available_exams,id',
                ]);

                // Check if email already exists
                $existingTeacher = Teacher::where('email', $request->email)->first();
                if ($existingTeacher) {
                    return redirect('/teachers/' . $examId)->with('error', 'A teacher with this email address already exists: ' . $existingTeacher->name);
                }

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

    /**
     * Assign or update teacher for a course and position
     */
    public function assignTeacher(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:available_exams,id',
            'course_id' => 'required|exists:courses,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'position' => 'required|in:1,2',
        ]);

        try {
            $examId = $request->exam_id;
            $courseId = $request->course_id;
            $teacherId = $request->teacher_id;
            $position = (int)$request->position;

            // Get current assignments for this course
            $currentAssignments = CourseTeacherAssignment::where([
                'exam_id' => $examId,
                'course_id' => $courseId,
            ])->orderBy('id')->get();

            // If teacher_id is empty, remove the assignment
            if (empty($teacherId)) {
                if ($position == 1 && $currentAssignments->count() > 0) {
                    $firstAssignment = $currentAssignments->first();
                    $firstAssignment->delete();
                    
                    // If there's a second teacher, it will automatically become the "first" one
                } elseif ($position == 2 && $currentAssignments->count() > 1) {
                    $secondAssignment = $currentAssignments->skip(1)->first();
                    $secondAssignment->delete();
                }
                
                return response()->json(['success' => true, 'message' => 'Teacher assignment removed successfully!']);
            }

            // Check if teacher is already assigned to this course
            $existingAssignment = $currentAssignments->where('teacher_id', $teacherId)->first();
            if ($existingAssignment) {
                return response()->json(['success' => false, 'message' => 'This teacher is already assigned to this course!']);
            }

            if ($position == 1) {
                // For position 1, remove existing assignment if any and add new one at the beginning
                if ($currentAssignments->count() > 0) {
                    $firstAssignment = $currentAssignments->first();
                    $firstAssignment->update(['teacher_id' => $teacherId]);
                } else {
                    CourseTeacherAssignment::create([
                        'exam_id' => $examId,
                        'course_id' => $courseId,
                        'teacher_id' => $teacherId,
                    ]);
                }
            } else {
                // For position 2
                if ($currentAssignments->count() == 0) {
                    return response()->json(['success' => false, 'message' => 'Please assign Teacher 1 first!']);
                } elseif ($currentAssignments->count() == 1) {
                    // Add second teacher
                    CourseTeacherAssignment::create([
                        'exam_id' => $examId,
                        'course_id' => $courseId,
                        'teacher_id' => $teacherId,
                    ]);
                } else {
                    // Update second teacher
                    $secondAssignment = $currentAssignments->skip(1)->first();
                    $secondAssignment->update(['teacher_id' => $teacherId]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Teacher assigned successfully!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error assigning teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove teacher from a specific position
     */
    public function removeTeacher(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:available_exams,id',
            'course_id' => 'required|exists:courses,id',
            'position' => 'required|in:1,2',
        ]);

        try {
            $examId = $request->exam_id;
            $courseId = $request->course_id;
            $position = (int)$request->position;

            // Get current assignments for this course
            $currentAssignments = CourseTeacherAssignment::where([
                'exam_id' => $examId,
                'course_id' => $courseId,
            ])->orderBy('id')->get();

            if ($currentAssignments->count() == 0) {
                return response()->json(['success' => false, 'message' => 'No assignments found for this course!']);
            }

            if ($position == 1) {
                $firstAssignment = $currentAssignments->first();
                $firstAssignment->delete();
                
                // If there's a second teacher, move them to position 1
                if ($currentAssignments->count() > 1) {
                    // The remaining assignment will automatically be the "first" one
                }
            } else {
                if ($currentAssignments->count() < 2) {
                    return response()->json(['success' => false, 'message' => 'No teacher assigned to position 2!']);
                }
                $secondAssignment = $currentAssignments->skip(1)->first();
                $secondAssignment->delete();
            }

            return response()->json(['success' => true, 'message' => 'Teacher removed successfully!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error removing teacher: ' . $e->getMessage()]);
        }
    }
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
     * Remove a course assignment
     */
    public function removeAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:course_teacher_assignments,id',
        ]);

        try {
            $assignment = CourseTeacherAssignment::findOrFail($request->assignment_id);
            $assignment->delete();

            return response()->json(['success' => true, 'message' => 'Assignment removed successfully!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error removing assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Get teachers for AJAX requests
     */
    public function getTeachers($examid)
    {
        try {
            $teachers = Teacher::with(['courseAssignments' => function($query) use ($examid) {
                $query->where('exam_id', $examid)->with('course');
            }])->orderBy('name')->get();

            return response()->json(['success' => true, 'teachers' => $teachers]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching teachers: ' . $e->getMessage()]);
        }
    }
}
