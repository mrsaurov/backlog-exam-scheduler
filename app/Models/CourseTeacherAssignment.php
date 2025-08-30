<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTeacherAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'course_id',
        'teacher_id',
    ];

    /**
     * Get the exam that owns the assignment.
     */
    public function exam()
    {
        return $this->belongsTo(AvailableExam::class, 'exam_id');
    }

    /**
     * Get the course that owns the assignment.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the teacher that owns the assignment.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
