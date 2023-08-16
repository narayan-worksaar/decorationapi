<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskType extends Model
{
    use HasFactory;
    protected $table ="task_types";
    protected $fillable = [
        'task_name',
        'task_status'
    ];
}
