<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'designation',
        'department',
    ];

    /**
     * Get the course assignments for this teacher.
     */
    public function courseAssignments()
    {
        return $this->hasMany(CourseTeacherAssignment::class);
    }

    /**
     * Get courses assigned to this teacher for a specific exam.
     */
    public function coursesForExam($examId)
    {
        return $this->hasManyThrough(
            Course::class,
            CourseTeacherAssignment::class,
            'teacher_id',
            'id',
            'id',
            'course_id'
        )->where('course_teacher_assignments.exam_id', $examId);
    }
}
