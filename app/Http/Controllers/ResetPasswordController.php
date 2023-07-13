<?php

namespace App\Http\Controllers;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    public function reset_password (Request $request){
       $resetData= PasswordReset::where('token', $request->token)->get();
       
       if(isset($request->token) && count($resetData)>0){
       $user = User::where('email', $resetData[0]['email'])->get();
       return view('reset_password.resetPassword', compact('user'));
       }else{
        return view('404');
       }
    }

    public function save_new_password (Request $request){
           
        $request->validate([
            "password" => "required|confirmed|min: 8",
        ]);

        $user = User::find($request->id);
        $user->password = bcrypt($request->password);
        $user->save();

        PasswordReset::where('email', $user->email)->delete();
        return view('reset_password.password_reset_success');
        
    }
}
