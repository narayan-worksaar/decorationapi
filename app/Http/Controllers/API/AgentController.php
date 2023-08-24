<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceUpdatedByAgent;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class AgentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    public function guard()
    {
        return Auth::guard('api');

    }

    public function all_status(){
    
        $all_status = Status::select('id','status_list')->get();
        return response()->json([
            "status" => 200,
            "items" => $all_status
        ],200);
    }

    public function update_service_by_agent(Request $request)
    {
       

        $updateNewService = new ServiceUpdatedByAgent();
        $updateNewService->service_id = $request->service_id;

     
       
        if ($request->hasFile('form')) {
            $formImage = time() . "_form" . "." . $request->file('form')->getClientOriginalExtension();
            $originalImagePath = $request->file('form')->getRealPath();
        
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
            $image->save(public_path('storage/images/' . $formImage), 60);
        
            $updateNewService->form = $formImage;
        }
        $updateNewService->remarks = $request->remarks;
        $updateNewService->status = $request->status;
        $updateNewService->created_by = auth()->id();
        // Set the current date and time
        $currentDateTime = now();
        $updateNewService->created_date = $currentDateTime->toDateString();
        $updateNewService->created_time = $currentDateTime->toTimeString();

        if ($request->service_id != null) {
            $serviceUpdate = Service::where('id', $request->service_id)->first();
            $serviceUpdate->status = $request->status;
            $serviceUpdate->save(); 
        }
        
        $updateNewService->save();
        return response()->json([
            "message" => "Service successfully updated!",
            "status" => 200,
        ], 200);
    }
}
