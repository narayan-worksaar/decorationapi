<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentAssigned extends Model
{
    use HasFactory;
    protected $table ="agent_assigned";
    protected $fillable = [
        'service_id',
        'assigned_by',
        'agent',
        'status',
        'assigned_date',
        'assigned_time'
    ];
    public function agent_name() 
    {
        return $this->hasOne(User::class,'id', 'agent')->select('id','name','avatar');
    }
}
