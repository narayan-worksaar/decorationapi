<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanjayCrud extends Model
{
    use HasFactory;
    protected $table ="sanjaycrud";
    protected $fillable = [
        'name',
        'email',
        'description',
    ];
}
