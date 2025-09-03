<?php

namespace App\Http\Controllers;

use App\Models\AvailableExam;
use App\Models\Course;
use App\Models\CourseExamMapping;
use App\Models\RegisteredStudent;
use App\Models\Notice;
use Hamcrest\Core\IsTypeOf;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Exception;

class AdminController extends Controller
{
    //
    public function students($id, Request $request)
    {
        // Get sorting parameter from request
        $sortBy = $request->get('sort', 'default');
        
        $students = RegisteredStudent::all()->where('examid','=',$id);
        
        // Apply sorting based on the parameter
        if ($sortBy === 'roll') {
            $students = $students->sortBy('roll');
        }
        // For default order, keep the original order (no additional sorting needed)
        
        $students = $students->toArray();
        $exam = AvailableExam::all()->where('id','=',$id)->first();

        // Get only courses that are mapped to this specific exam
        $examCourseIds = CourseExamMapping::where('examid', $id)->pluck('courseid')->toArray();
        $courses = Course::whereIn('id', $examCourseIds)->get();
        $coursesArray = $courses->toArray();
        $coursemap = [];
        foreach($coursesArray as $crs)
        {
            // Use only course_code for table display
            $coursemap[$crs['id']] = $crs['course_code'];
        }
        $stds = [];
        foreach($students as $std)
        {
            // Store original course IDs for the edit modal
            $std['course1_id'] = $std['course1'];
            $std['course2_id'] = $std['course2'];
            $std['course3_id'] = $std['course3'];
            $std['course4_id'] = $std['course4'];
            $std['course5_id'] = $std['course5'];
            
            // Convert to course codes with titles for display
            if($std['course1'] && $std['course1']>0) 
                $std['course1'] = $coursemap[$std['course1']];

            if($std['course2'] && $std['course2']>0) 
                $std['course2'] = $coursemap[$std['course2']];

            if($std['course3'] && $std['course3']>0) 
                $std['course3'] = $coursemap[$std['course3']];

            if($std['course4'] && $std['course4']>0) 
                $std['course4'] = $coursemap[$std['course4']];

            if($std['course5'] && $std['course5']>0) 
                $std['course5'] = $coursemap[$std['course5']];
            
            array_push($stds,$std);
        }
        return view('student')->with([
                                        'students'=>$stds, 
                                        'exam'=>$exam,
                                        'courses'=>$courses,
                                        'currentSort'=>$sortBy
                                    ]);
    }
    public function studentsupdate(Request $req)
    {
        $std = $req->input('verification');
        $examid = $req->input('examid');
        
        // First, set all students for this exam as unverified
        RegisteredStudent::where('examid','=', $examid)->update(array('verified'=>false));

        // Only update verified students if there are any selected
        if (!empty($std) && is_array($std)) {
            RegisteredStudent::wherein('id',$std)->update(array('verified'=>true));
        }
        
        flash()->addSuccess('Data has been saved successfully!');

        return redirect('/students/'.$examid);
    }
    
    public function studentdelete(Request $req)
    {
        $studentId = $req->input('student_id');
        $examid = $req->input('examid');
        
        try {
            $student = RegisteredStudent::find($studentId);
            if ($student) {
                $student->delete();
                flash()->addSuccess('Student registration deleted successfully!');
            } else {
                flash()->addError('Student not found!');
            }
        } catch (Exception $e) {
            flash()->addError('Error deleting student registration!');
        }
        
        return redirect('/students/'.$examid);
    }
    
    private function isOddNumberedCourse($courseId)
    {
        // Get course code from database
        $course = Course::find($courseId);
        if (!$course) {
            return false;
        }
        
        $courseCode = $course->course_code;
        
        // Extract numbers from course code using regex
        // This handles both "CSE 1101" and "Chem1113" formats
        preg_match('/(\d+)/', $courseCode, $matches);
        
        if (empty($matches)) {
            return false; // No numbers found
        }
        
        $courseNumber = intval($matches[0]);
        
        // Check if the last digit is odd (making it an odd-numbered course)
        return ($courseNumber % 2) === 1;
    }
    
    public function schedule($examid)
    {
        $courses = RegisteredStudent::all()->where('examid','=',$examid)->where('verified','=', true);
        $allstd = $courses->toArray();
        $edge = (object)[];
        $result = (object)[];
        $vertex = [];
        $courseStudents = []; // Array to store roll numbers for each course
        
        foreach($allstd as $std)
        {
            // Only consider odd-numbered courses
            if($std['course1'] && $this->isOddNumberedCourse($std['course1']))
                array_push($vertex, $std['course1']);
            if($std['course2'] && $this->isOddNumberedCourse($std['course2']))
                array_push($vertex, $std['course2']);
            if($std['course3'] && $this->isOddNumberedCourse($std['course3']))
                array_push($vertex, $std['course3']);
            if($std['course4'] && $this->isOddNumberedCourse($std['course4']))
                array_push($vertex, $std['course4']);
            if($std['course5'] && $this->isOddNumberedCourse($std['course5']))
                array_push($vertex, $std['course5']);
        }
        $vertexcount = array_count_values($vertex);
        $vertex = array_unique($vertex);
        
        // Initialize courseStudents array for each course
        foreach($vertex as $v)
        {
            $courseStudents[$v] = [];
        }
        
        // Collect roll numbers for each course
        foreach($allstd as $std)
        {
            $studentCourses = [];
            // Only collect roll numbers for odd-numbered courses
            if($std['course1'] && $this->isOddNumberedCourse($std['course1'])) $studentCourses[] = $std['course1'];
            if($std['course2'] && $this->isOddNumberedCourse($std['course2'])) $studentCourses[] = $std['course2'];
            if($std['course3'] && $this->isOddNumberedCourse($std['course3'])) $studentCourses[] = $std['course3'];
            if($std['course4'] && $this->isOddNumberedCourse($std['course4'])) $studentCourses[] = $std['course4'];
            if($std['course5'] && $this->isOddNumberedCourse($std['course5'])) $studentCourses[] = $std['course5'];
            
            foreach($studentCourses as $courseId)
            {
                if(!in_array($std['roll'], $courseStudents[$courseId]))
                {
                    $courseStudents[$courseId][] = $std['roll'];
                }
            }
        }
        
        // Sort roll numbers in ascending order for each course
        foreach($courseStudents as $courseId => $rolls)
        {
            sort($courseStudents[$courseId]);
        }
        
        // For the first table - collect ALL courses (including even-numbered ones)
        $allCourses = [];
        $allCourseStudents = [];
        
        foreach($allstd as $std)
        {
            // Collect ALL courses regardless of odd/even
            if($std['course1']) array_push($allCourses, $std['course1']);
            if($std['course2']) array_push($allCourses, $std['course2']);
            if($std['course3']) array_push($allCourses, $std['course3']);
            if($std['course4']) array_push($allCourses, $std['course4']);
            if($std['course5']) array_push($allCourses, $std['course5']);
        }
        $allCoursesCount = array_count_values($allCourses);
        $allCourses = array_unique($allCourses);
        
        // Initialize allCourseStudents array for each course
        foreach($allCourses as $courseId)
        {
            $allCourseStudents[$courseId] = [];
        }
        
        // Collect roll numbers for ALL courses
        foreach($allstd as $std)
        {
            $studentAllCourses = [];
            if($std['course1']) $studentAllCourses[] = $std['course1'];
            if($std['course2']) $studentAllCourses[] = $std['course2'];
            if($std['course3']) $studentAllCourses[] = $std['course3'];
            if($std['course4']) $studentAllCourses[] = $std['course4'];
            if($std['course5']) $studentAllCourses[] = $std['course5'];
            
            foreach($studentAllCourses as $courseId)
            {
                if(!in_array($std['roll'], $allCourseStudents[$courseId]))
                {
                    $allCourseStudents[$courseId][] = $std['roll'];
                }
            }
        }
        
        // Sort roll numbers in ascending order for each course
        foreach($allCourseStudents as $courseId => $rolls)
        {
            sort($allCourseStudents[$courseId]);
        }
        
        foreach($vertex as $v)
        {
            $edge->$v = [];
            $result->$v = -1;
        }
        foreach($allstd as $std)
        {
            $items = [];
            // Only include odd-numbered courses for dependency calculation
            if($std['course1'] && $this->isOddNumberedCourse($std['course1']))
                array_push($items, $std['course1']);
            if($std['course2'] && $this->isOddNumberedCourse($std['course2']))
                array_push($items, $std['course2']);
            if($std['course3'] && $this->isOddNumberedCourse($std['course3']))
                array_push($items, $std['course3']);
            if($std['course4'] && $this->isOddNumberedCourse($std['course4']))
                array_push($items, $std['course4']);
            if($std['course5'] && $this->isOddNumberedCourse($std['course5']))
                array_push($items, $std['course5']);
            foreach($items as $item)
            {
                foreach($items as $it)
                {
                    if($item != $it)
                        array_push($edge->$item, $it);
                }
                $edge->$item = array_unique($edge->$item);
            }

        }
        foreach($vertex as $node)
        {
            $used = [];
            foreach($edge->$node as $adjacent)
            {
                if($node == $adjacent)
                    continue;
                if($result->$adjacent != -1)
                     array_push($used, $result->$adjacent);
            }
            for($i=0;$i<count($vertex);$i++)
            {
                $found = false;
                foreach($used as $c)
                {
                    if($i==$c) 
                        $found = true; 
                }
                if($found == false)
                {
                    $result->$node = $i;

                    break;
                }
            }
        }
        $ret = [];
        $colors = [];
        foreach($vertex as $v)
        {
            array_push($colors, $result->$v);
        }
        foreach($colors as $color)
        {
            $ret[$color] = [];
        }
        foreach($vertex as $v)
        {
            array_push($ret[$result->$v], $v);
        }
        $coursemap = Course::all()->wherein('id',$vertex)->pluck('course_code','id')->toArray();
        $allCoursemap = Course::all()->wherein('id',$allCourses)->pluck('course_code','id')->toArray();
        $allCourseTitles = Course::all()->wherein('id',$allCourses)->pluck('course_title','id')->toArray();
        
        // Sort allCoursesCount by course code for the first table
        $sortedAllCoursesData = [];
        foreach($allCoursesCount as $courseId => $count) {
            $sortedAllCoursesData[] = [
                'course_id' => $courseId,
                'course_code' => $allCoursemap[$courseId],
                'course_title' => $allCourseTitles[$courseId],
                'count' => $count,
                'students' => $allCourseStudents[$courseId]
            ];
        }
        
        // Sort by course code
        usort($sortedAllCoursesData, function($a, $b) {
            return strcmp($a['course_code'], $b['course_code']);
        });
        
        return view('schedule')->with([
            'edge'=>$edge,
            'vertex'=>$vertex,
            'vertexcount'=>$vertexcount,
            'result'=>$ret,
            'coursemap'=>$coursemap,
            'courseStudents'=>$courseStudents,
            'allCoursesCount'=>$allCoursesCount,
            'allCoursemap'=>$allCoursemap,
            'allCourseStudents'=>$allCourseStudents,
            'allCourseTitles'=>$allCourseTitles,
            'sortedAllCoursesData'=>$sortedAllCoursesData,
            'examid'=>$examid
        ]);
    }
    
    public function exportCoursesCSV($examid)
    {
        $courses = RegisteredStudent::all()->where('examid','=',$examid)->where('verified','=', true);
        $allstd = $courses->toArray();
        $vertex = [];
        $courseStudents = [];
        
        foreach($allstd as $std)
        {
            // Only consider odd-numbered courses for export
            if($std['course1'] && $this->isOddNumberedCourse($std['course1']))
                array_push($vertex, $std['course1']);
            if($std['course2'] && $this->isOddNumberedCourse($std['course2']))
                array_push($vertex, $std['course2']);
            if($std['course3'] && $this->isOddNumberedCourse($std['course3']))
                array_push($vertex, $std['course3']);
            if($std['course4'] && $this->isOddNumberedCourse($std['course4']))
                array_push($vertex, $std['course4']);
            if($std['course5'] && $this->isOddNumberedCourse($std['course5']))
                array_push($vertex, $std['course5']);
        }
        $vertexcount = array_count_values($vertex);
        $vertex = array_unique($vertex);
        
        // Initialize courseStudents array for each course
        foreach($vertex as $v)
        {
            $courseStudents[$v] = [];
        }
        
        // Collect roll numbers for each course
        foreach($allstd as $std)
        {
            $studentCourses = [];
            // Only include odd-numbered courses  
            if($std['course1'] && $this->isOddNumberedCourse($std['course1'])) $studentCourses[] = $std['course1'];
            if($std['course2'] && $this->isOddNumberedCourse($std['course2'])) $studentCourses[] = $std['course2'];
            if($std['course3'] && $this->isOddNumberedCourse($std['course3'])) $studentCourses[] = $std['course3'];
            if($std['course4'] && $this->isOddNumberedCourse($std['course4'])) $studentCourses[] = $std['course4'];
            if($std['course5'] && $this->isOddNumberedCourse($std['course5'])) $studentCourses[] = $std['course5'];
            
            foreach($studentCourses as $courseId)
            {
                if(!in_array($std['roll'], $courseStudents[$courseId]))
                {
                    $courseStudents[$courseId][] = $std['roll'];
                }
            }
        }
        
        // Sort roll numbers in ascending order for each course
        foreach($courseStudents as $courseId => $rolls)
        {
            sort($courseStudents[$courseId]);
        }
        
        $coursemap = Course::all()->wherein('id',$vertex)->pluck('course_code','id')->toArray();
        
        $filename = 'courses_exam_' . $examid . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
        
        $callback = function() use ($vertexcount, $coursemap, $courseStudents) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Course', 'No. of Students', 'Student Rolls (Ascending Order)']);
            
            foreach($vertexcount as $v => $count) {
                $studentRolls = isset($courseStudents[$v]) ? implode(', ', $courseStudents[$v]) : '';
                fputcsv($file, [$coursemap[$v], $count, $studentRolls]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    public function exportDependenciesCSV($examid)
    {
        $courses = RegisteredStudent::all()->where('examid','=',$examid)->where('verified','=', true);
        $allstd = $courses->toArray();
        $edge = (object)[];
        $vertex = [];
        
        foreach($allstd as $std)
        {
            // Only consider odd-numbered courses
            if($std['course1'] && $this->isOddNumberedCourse($std['course1']))
                array_push($vertex, $std['course1']);
            if($std['course2'] && $this->isOddNumberedCourse($std['course2']))
                array_push($vertex, $std['course2']);
            if($std['course3'] && $this->isOddNumberedCourse($std['course3']))
                array_push($vertex, $std['course3']);
            if($std['course4'] && $this->isOddNumberedCourse($std['course4']))
                array_push($vertex, $std['course4']);
            if($std['course5'] && $this->isOddNumberedCourse($std['course5']))
                array_push($vertex, $std['course5']);
        }
        $vertex = array_unique($vertex);
        
        foreach($vertex as $v)
        {
            $edge->$v = [];
        }
        
        foreach($allstd as $std)
        {
            $items = [];
            // Only consider odd-numbered courses for dependencies
            if($std['course1'] && $this->isOddNumberedCourse($std['course1']))
                array_push($items, $std['course1']);
            if($std['course2'] && $this->isOddNumberedCourse($std['course2']))
                array_push($items, $std['course2']);
            if($std['course3'] && $this->isOddNumberedCourse($std['course3']))
                array_push($items, $std['course3']);
            if($std['course4'] && $this->isOddNumberedCourse($std['course4']))
                array_push($items, $std['course4']);
            if($std['course5'] && $this->isOddNumberedCourse($std['course5']))
                array_push($items, $std['course5']);
            
            foreach($items as $item)
            {
                foreach($items as $it)
                {
                    if($item != $it)
                        array_push($edge->$item, $it);
                }
                $edge->$item = array_unique($edge->$item);
            }
        }
        
        $coursemap = Course::all()->wherein('id',$vertex)->pluck('course_code','id')->toArray();
        
        $filename = 'dependencies_exam_' . $examid . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
        
        $callback = function() use ($vertex, $edge, $coursemap) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Course', 'Dependencies']);
            
            foreach($vertex as $v) {
                $dependencies = [];
                foreach($edge->$v as $e) {
                    $dependencies[] = $coursemap[$e];
                }
                $dependencyString = implode(', ', $dependencies);
                fputcsv($file, [$coursemap[$v], $dependencyString]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    public function exportScheduleCSV($examid)
    {
        // Get the same data as in schedule method
        $courses = RegisteredStudent::all()->where('examid','=',$examid)->where('verified','=', true);
        $allstd = $courses->toArray();
        $edge = (object)[];
        $result = (object)[];
        $vertex = [];
        
        foreach($allstd as $std)
        {
            // Only consider odd-numbered courses for scheduling
            if($std['course1'] && $this->isOddNumberedCourse($std['course1']))
                array_push($vertex, $std['course1']);
            if($std['course2'] && $this->isOddNumberedCourse($std['course2']))
                array_push($vertex, $std['course2']);
            if($std['course3'] && $this->isOddNumberedCourse($std['course3']))
                array_push($vertex, $std['course3']);
            if($std['course4'] && $this->isOddNumberedCourse($std['course4']))
                array_push($vertex, $std['course4']);
            if($std['course5'] && $this->isOddNumberedCourse($std['course5']))
                array_push($vertex, $std['course5']);
        }
        $vertex = array_unique($vertex);
        
        foreach($vertex as $v)
        {
            $edge->$v = [];
            $result->$v = -1;
        }
        
        foreach($allstd as $std)
        {
            $items = [];
            // Only consider odd-numbered courses for schedule export
            if($std['course1'] && $this->isOddNumberedCourse($std['course1']))
                array_push($items, $std['course1']);
            if($std['course2'] && $this->isOddNumberedCourse($std['course2']))
                array_push($items, $std['course2']);
            if($std['course3'] && $this->isOddNumberedCourse($std['course3']))
                array_push($items, $std['course3']);
            if($std['course4'] && $this->isOddNumberedCourse($std['course4']))
                array_push($items, $std['course4']);
            if($std['course5'] && $this->isOddNumberedCourse($std['course5']))
                array_push($items, $std['course5']);
            
            foreach($items as $item)
            {
                foreach($items as $it)
                {
                    if($item != $it)
                        array_push($edge->$item, $it);
                }
                $edge->$item = array_unique($edge->$item);
            }
        }
        
        foreach($vertex as $node)
        {
            $used = [];
            foreach($edge->$node as $adjacent)
            {
                if($node == $adjacent)
                    continue;
                if($result->$adjacent != -1)
                     array_push($used, $result->$adjacent);
            }
            for($i=0;$i<count($vertex);$i++)
            {
                $found = false;
                foreach($used as $c)
                {
                    if($i==$c) 
                        $found = true; 
                }
                if($found == false)
                {
                    $result->$node = $i;
                    break;
                }
            }
        }
        
        $ret = [];
        $colors = [];
        foreach($vertex as $v)
        {
            array_push($colors, $result->$v);
        }
        foreach($colors as $color)
        {
            $ret[$color] = [];
        }
        foreach($vertex as $v)
        {
            array_push($ret[$result->$v], $v);
        }
        
        $coursemap = Course::all()->wherein('id',$vertex)->pluck('course_code','id')->toArray();
        
        $filename = 'schedule_exam_' . $examid . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
        
        $callback = function() use ($ret, $coursemap) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date No.', 'Exams']);
            
            $dayCounter = 1;
            foreach($ret as $days) {
                $examCodes = [];
                foreach($days as $exam) {
                    $examCodes[] = $coursemap[$exam];
                }
                $examString = implode(', ', $examCodes);
                fputcsv($file, [$dayCounter, $examString]);
                $dayCounter++;
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    public function course($courseid, $examid)
    {
        if($courseid == 0)
        {
            $course = (object)[];
            $course->id=0;
            $course->course_code = "";
            $course->course_title = "";
            $course->department = "";
            $course->year = "";
            return view('course')->with([
                                    'course'=>$course,
                                    'examid'=>$examid
                                ]);
        
        }
        else {
            $course = Course::all()->where('id', '=', $courseid)->first();
            return view('course')->with([
                                    'course'=>$course,
                                    'examid'=>$examid
                                ]);
        }
            
    }
    public function courseupdate(Request $req)
    {
        $operation = $req->input('submit');
        $id = $req->input('id');
        $course_code = $req->input('course_code');
        $course_title = $req->input('course_title');
        $department = $req->input('department');
        $year = $req->input('year');
        $examid = $req->input('examid');
        if($operation == 'create') 
        {
            $course = new Course;
            $course->course_code = $course_code;
            $course->course_title = $course_title;
            $course->department = $department;
            $course->year = $year;
            if($course->save())
                flash()->addSuccess('Course added successfully');
            else 
                flash()->addError('Add course operation failed');
            return redirect('/exams/'.$examid);
        }
        else if($operation == 'update')
        {
            $res = Course::where('id','=', $id)->update(array(
                                            'course_code'=>$course_code,
                                            'course_title'=>$course_title,
                                            'department'=>$department,
                                            'year'=>$year
                                    ));
            if($res)
                flash()->addSuccess('Course updated successfully');
            else 
                flash()->addError('Update course operation failed');
            return redirect('/exams/'.$examid);
        }
        else if($operation == 'delete')
        {
            $res = Course::where('id','=', $id)->delete();
            if($res)
                flash()->addSuccess('Course deleted successfully');
            else 
                flash()->addError('Delete course operation failed');
            return redirect('/exams/'.$examid);
        }
    }
    public function studentedit(Request $req)
    {
        $studentId = $req->input('student_id');
        $examid = $req->input('examid');
        $name = $req->input('name');
        $roll = $req->input('roll');
        $registration = $req->input('registration');
        $course1 = $req->input('course1');
        $course2 = $req->input('course2') ?: null;
        $course3 = $req->input('course3') ?: null;
        $course4 = $req->input('course4') ?: null;
        $course5 = $req->input('course5') ?: null;
        $verified = $req->input('verified') == '1';
        
        // Validate required fields
        $req->validate([
            'name' => 'required|string|max:255',
            'roll' => 'required|integer|min:1',
            'registration' => 'required|integer|min:1',
            'course1' => 'required|integer|min:1'
        ]);
        
        // Check for duplicate courses
        $courses = array_filter([$course1, $course2, $course3, $course4, $course5]);
        if (count($courses) !== count(array_unique($courses))) {
            flash()->addError('You cannot select the same course multiple times!');
            return redirect('/students/'.$examid);
        }
        
        // Check if roll number already exists for other students in the same exam
        $existingStudent = RegisteredStudent::where('examid', $examid)
            ->where('id', '!=', $studentId)
            ->where('roll', $roll)
            ->first();
            
        if ($existingStudent) {
            flash()->addError('Roll number already exists for another student in this exam!');
            return redirect('/students/'.$examid);
        }
        
        try {
            $student = RegisteredStudent::find($studentId);
            if ($student) {
                $student->update([
                    'name' => $name,
                    'roll' => $roll,
                    'registration' => $registration,
                    'course1' => $course1,
                    'course2' => $course2 == 0 ? null : $course2,
                    'course3' => $course3 == 0 ? null : $course3,
                    'course4' => $course4 == 0 ? null : $course4,
                    'course5' => $course5 == 0 ? null : $course5,
                    'verified' => $verified
                ]);
                flash()->addSuccess('Student registration updated successfully!');
            } else {
                flash()->addError('Student not found!');
            }
        } catch (Exception $e) {
            flash()->addError('Error updating student registration!');
        }
        
        return redirect('/students/'.$examid);
    }
    
    // Notice Management Methods
    public function notices($examid)
    {
        $exam = AvailableExam::findOrFail($examid);
        $notices = Notice::where('exam_id', $examid)->orderBy('created_at', 'desc')->get();
        
        return view('notices')->with([
            'exam' => $exam,
            'notices' => $notices
        ]);
    }
    
    public function noticeForm($examid, $noticeid = 0)
    {
        $exam = AvailableExam::findOrFail($examid);
        
        if ($noticeid == 0) {
            $notice = (object)[
                'id' => 0,
                'title' => '',
                'content' => '',
                'is_active' => true
            ];
        } else {
            $notice = Notice::findOrFail($noticeid);
        }
        
        return view('notice-form')->with([
            'exam' => $exam,
            'notice' => $notice,
            'isNew' => $noticeid == 0
        ]);
    }
    
    public function noticeStore(Request $request)
    {
        $operation = $request->input('submit');
        $examId = $request->input('exam_id');
        $noticeId = $request->input('notice_id');

        try {
            if ($operation === 'delete') {
                // Validate inputs needed for delete only
                $request->validate([
                    'exam_id' => 'required|integer|exists:available_exams,id',
                    'notice_id' => 'required|integer|exists:notices,id',
                ]);

                $notice = Notice::findOrFail($noticeId);
                
                // Delete associated file if exists
                if ($notice->file_path && file_exists(public_path($notice->file_path))) {
                    unlink(public_path($notice->file_path));
                }
                
                $notice->delete();
                flash()->addSuccess('Notice deleted successfully!');
            } else {
                // Normalize checkbox to explicit boolean-friendly value
                $request->merge([
                    'is_active' => $request->has('is_active') ? 1 : 0,
                ]);

                // Validate for create/update
                $validationRules = [
                    'title' => 'required|string|max:500',
                    'content' => 'required|string',
                    'exam_id' => 'required|integer|exists:available_exams,id',
                    'is_active' => 'sometimes|boolean',
                ];
                
                // Add file validation if file is uploaded
                if ($request->hasFile('notice_file')) {
                    $validationRules['notice_file'] = 'file|mimes:pdf,jpg,jpeg,png,gif,doc,docx|max:5120'; // 5MB max
                }
                
                $request->validate($validationRules);

                // Handle file upload
                $fileData = [];
                if ($request->hasFile('notice_file')) {
                    $file = $request->file('notice_file');
                    
                    // Get file information BEFORE moving the file
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getClientMimeType();
                    $fileSize = $file->getSize();
                    
                    $fileName = time() . '_' . $originalName;
                    $filePath = 'uploads/notices/' . $fileName;
                    
                    // Create directory if it doesn't exist
                    $directory = public_path('uploads/notices');
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    
                    // Move file to public directory
                    $file->move($directory, $fileName);
                    
                    $fileData = [
                        'file_name' => $originalName,
                        'file_path' => $filePath,
                        'file_type' => $mimeType,
                        'file_size' => $fileSize,
                    ];
                }

                if ($operation === 'create') {
                    Notice::create(array_merge([
                        'title' => $request->input('title'),
                        'content' => $request->input('content'),
                        'exam_id' => $examId,
                        'is_active' => (bool)$request->input('is_active'),
                    ], $fileData));
                    flash()->addSuccess('Notice created successfully!');
                } elseif ($operation === 'update') {
                    $request->validate([
                        'notice_id' => 'required|integer|exists:notices,id',
                    ]);
                    $notice = Notice::findOrFail($noticeId);
                    
                    $updateData = [
                        'title' => $request->input('title'),
                        'content' => $request->input('content'),
                        'is_active' => (bool)$request->input('is_active'),
                    ];
                    
                    // If new file uploaded, delete old file and update with new file data
                    if (!empty($fileData)) {
                        if ($notice->file_path && file_exists(public_path($notice->file_path))) {
                            unlink(public_path($notice->file_path));
                        }
                        $updateData = array_merge($updateData, $fileData);
                    }
                    
                    $notice->update($updateData);
                    flash()->addSuccess('Notice updated successfully!');
                }
            }
        } catch (Exception $e) {
            flash()->addError('Error processing notice: ' . $e->getMessage());
        }

        return redirect('/notices/' . $examId);
    }
    
    public function viewNoticeFile($noticeId)
    {
        $notice = Notice::findOrFail($noticeId);
        
        if (!$notice->file_path || !file_exists(public_path($notice->file_path))) {
            abort(404, 'File not found');
        }
        
        $filePath = public_path($notice->file_path);
        $mimeType = $notice->file_type ?: mime_content_type($filePath);
        
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $notice->file_name . '"'
        ]);
    }
}
