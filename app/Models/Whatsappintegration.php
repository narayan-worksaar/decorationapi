<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Whatsappintegration extends Model
{
    use HasFactory;

    protected $table ="whatsapp_integration";
    protected $fillable = [
        'instance_id',
        'token_id'
    ];
}
