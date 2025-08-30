<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'name',
        'type',
        'subject',
        'content',
        'assigned_courses',
    ];

    protected $casts = [
        'assigned_courses' => 'array',
    ];

    /**
     * Get the exam that owns the mail template.
     */
    public function exam()
    {
        return $this->belongsTo(AvailableExam::class, 'exam_id');
    }

    /**
     * Get the courses assigned to this template.
     */
    public function courses()
    {
        if ($this->type === 'customized' && $this->assigned_courses) {
            return Course::whereIn('id', $this->assigned_courses)->get();
        }
        return collect();
    }
}
