<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegisteredStudent extends Model
{
    use HasFactory;
    protected $table = "registered_students";
    
    protected $fillable = [
        'roll', 'name', 'registration', 'examid', 'verified',
        'course1', 'course2', 'course3', 'course4', 'course5',
        'last_appeared_exam', 'backlogged_subjects'
    ];
}
