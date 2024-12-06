<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title', 'content', 'dueDate', 'priority', 'tags', 'createdBy', 'assignedUsers'
    ];

    protected $casts = [
        'assignedUsers' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'createdBy');
    }
}