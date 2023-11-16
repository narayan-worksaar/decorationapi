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
        'agent_id',
        'notification_message',
        'is_accept',
        'is_read',
        'date',
        'time'
    ];

    public function installerName() 
    {
        return $this->hasOne(User::class,'id', 'agent_id')->select('id','name','avatar');
    }

    
}
