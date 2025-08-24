<?php

namespace App\Http\Controllers;

use App\Models\AvailableExam;
use App\Models\CourseExamMapping;
use App\Models\Course;
use App\Models\RegisteredStudent;
use App\Models\Notice;
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
        // Get exams that are not older than 3 months from deadline
        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
        $exams = AvailableExam::where('deadline', '>=', $threeMonthsAgo)
                              ->orderBy('deadline', 'desc')
                              ->get();
        
        // Add notice counts and deadline status for each exam
        foreach ($exams as $exam) {
            $exam->notice_count = Notice::where('exam_id', $exam->id)
                                      ->where('is_active', true)
                                      ->count();
            
            // Check if registration is still open (deadline not passed)
            $exam->registration_open = $exam->deadline >= date('Y-m-d');
        }

        return view('home')->with('exams', $exams);
    }
    public function register($examid)
    {
        $exam = AvailableExam::findOrFail($examid);
        
        // Check if registration is still open
        if ($exam->deadline < date('Y-m-d')) {
            return redirect('/')->with('error', 'Registration deadline for this exam has passed.');
        }
        
        $course_num = CourseExamMapping::where('examid','=',$examid)->pluck('courseid')->toArray();
        
        $courses = Course::all()->whereIn('id', $course_num);
        
        // Get active notices for this exam
        $notices = Notice::where('exam_id', $examid)
                        ->where('is_active', true)
                        ->orderBy('created_at', 'desc')
                        ->take(3) // Show only latest 3 notices
                        ->get();
        
        return view('register')->with(['exam'=>$exam,
                                        'courses'=>$courses,
                                        'notices'=>$notices,
                                        'cnum'=>$course_num]);
    }
    public function registerstudent(Request $request)
    {
        // Validate student input
        $request->validate([
            'name' => 'required|string|max:250',
            'roll' => 'required|integer|min:1',
            'registration' => 'required|integer|min:1',
            'examid' => 'required|integer|exists:available_exams,id',
            'course1' => 'required|integer|min:1',
            'last_appeared_exam' => 'required|string|max:255',
            'backlogged_subjects' => 'required|string'
        ]);

        $examid = $request->input('examid');
        
        // Check if registration is still open for this exam
        $exam = AvailableExam::findOrFail($examid);
        if ($exam->deadline < date('Y-m-d')) {
            return redirect('/')->with('error', 'Registration deadline for this exam has passed.');
        }
        
        $name = $request->input('name');
        $roll = $request->input('roll');
        $reg = $request->input('registration');
        $lastAppearedExam = $request->input('last_appeared_exam');
        $backloggedSubjects = $request->input('backlogged_subjects');

        $data = [
            'examid'=>$examid,
            'name' => $name,
            'roll' => $roll,
            'registration' => $reg,
            'last_appeared_exam' => $lastAppearedExam,
            'backlogged_subjects' => $backloggedSubjects
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
            return redirect('/')->with('error', 'You have already registered for this exam. To download your application again, <a href="/download/'.$examid.'/'.$roll.'" class="alert-link">click here</a>.');
        }
        
        RegisteredStudent::insert($data);
        return redirect('/')->with('success', 'Registration successful! Your application has been submitted. <a href="/download/'.$examid.'/'.$roll.'" class="alert-link">Download your application form</a>.');
        
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
    
    public function checkRegistration(Request $request, $examid)
    {
        $request->validate([
            'roll' => 'required|integer|min:1'
        ]);
        
        $roll = $request->input('roll');
        $exam = AvailableExam::findOrFail($examid);
        $student = RegisteredStudent::where('examid', $examid)->where('roll', $roll)->first();
        
        if ($student) {
            // Student is registered, redirect to download
            return redirect("/download/{$examid}/{$roll}");
        } else {
            // Student not found
            return redirect("/register/{$examid}")->with('error', 'No registration found for roll number ' . $roll . '. Please register first.');
        }
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
        $exams = AvailableExam::orderBy('deadline', 'desc')->get();
        
        // Add notice counts for each exam
        foreach ($exams as $exam) {
            $exam->notice_count = Notice::where('exam_id', $exam->id)->count();
            $exam->active_notice_count = Notice::where('exam_id', $exam->id)
                                               ->where('is_active', true)
                                               ->count();
        }
        
        return view('admin')->with('exams', $exams);
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
    
    public function examNotices($examId)
    {
        $exam = AvailableExam::findOrFail($examId);
        $notices = Notice::where('exam_id', $examId)
                        ->where('is_active', true)
                        ->orderBy('created_at', 'desc')
                        ->get();
        
        return view('exam-notices')->with([
            'exam' => $exam,
            'notices' => $notices
        ]);
    }
}
