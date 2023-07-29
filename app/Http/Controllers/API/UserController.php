<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

    public function update_dealer_details(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'image' => 'mimes:jpeg,png,jpg,gif,svg|max:1024',
            "name" => "required",
            "mobile_number" => "required",
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        $dealerUpdate = User::find($request->id);
        
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

    
        if($request->hasFile('image')){
            $fileNameImage = time().".".$request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('public/images',$fileNameImage);
            $dealerUpdate->image = $fileNameImage;
        }

        $dealerUpdate->name = $request->name;
        $dealerUpdate->mobile_number = $request->mobile_number;
        $dealerUpdate->alternate_mobile_number = $request->alternate_mobile_number;
        
        $dealerUpdate->gender_id = $request->gender_id;
        $dealerUpdate->date_of_birth = $request->date_of_birth;
        $dealerUpdate->address = $request->address;
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
}
