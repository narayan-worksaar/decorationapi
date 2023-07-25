<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $table ="services";
    protected $guarded = [];

    public function measurements_details() 
    {
        return $this->hasMany(MeasurementDetail::class,'service_id', 'id');
    }
    public function installation_details() 
    {
        return $this->hasMany(InstallationDetail::class,'service_id', 'id');
    }

    public function tasktype() 
    {
        return $this->hasOne(TaskType::class,'id', 'task_type_id')->select('id','task_name')->where('task_status','active');
    }
}
