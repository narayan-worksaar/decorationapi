<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SanjayCrud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SanjayController extends Controller
{
    public function get_details(){
    
        $userdata = SanjayCrud::get();
        return response()->json([
            "status" => 200,
            "data" => $userdata
        ],200);
    }

    public function store_data(Request $request)
    {
        $validator = Validator::make($request->all(),[
            "name" => "required",
           
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $storedata = new SanjayCrud();
        $storedata->name = $request->name;
        $storedata->email = $request->email;
        $storedata->description = $request->description;
        $storedata->save();
       
        return response()->json([
            "message" => "added successfully",
            "status" => 200,
            "items" => $storedata,
            
        ], 200);

      
    }

    public function update_data(Request $request)
    {
        $validator = Validator::make($request->all(),[
            "id" => "required",
           
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        $sanajyUpdate = SanjayCrud::find($request->id);
        
        if(!$sanajyUpdate){

            return response()->json([
                "message" => "Data not found.",
            ],403);

        }

        $sanajyUpdate->name = $request->name;
        $sanajyUpdate->email = $request->email;
        $sanajyUpdate->description = $request->description;
        $sanajyUpdate->save();
        
        return response()->json([
            "message" => "updated successfull!",
            "status" => 200,
            "item" =>$sanajyUpdate
        ],200);

    }
    public function delete_data(Request $request)
    {
    
        $dataDelete = SanjayCrud::find($request->id);
        
        if(!$dataDelete){

            return response()->json([
                "message" => "Data not found.",
            ],403);

        }


        $dataDelete->delete();
        return response()->json([
            "message" => "data deleted!",
        ],200);

    }
}
