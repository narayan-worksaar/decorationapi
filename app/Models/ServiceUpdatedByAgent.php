<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceUpdatedByAgent extends Model
{
    use HasFactory;
    protected $table ="service_update_by_agent";
    protected $fillable = [
        'service_id',
        'form',
        'remarks',
        'status',
        'created_by',
        'created_date',
        'created_time',
    ];
}
