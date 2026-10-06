<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'teacher_id',
        'recipient_name',
        'recipient_email',
        'mail_type',
        'template_name',
        'subject',
        'content',
        'attachment_names',
        'status',
        'error_message',
        'resent_from_id',
    ];

    protected $casts = [
        'attachment_names' => 'array',
    ];

    /**
     * Get the exam this mail was sent for.
     */
    public function exam()
    {
        return $this->belongsTo(AvailableExam::class, 'exam_id');
    }

    /**
     * Get the teacher this mail was sent to.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
