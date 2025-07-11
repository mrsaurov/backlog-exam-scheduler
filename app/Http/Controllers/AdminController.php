<?php

namespace App\Http\Controllers;

use App\Models\AvailableExam;
use App\Models\Course;
use App\Models\RegisteredStudent;
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

        $courses = Course::all();
        $coursesArray = $courses->toArray();
        $coursemap = [];
        foreach($coursesArray as $crs)
        {
            $coursemap[$crs['id']] = $crs['course_code'];
        }
        $stds = [];
        foreach($students as $std)
        {
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
        RegisteredStudent::where('examid','=', $examid)->update(array('verified'=>false));

        RegisteredStudent::wherein('id',$std)->update(array('verified'=>true));
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
            if($std['course1'])
                array_push($vertex, $std['course1']);
            if($std['course2'])
                array_push($vertex, $std['course2']);
            if($std['course3'])
                array_push($vertex, $std['course3']);
            if($std['course4'])
                array_push($vertex, $std['course4']);
            if($std['course5'])
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
            if($std['course1']) $studentCourses[] = $std['course1'];
            if($std['course2']) $studentCourses[] = $std['course2'];
            if($std['course3']) $studentCourses[] = $std['course3'];
            if($std['course4']) $studentCourses[] = $std['course4'];
            if($std['course5']) $studentCourses[] = $std['course5'];
            
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
        
        foreach($vertex as $v)
        {
            $edge->$v = [];
            $result->$v = -1;
        }
        foreach($allstd as $std)
        {
            $items = [];
            if($std['course1'])
                array_push($items, $std['course1']);
            if($std['course2'])
                array_push($items, $std['course2']);
            if($std['course3'])
                array_push($items, $std['course3']);
            if($std['course4'])
                array_push($items, $std['course4']);
            if($std['course5'])
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
        return view('schedule')->with([
            'edge'=>$edge,
            'vertex'=>$vertex,
            'vertexcount'=>$vertexcount,
            'result'=>$ret,
            'coursemap'=>$coursemap,
            'courseStudents'=>$courseStudents
        ]);
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
        
        // Check if roll or registration number already exists for other students in the same exam
        $existingStudent = RegisteredStudent::where('examid', $examid)
            ->where('id', '!=', $studentId)
            ->where(function($query) use ($roll, $registration) {
                $query->where('roll', $roll)
                      ->orWhere('registration', $registration);
            })
            ->first();
            
        if ($existingStudent) {
            if ($existingStudent->roll == $roll) {
                flash()->addError('Roll number already exists for another student in this exam!');
            } else {
                flash()->addError('Registration number already exists for another student in this exam!');
            }
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
}
