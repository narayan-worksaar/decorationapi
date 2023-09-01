<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YesNo extends Model
{
    use HasFactory;
    protected $table ="yes_no";
    protected $fillable = [
        'yes_no_list'
       
    ];
}
