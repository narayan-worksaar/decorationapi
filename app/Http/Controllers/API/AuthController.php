<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\RegisterUserMail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public $token = true;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

   
    public function register(Request $request)
    {
      

        try{
            $validator = Validator::make($request->all(),[
                "email" => "required|email|unique:users",
                "password" => "required|confirmed|min: 8",
            ]);
    
            if($validator->fails()){
                return response()->json($validator->errors(), 400);
            }
    
            $user = new User();
            $user->name = $request->name;
            $user->company_name = $request->company_name;
            $user->email = $request->email;
            $user->mobile_number = $request->mobile_number;
            $user->aadhaar_card = $request->aadhaar_card;
            $user->password = bcrypt($request->password);
            $user->role_id = $request->user_type_id;
            if($request->user_type_id == 5){
                $user->status = 'active';
                $user->employee_of_dealer_id = $request->employee_of_dealer_id;
                
                $dealerCompanyName = User::find($request->employee_of_dealer_id);
                $user->company_name = $dealerCompanyName->company_name;
            }
            $user->save();
            
          
            // $mailData = [
            //     'name' => $request->name,
            //     'company_name' => $request->company_name,
            //     'email' => $request->email,
            // ];
            // Mail::to('paswan.narayan@gmail.com')->send(new RegisterUserMail($mailData));
            // return response()->json(['success' => 'Mail sent!.'], 200);
        }catch (\Throwable $th){
            return response()->json(['error' => 'Something went wrong!.'], 401);
        }
      
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            // Check if the user's status is "active" before allowing them to login
            $user = $this->guard()->user();
            if ($user->status === 'active') {
                return $this->respondWithToken($token);
            } else {
                return response()->json(['error' => 'Your account is not active.'], 401);
            }
            
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function me()
    {
        return response()->json($this->guard()->user());
    }

    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token)
    {   //this is getting user details
        
        $user = $this->guard()->user();
        return response()->json([
            // 'access_token' => $token,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'user' => $user
            
        ]);
    }

    public function guard()
    {
        return Auth::guard('api');
    }

    
}
