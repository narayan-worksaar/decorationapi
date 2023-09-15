<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coordinate extends Model
{
    use HasFactory;
    protected $table ="coordinates";
    protected $fillable = [
        'place',
        'latitude',
        'longitude',
        'status'
    ];
}
