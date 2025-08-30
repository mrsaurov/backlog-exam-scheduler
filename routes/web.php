<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class,'home']);
Route::get('/register/{id}',[HomeController::class, 'register']);
Route::post('/register', [HomeController::class, 'registerstudent']);
Route::post('/check-registration/{examid}', [HomeController::class, 'checkRegistration']);
Route::get('/login',function (){
    return view('login');
});
Route::post('/login', [HomeController::class, 'login']);
Route::post('/course', [AdminController::class, 'courseupdate'])->middleware('adminlogin');
Route::get('/courses/{courseid}/{examid}',[AdminController::class, 'course'])->middleware('adminlogin');
Route::get('/schedule/{id}',[AdminController::class, 'schedule'])->middleware('adminlogin');

// CSV Export routes for schedule page
Route::get('/export/courses/{examid}', [AdminController::class, 'exportCoursesCSV'])->middleware('adminlogin');
Route::get('/export/dependencies/{examid}', [AdminController::class, 'exportDependenciesCSV'])->middleware('adminlogin');
Route::get('/export/schedule/{examid}', [AdminController::class, 'exportScheduleCSV'])->middleware('adminlogin');

Route::get('/students/{id}',[AdminController::class, 'students'])->middleware('adminlogin');
Route::post('/students',[AdminController::class, 'studentsupdate'])->middleware('adminlogin');
Route::delete('/students/delete',[AdminController::class, 'studentdelete'])->middleware('adminlogin');
Route::put('/students/edit',[AdminController::class, 'studentedit'])->middleware('adminlogin');
Route::post('/exams',[HomeController::class, 'addorupdateexams'])->middleware('adminlogin');
Route::get('/exams/{id}',[HomeController::class, 'exams'])->middleware('adminlogin');
Route::get('/admin',[HomeController::class, 'admin'])->middleware('adminlogin');
Route::get('/logout', [HomeController::class, 'logout']);
Route::get('/download/{examid}/{roll}',[HomeController::class, 'download']);
Route::post('/exams',[HomeController::class, 'addorupdateexams'])->middleware('adminlogin');

// Notice routes for students
Route::get('/exam/{examid}/notices', [HomeController::class, 'examNotices']);

// Notice management routes for admin
Route::get('/notices/{examid}', [AdminController::class, 'notices'])->middleware('adminlogin');
Route::get('/notices/{examid}/create', [AdminController::class, 'noticeForm'])->middleware('adminlogin');
Route::get('/notices/{examid}/edit/{noticeid}', [AdminController::class, 'noticeForm'])->middleware('adminlogin');
Route::post('/notices', [AdminController::class, 'noticeStore'])->middleware('adminlogin');

// Teacher management routes
Route::get('/teachers/{examid}', [App\Http\Controllers\TeacherController::class, 'index'])->middleware('adminlogin');
Route::post('/teachers', [App\Http\Controllers\TeacherController::class, 'store'])->middleware('adminlogin');
Route::post('/teachers/assign-teacher', [App\Http\Controllers\TeacherController::class, 'assignTeacher'])->middleware('adminlogin');
Route::post('/teachers/remove-teacher', [App\Http\Controllers\TeacherController::class, 'removeTeacher'])->middleware('adminlogin');
Route::post('/teachers/assign-course', [App\Http\Controllers\TeacherController::class, 'assignCourse'])->middleware('adminlogin');
Route::delete('/teachers/remove-assignment', [App\Http\Controllers\TeacherController::class, 'removeAssignment'])->middleware('adminlogin');
Route::get('/api/teachers/{examid}', [App\Http\Controllers\TeacherController::class, 'getTeachers'])->middleware('adminlogin');

// Mail management routes
Route::get('/mail/{examid}', [App\Http\Controllers\MailController::class, 'index'])->middleware('adminlogin');
Route::get('/mail/{examid}/create', [App\Http\Controllers\MailController::class, 'form'])->middleware('adminlogin');
Route::get('/mail/{examid}/edit/{templateid}', [App\Http\Controllers\MailController::class, 'form'])->middleware('adminlogin');
Route::post('/mail', [App\Http\Controllers\MailController::class, 'store'])->middleware('adminlogin');
Route::get('/mail/preview/{templateid}', [App\Http\Controllers\MailController::class, 'preview'])->middleware('adminlogin');
Route::post('/mail/{examid}/send-general', [App\Http\Controllers\MailController::class, 'sendGeneral'])->middleware('adminlogin');
Route::post('/mail/{examid}/send-customized', [App\Http\Controllers\MailController::class, 'sendCustomized'])->middleware('adminlogin');

// Notice file viewing route
Route::get('/notice-file/{noticeid}', [AdminController::class, 'viewNoticeFile']);

// Route::get('/migrate-now', function () {
//     Artisan::call('migrate', ['--force' => true]);
//     return 'Migrations executed!';
// });
