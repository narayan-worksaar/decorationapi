<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeleteReason extends Model
{
    use HasFactory;
    protected $table ="delete_reason";
    protected $fillable = [
        'reason_list',
        'status'
    ];
}
