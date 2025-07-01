<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailableExam extends Model
{
    use HasFactory;
    protected $table = "available_exams";
    
    protected $fillable = [
        'exam_name', 'department', 'series', 'deadline'
    ];
}
