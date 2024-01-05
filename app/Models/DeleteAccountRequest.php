<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeleteAccountRequest extends Model
{
    use HasFactory;
    protected $table ="delete_account_request";
    protected $fillable = [
        'user_id',
        'reason',
        'others',
        'status'
    ];
}
