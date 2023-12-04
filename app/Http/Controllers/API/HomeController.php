<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Gender;
use App\Models\OnBoard;
use Illuminate\Http\Request;
use App\Models\UserType;
use App\Models\PasswordReset;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL; 
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;



class HomeController extends Controller
{
    public function place_api_autocomplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search_text' => 'required',
        ]);

       
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.$request['search_text'].'&key='.'AIzaSyBgcejM6KxDF1mfBl6icxy2WlZ84WR1shs');
        
        return $response->json();
    }

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
           
           Mail::send('mail.forgetPasswordMail',['data'=>$data], function($message)use ($data){
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
           echo $e;
        }
       
    }

    public function update_role(Request $request)
    {
        $validator = Validator::make($request->all(),[
            "user_type_id" => "required",
           
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        $roleUpdate = User::find($request->id);
        
        if(!$roleUpdate){

            return response()->json([
                "message" => "Data not found.",
            ],403);

        }

        $roleUpdate->user_type_id = $request->user_type_id;
        $roleUpdate->save();
        
        return response()->json([
            "message" => "Roll updated successfully!",
            "status" => 200,
            // "item" =>$roleUpdate
        ],200);

    }

    public function get_service_booking_data(){
    
        $usertypedata = Service::get();
        return response()->json([
            "status" => 200,
            "data" => $usertypedata
        ],200);
    }

    public function gender(){
    
        $genderdata = Gender::select('id','name')->get();
        return response()->json([
            "status" => 200,
            "items" => $genderdata
        ],200);
    }


    
    
}
