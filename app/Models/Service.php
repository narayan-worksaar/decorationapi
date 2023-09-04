<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $table ="services";
    // protected $guarded = [];
    protected $fillable = [
        'image',
        'service_code',
        'client_name',
        'client_email_address',
        'client_mobile_number',
        'client_alternate_mobile_number',
        'date_time',
        'task_type_id',
        'measurement',
        'descriptions',
        'action_type_id',
        'assigned_agent_id',
        'dealer_id',
        'service_charge',
        'status',
        'slug',    
        'meta_data',   
        'remarks', 
        'notes',    
        'payment_mode_id',  
        'created_by_user_id',    
        'address',    
        'type_of_measurement',    
        'type_of_material',    
        'employee_of',    
    ];

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

    public function serviceCreator() 
    {
        return $this->hasOne(User::class,'id', 'created_by_user_id')->select('id','name','company_name');
    }
    public function paymentMode() 
    {
        return $this->hasOne(PaymentMode::class,'id', 'payment_mode_id')->select('id','payment_method_name')->where('status','active');
    }
   
    public function assigned_agent() 
    {
        return $this->hasOne(AgentAssigned::class,'service_id', 'id')
        ->latest('created_at')
        ->with('agent_name');
        
    }
    public function task_completed_by_agent() 
    {
        return $this->hasOne(ServiceUpdatedByAgent::class,'service_id', 'id')
        ->latest('created_at')
        ->with('userData');
        
    }

    public function task_upadated_by_agent() 
    {
        return $this->hasMany(ServiceUpdatedByAgent::class,'service_id', 'id')->with('formImageData')->with('siteImageData');
    }

}
