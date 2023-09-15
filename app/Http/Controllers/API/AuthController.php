<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\RegisterUserMail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Rules\UniqueMobileNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    
    public $token = true;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register','nearest_service_center']]);
        ini_set('memory_limit', '512M');
         ini_set('max_execution_time', '300');
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '10M');
        
    }

   
    public function register(Request $request)
    {
      

        // try{
            $validator = Validator::make($request->all(),[
                "email" => "email|unique:users",
                "mobile_number" => ["required", "numeric", new UniqueMobileNumber],
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
            $user->coordinate = $request->coordinate;
            
            if($request->user_type_id == 5){
                $user->status = 'active';
                $user->employee_of_dealer_id = $request->employee_of_dealer_id;
                
                $dealerCompanyName = User::find($request->employee_of_dealer_id);
                $user->company_name = $dealerCompanyName->company_name;
            }
           
            if ($request->hasFile('aadhaar_card')) {
                $aadhaarImage = time() . "_aadhaar" . "." . $request->file('aadhaar_card')->getClientOriginalExtension();
                $originalImagePath = $request->file('aadhaar_card')->getRealPath();
            
                $image = Image::make($originalImagePath);
            
                // Check if the image size is greater than 500 KB
                if (filesize($originalImagePath) > 500 * 1024) {
                    // Resize the image
                    $image->resize(500, 550, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
            
                // Save the image with 60% quality
                $image->save(public_path('storage/images/' . $aadhaarImage), 60);
            
                $user->aadhaar_card = $aadhaarImage;
            }
            

            if ($request->hasFile('driving_license')) {
                $drivingImage = time() . "_driving" . "." . $request->file('driving_license')->getClientOriginalExtension();
                $originalImagePath = $request->file('driving_license')->getRealPath();
            
                $image = Image::make($originalImagePath);
            
                // Check if the image size is greater than 500 KB
                if (filesize($originalImagePath) > 500 * 1024) {
                    // Resize the image
                    $image->resize(500, 550, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
            
                // Save the image with 60% quality
                $image->save(public_path('storage/images/' . $drivingImage), 60);
            
                $user->driving_license = $drivingImage;
            }
            
            $user->save();
            
          
            // $mailData = [
            //     'name' => $request->name,
            //     'company_name' => $request->company_name,
            //     'email' => $request->email,
            // ];
            // Mail::to('paswan.narayan@gmail.com')->send(new RegisterUserMail($mailData));
            // return response()->json(['success' => 'Mail sent!.'], 200);
        // }catch (\Throwable $th){
        //     return response()->json(['error' => 'Something went wrong!.'], 401);
        // }
      
    }
    /*
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
    */
    
    public function login(Request $request)
    {
        $credentials = $request->only('email_or_mobile', 'password');
    
        // Find the user by email or mobile number
        $user = User::where(function ($query) use ($credentials) {
            $query->where('email', $credentials['email_or_mobile'])
                ->orWhere('mobile_number', $credentials['email_or_mobile']);
        })->first();
    
        if ($user) {
            // Verify the password manually
            if (Hash::check($credentials['password'], $user->password)) {
                if ($user->status === 'active') {
                    $token = $this->guard()->login($user);
                    return $this->respondWithToken($token);
                } else {
                    return response()->json(['error' => 'Your account is not active.'], 401);
                }
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

    public function nearest_service_center(Request $request){
        
        $lat = $request->latitude;
        $long = $request->longitude;

       $data= DB::table('coordinates')
        ->select('coordinates.id',
        'coordinates.place',
        DB::raw("6371 * acos(cos(radians(" . $lat . "))
        * cos(radians(coordinates.latitude))
        * cos(radians(coordinates.longitude) - radians(" . $long . ")) 
        + sin(radians(" . $lat . "))
        * sin(radians(coordinates.latitude))) AS distance"))
        ->orderBy('distance','asc')
        ->first();
        // ->get();
        

        return response()->json([
            "status" => 200,
            "items" => $data
        ], 200);
    }

    
}
