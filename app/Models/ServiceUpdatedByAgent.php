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
        'service_charge',
        'payment_collected',
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
        return $this->hasOne(User::class,'id', 'created_by')->select('id','name','avatar');
    }
    public function yesNoData() 
    {
        return $this->hasOne(YesNo::class,'id', 'payment_collected')->select('id','yes_no_list');
    }
    public function formImageData() 
    {
        return $this->hasMany(FormImage::class, 'service_updated_by_agent_id', 'id');
        
    }
    public function siteImageData() 
    {
        return $this->hasMany(SiteImage::class, 'service_updated_by_agent_id', 'id');
        
    }

}
