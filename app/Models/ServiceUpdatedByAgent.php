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

    public function serviceData() 
    {
        return $this->hasOne(Service::class,'id', 'service_id')->select('id','service_code','client_name');
    }

    public function statusData() 
    {
        return $this->hasOne(Status::class,'id', 'status')->select('id','status_list');
    }
    public function userData() 
    {
        return $this->hasOne(User::class,'id', 'created_by')->select('id','name');
    }

}
