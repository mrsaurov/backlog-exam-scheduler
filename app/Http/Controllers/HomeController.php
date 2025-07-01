<?php

namespace App\Http\Controllers;

use App\Models\AvailableExam;
use App\Models\CourseExamMapping;
use App\Models\Course;
use App\Models\RegisteredStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Barryvdh\DomPDF\Facade\Pdf;
class HomeController extends Controller
{
    //
    public function home()
    {
        $exams = AvailableExam::all()->where('deadline','>=', date("Y-m-d"));

        return view('home')->with('exams', $exams);
    }
    public function register($examid)
    {
        $exam = AvailableExam::all()->where('id','=', $examid)->first();
        $course_num = CourseExamMapping::where('examid','=',$examid)->pluck('courseid')->toArray();
        
        $courses = Course::all()->whereIn('id', $course_num);
        return view('register')->with(['exam'=>$exam,
                                        'courses'=>$courses,
                                        'cnum'=>$course_num]);
    }
    public function registerstudent(Request $request)
    {

        $examid = $request->input('examid');
        $name = $request->input('name');
        $roll = $request->input('roll');
        $reg = $request->input('registration');


        $data = [
            'examid'=>$examid,
            'name' => $name,
            'roll' => $roll,
            'registration' => $reg
        ];

        $course1 = $request->input('course1');
        $course2 = $request->input('course2');
        $course3 = $request->input('course3');
        $course4 = $request->input('course4');
        $course5 = $request->input('course5');
        
        // Check for duplicate course selections
        $selectedCourses = [];
        $courses = [$course1, $course2, $course3, $course4, $course5];
        
        foreach($courses as $course) {
            if($course && $course > 0) {
                if(in_array($course, $selectedCourses)) {
                    return redirect()->back()->withErrors(['error' => 'You cannot select the same course multiple times. Please choose different courses.'])->withInput();
                }
                $selectedCourses[] = $course;
            }
        }
        
        if($course1 && $course1 > 0 )
        {
            $data["course1"] = $course1;
        }    

        if($course2 && $course2 > 0 )
        {
            $data["course2"] = $course2;
        }    

        if($course3 && $course3 > 0 )
        {
            $data["course3"] = $course3;
        }    

        if($course4 && $course4 > 0 )
        {
            $data["course4"] = $course4;
        }    

        if($course5 && $course5 > 0 )
        {
            $data["course5"] = $course5;
        }    
        $std = RegisteredStudent::all()->where('examid','=',$examid)->where('roll','=',$roll)->first();
        if($std) {
            echo "You've already registered for this exam. To download your application again <a href='/download/".$examid."/".$roll."'>click here</a>";
            return;
        }
        
        RegisteredStudent::insert($data);
        return redirect("/download/".$examid.'/'.$roll);
        
    }
    public function download(Request $req, $examid, $roll)
    {
        $exam = AvailableExam::all()->where('id','=',$examid)->first();
        $student= RegisteredStudent::all()->where("examid",'=',$examid)->where('roll','=',$roll)->first()->toArray();
        $c = array_filter([
            $student['course1'],
            $student['course2'],
            $student['course3'],
            $student['course4'],
            $student['course5']
        ]);
        $courses = Course::all()->whereIn('id',$c);
        $pdf = Pdf::loadView('application', ['exam'=>$exam,
                                            'student'=>$student,
                                            'courses'=>$courses]);
        return $pdf->download($roll.'_'.$exam->exam_name.'.pdf');
    }
    public function login(Request $req)
    {
        $email = $req->input('email');
        $pass = $req->input('password');
        $user = User::all()->where('email','=',$email)->where('password','=',$pass)->first();
        if($user != null && $user->name)
        {
            session(['name'=>$user->name]);
            return redirect('/admin');
        }
        return view('login');
    }
    public function admin()
    {
        $exams = AvailableExam::all()->take(-10);
        return view('admin',['exams'=>$exams]);
    }
    public function logout(Request $data)
    {
        $data->session()->flush();
        Artisan::call('cache:clear');
        return redirect('/login');
    }
    public function exams($id)
    {
        $courses = Course::all();
        if($id==0) {
            $exam = (object)[];
            $exam->id=0;
            $exam->series = "";
            $exam->deadline = "";
            $exam->exam_name = "";
            $exam->department = "";
            return view('exam')->with(['new'=>true, 
                                        'exam'=>$exam,
                                        'courses'=>$courses,
                                        'selected'=>[]
                                    ]);
        }
            
        else {
            $exam = AvailableExam::all()->where('id','=',$id)->first();
            $selectedCourses = CourseExamMapping::all()->where('examid','=',$id)->pluck('courseid');
            return view('exam')->with(['new'=>false, 
                                        'exam'=>$exam, 
                                        'courses'=>$courses,
                                        'selected'=>$selectedCourses]
                                    );
        }
    }
    public function addorupdateexams(Request $req)
    {
        $operation = $req->input('submit');

        $name = $req->input('exam_name');
        $dept = $req->input('department');
        $series = $req->input('series');
        $deadline = $req->input('deadline');
        $selected = $req->input('assignedcourses');
        if($operation == "delete")
        {
            $examid = $req->input('exam_id');
            AvailableExam::where('id','=',$examid)->delete();
            flash()->addSuccess('Exam deleted successfully.');
            return redirect('/admin');
        }
        else if($operation == "update")
        {
            $examid = $req->input('exam_id');
            AvailableExam::where('id','=',$examid)->update(array('exam_name'=>$name, 'department'=>$dept,'series'=>$series, 'deadline'=>$deadline));
            
            CourseExamMapping::where('examid','=',$examid)->delete();
            
            foreach($selected as $course)
            {
                $obj = new CourseExamMapping;
                $obj->examid = $examid;
                $obj->courseid = $course;
                $obj->save();
            }
            flash()->addSuccess('Exam updated successfully.');
            return redirect('/exams/'.$examid);
        }
        else if($operation == "create") 
        {
            $exam = new AvailableExam;
            $exam->exam_name = $name;
            $exam->department = $dept;
            $exam->series= $series;
            $exam->deadline = $deadline;
            $exam->save();
            $examid = $exam->id;
            
            foreach($selected as $course)
            {
                $obj = new CourseExamMapping;
                $obj->examid = $examid;
                $obj->courseid = $course;
                $obj->save();
            }
            flash()->addSuccess('Exam created successfully.');
            return redirect('/exams/'.$examid);
        }
    }
}
