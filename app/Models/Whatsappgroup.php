<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Whatsappgroup extends Model
{
    use HasFactory;

    protected $table ="whatsapp_group";
    protected $fillable = [
        'dealer_id',
        'group_id',
        'chat_id',
        'whatsapp_integration_id'
    ];

    public function instance_data()
    {
        return $this->hasOne(Whatsappintegration::class,'id', 'whatsapp_integration_id')
        ->select('id','instance_id','token_id');
    }
}
