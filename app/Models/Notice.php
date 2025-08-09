<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title', 'content', 'exam_id', 'is_active', 'file_name', 'file_path', 'file_type', 'file_size'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];
    
    public function exam()
    {
        return $this->belongsTo(AvailableExam::class, 'exam_id');
    }
}
