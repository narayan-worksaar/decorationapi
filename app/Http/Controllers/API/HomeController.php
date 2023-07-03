<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OnBoard;
use Illuminate\Http\Request;
use App\Models\UserType;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL; 
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{
    public function user_types(){
    
        $usertypedata = UserType::whereIn('id', [2, 3])->select('id','usertype')->get();
        return response()->json([
            "status" => 200,
            "data" => $usertypedata
        ],200);
    }

    public function on_boarding(){
    
        $onboarddata = OnBoard::select('image','title','sub_title','more_services')->get();
        return response()->json([
            "status" => 200,
            "data" => $onboarddata
        ],200);
    }

    public function forgot_password(Request $request){
      
        try{
          $user= User::where('email', $request->email)->get();
          //check lagawo
          if(count($user)>0){
           $token = Str::random(40);
           $domain = URL::to('/');
           $url= $domain . '/reset-password?token='.$token;
           $data['url'] = $url;
           $data['email'] = $request->email;
           $data['title'] = 'Password Reset';
           $data['body'] = 'Please click on below link to reset your password.';
           
           Mail::send('forgetPasswordMail',['data'=>$data], function($message)use ($data){
            $message->to($data['email'])
            ->subject($data['title']);
           });

           $dataTime = Carbon::now()->format('Y-m-d H:i:s');
           PasswordReset::updateOrCreate(
            ['email'=>$request->email],
            [
              'email' => $request->email,
              'token' => $token,
              'created_at' => $dataTime,
            ],
           );
           return response()->json([
            "status" => 200,
            "data" => "Please check your mail to reset your password!"
        ],200);

          }else{
            return response()->json([
                "status" => 200,
                "data" => "Email is not exist!"
            ],200);
          }
        }catch(\Exception $e){
           print_r($e);
        }
       
    }


    
    
}
