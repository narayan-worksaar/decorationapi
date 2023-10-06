<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAcceptDeclinedNotification extends Model
{
    use HasFactory;
    protected $table ="notifications";
    protected $fillable = [
        'service_id',
        'user_id',
        'notification_message',
        'is_accept',
        'is_read',
        'date',
        'time'
    ];

    
}
