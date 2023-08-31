<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FormImage;
use App\Models\Service;
use App\Models\ServiceUpdatedByAgent;
use App\Models\SiteImage;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;


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
        $updateNewService->remarks = $request->remarks;
        $updateNewService->status = $request->status;
        $updateNewService->payment_collected = $request->payment_collected;
        $updateNewService->created_by = auth()->id();
        // Set the current date and time
        $currentDateTime = now();
        $updateNewService->created_date = $currentDateTime->toDateString();
        $updateNewService->created_time = $currentDateTime->toTimeString();
        $updateNewService->save();

        if ($request->service_id != null) {
            $serviceUpdate = Service::where('id', $request->service_id)->first();
            $serviceUpdate->status = $request->status;
            $serviceUpdate->save(); 
        }

        if ($request->has('agent_form_image')) {
            $agentFormData = $request->agent_form_image;
           
            foreach ($agentFormData as $formData) {
                
                $measure = new FormImage();
                $measure->service_updated_by_agent_id = $updateNewService->id; 
             
                if($formData['form_image_file']){
                $formImage = time() . '_' . Str::random(10) . '.' .  $formData['form_image_file']->getClientOriginalExtension();
                $originalImagePath =  $formData['form_image_file']->getRealPath();
             
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
                }
                $measure->form_image_file = $formImage;

                $measure->save();
               
            }
           
        }

        if ($request->has('agent_site_image')) {
            $agentSiteData = $request->agent_site_image;
           
            foreach ($agentSiteData as $siteData) {
                
                $uploadSite= new SiteImage();
                $uploadSite->service_updated_by_agent_id = $updateNewService->id; 
             
                if($siteData['site_image_file']){
                $siteImage = time() . '_' . Str::random(10) . '.' .  $siteData['site_image_file']->getClientOriginalExtension();
                $originalImagePath =  $siteData['site_image_file']->getRealPath();
             
                $image = Image::make($originalImagePath);
                if (filesize($originalImagePath) > 500 * 1024) {
                    $image->resize(500, 550, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
                $image->save(public_path('storage/images/' . $siteImage), 60);
                }
                $uploadSite->site_image_file = $siteImage;

                $uploadSite->save();
               
            }
           
        }
        
        return response()->json([
            "message" => "Service updated successfully!",
            "status" => 200,
        ], 200);
    }

    /*
   public function update_service_by_agent(Request $request)
    {
        
        $updateNewService = new ServiceUpdatedByAgent();
        $updateNewService->service_id = $request->service_id;
        $updateNewService->remarks = $request->remarks;
        $updateNewService->status = $request->status;
        $updateNewService->created_by = auth()->id();
        // Set the current date and time
        $currentDateTime = now();
        $updateNewService->created_date = $currentDateTime->toDateString();
        $updateNewService->created_time = $currentDateTime->toTimeString();
        $updateNewService->save();

        if ($request->service_id != null) {
            $serviceUpdate = Service::where('id', $request->service_id)->first();
            $serviceUpdate->status = $request->status;
            $serviceUpdate->save(); 
        }

        if ($request->has('agent_form_image')) {
            $agentFormData = $request->agent_form_image;
           
            foreach ($agentFormData as $formData) {
                
                $measure = new FormImage();
                $measure->service_updated_by_agent_id = $updateNewService->id; 
                // $measure->form_image_file = $formData['form_image_file'];
                
                $file = $formData['form_image_file'];
                
                if ($file) {
                    $fileNameImage = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('public/images',$fileNameImage);
                    $measure->form_image_file = $fileNameImage;
                }

                $measure->save();
              
               
            }
           
        }
        
        return response()->json([
            "message" => "Service updated successfully!",
            "status" => 200,
        ], 200);
    }
    */

   
}
