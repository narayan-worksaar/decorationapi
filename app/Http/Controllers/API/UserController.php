<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\DeleteAccountRequestMail;
use App\Models\City;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Rules\UniqueMobileNumber;
use App\Models\DeleteAccountRequest;
use App\Models\DeleteReason;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    public function guard()
    {
        return Auth::guard('api');

    }

    public function delete_reason(){
    
        $delete_reason = DeleteReason::select('id','reason_list','status')->get();
        return response()->json([
            "status" => 200,
            "items" => $delete_reason
        ],200);
    }

    public function delete_account_request(Request $request)
    {
      
        
        $loggedInUser = User::find(auth()->id());

        $coordinatates = User::where(function ($query) use ($loggedInUser) {
            $query->where('coordinate', $loggedInUser->coordinate)
                ->where('role_id', 2);
        })
        ->orWhere(function ($query) {
            $query->whereNull('coordinate')
                ->where('role_id', 1);
        })
        ->select('id', 'role_id', 'name', 'email')
        ->get();

        $mailData = [
            'user_id' => $loggedInUser->id,    
            'name' => $loggedInUser->name,
            'email' => $loggedInUser->email,
            'mobile' => $loggedInUser->mobile_number,
        ];

        foreach ($coordinatates as $user) {
            Mail::to($user->email)->send(new DeleteAccountRequestMail($mailData));
        }

      
        try {
          

            $deleteRequest = new DeleteAccountRequest();
            $deleteRequest->user_id = auth()->id();
            $deleteRequest->reason = $request->reason;
            $deleteRequest->others = $request->others;

            $loggedInUser = User::find(auth()->id());
            $loggedInUser->status = 'inactive';
            $loggedInUser->save();

            $deleteRequest->save();


            return response()->json([
                "message" => "Request successfully submitted!",
                "status" => 200,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "message" => 'Error: ' . $e->getMessage(),
                "status" => 500,
            ], 500);
        }
       
    }

    public function update_dealer_details(Request $request)
    {
        
        
        $dealerUpdate = User::find($request->id);
        
     
        $validator = Validator::make($request->all(),[
            'avatar' => 'mimes:jpeg,png,jpg,gif,svg|max:1024',
            "name" => "required",
            'email' => [
                'nullable', // Allow the email to be null or empty
                'email',
                Rule::unique('users')->ignore($request->id)->where(function ($query) use ($request) {
                    return $request->email !== null && $request->email !== '';
                }),
            ],
         

            'mobile_number' => [
                'required',
                $request->mobile_number === $dealerUpdate->mobile_number
                    ? 'nullable'
                    : new UniqueMobileNumber(),
            ],

        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
       
        
        if(!$dealerUpdate){ 

            return response()->json([
                "message" => "Data not found.",
            ],403);

        }

        if($dealerUpdate->id != auth()->id()){
            return response()->json([
                "message" => "Permission denied.",
            ],403);
        }

    
        if($request->hasFile('avatar')){
            $fileNameImage = time().".".$request->file('avatar')->getClientOriginalExtension();
            $request->file('avatar')->storeAs('public/users',$fileNameImage);
            $dealerUpdate->avatar = "users/".$fileNameImage;
        }

        $dealerUpdate->name = $request->name;
        $dealerUpdate->address = $request->address;
      
        if ($request->has('email')) {
            $dealerUpdate->email = $request->email;
        }
        
        $dealerUpdate->mobile_number = $request->mobile_number;
        
        if ($request->has('password') && !empty($request->password)) {
            
            $dealerUpdate->password = bcrypt($request->password);
        }

        $dealerUpdate->alternate_mobile_number = $request->alternate_mobile_number;
        
        $dealerUpdate->gender_id = $request->gender_id;
        $dealerUpdate->date_of_birth = $request->date_of_birth;
      
        $dealerUpdate->landmark = $request->landmark;
        $dealerUpdate->city = $request->city;
        $dealerUpdate->state = $request->state;
        $dealerUpdate->pin_code = $request->pin_code;
        
        $dealerUpdate->save();
        
        return response()->json([
            "message" => "Updated successfully",
            "status" => 200,
            // "item" =>$dealerUpdate
        ],200);

    }

    public function all_states(){
    
        $all_states = State::select('id','state_name')->where('status','active')->get();
        return response()->json([
            "status" => 200,
            "items" => $all_states
        ],200);
    }

    public function state_wise_cities(Request $request){
        
        $validator = Validator::make($request->all(),[
            "state_id" => "required|numeric",
            
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        $all_cities = City::where('state_id',$request->state_id)
        ->select('id','state_id','city_name')
        ->where('status','active')
        ->get();
        return response()->json([
            "status" => 200,
            "items" => $all_cities
        ],200);
    }

    public function dealer_employee_list(Request $request){
      
        $dealer_employee_list = User::orderBy('id', 'DESC')
        ->where('role_id',5)
        ->where('employee_of_dealer_id', auth()->id())
        ->select('id','image','name','email','mobile_number','address','avatar')
            ->paginate(10);

        return response()->json([
            "status" => 200,
            "items" => $dealer_employee_list
        ], 200);
    }
}
