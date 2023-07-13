<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnBoard extends Model
{
    use HasFactory;
    protected $table ="onboards";
    protected $fillable = [
        'image',
        'title',
        'sub_title',
        'more_services',
        'status',
    ];
}
