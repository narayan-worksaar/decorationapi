<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    use HasFactory;
    protected $table ="payment_mode";
    protected $fillable = [
        'payment_method_name',
        'remarks',
        'status',
    ];
}
