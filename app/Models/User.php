<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends \TCG\Voyager\Models\User implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    
    protected $fillable = [
        'role_id',    
        'image',
        'name',
        'email',
        'avatar',
        'email_verified_at',
        'password',
        'mobile_number',
        'alternate_mobile_number',
        'user_type_id',
        'bank_name',
        'branch_name',
        'account_number',
        'total_work_experience',
        'vehicle_type_id',
        'aadhaar_card',
        'driving_license',
        'voter_id_card',
        'status',
        'remember_token',
        'gender_id',
        'date_of_birth',
        'address',
        'landmark',
        'city',
        'state',
        'pin_code',
        'company_name',
        'coordinate'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

   
}
