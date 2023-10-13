<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UniqueMobileNumber implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    
    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        // Check if the mobile number is unique in the users table
        return User::where('mobile_number', $value)->count() === 0;
    }

    public function message()
    {
        return 'The :attribute has already been taken.';
    }
}
