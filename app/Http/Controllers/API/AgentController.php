<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FormImage;
use App\Models\Service;
use App\Models\ServiceUpdatedByAgent;
use App\Models\SiteImage;
use App\Models\Status;
use App\Models\TaskAcceptDeclinedNotification;
use App\Models\User;
use App\Models\YesNo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\Whatsappgroup;


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
        try {

          

        $updateNewService = new ServiceUpdatedByAgent();
        $updateNewService->service_id = $request->service_id;
        $updateNewService->remarks = $request->remarks;
        $updateNewService->status = $request->status;
        $updateNewService->service_charge = $request->service_charge;
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
                $measure->caption = $formData['caption']; 

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
                $uploadSite->caption = $siteData['caption']; 

                $uploadSite->save();
               
            }
           
        }

        $taskCompleted = new TaskAcceptDeclinedNotification();
        $taskCompleted->service_id = $request->service_id;
        $taskCompleted->agent_id = auth()->id();

        $serviceData = Service::find($request->service_id);
    

        $agnetName = User::where('id',auth()->id())->first();
        $currentDateTime = now();
        $taskCompleted->date = $currentDateTime->toDateString();
        $taskCompleted->time = $currentDateTime->toTimeString();
        $taskCompleted->notification_message = $serviceData->service_code . ' task completed by the agent ' . $agnetName->name . ' on ' .  $currentDateTime->toDateString() . ', at ' . $currentDateTime->toTimeString() . ' .';
        
        
        //fcm start
        if ($serviceData) {
         $userDeviceToken = User::find($serviceData['created_by_user_id']);       
       
        // Define the headers
        $headers = [
           'Content-Type' => 'application/json',
           'Authorization' => 'key=' . env('FIREBASE_KEY'),
       ];
   
       // Define the JSON body
       $body = [
           'registration_ids' => [
               $userDeviceToken['device_token'],
           ],
           'notification' => [
               'body' => $serviceData['client_name'] ."' task completed!",
               'title' => 'Task completed by installer!',
               'android_channel_id' => 'theinstallers',
               'sound' => true,
           ],
           'data' => [
               'notification_message' => 'completed',
            
           ],
       ];
       
       // Send the POST request
       $response = Http::withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send', $body);
         
        }
           //fcm end  
        
        
        $taskCompleted->is_accept = 1;
        $taskCompleted->save();

           //Send whatsapp group message
            if($request->status == 3){

           $serviceDealerIdData = Service::where('id', $request->service_id)
           ->with('whatsapp_data')
           ->first();
          

           if($serviceDealerIdData != null){
                // Your existing parameters
           $params = [
               'token' => $serviceDealerIdData['whatsapp_data']['instance_data']['token_id'],
               'to' => $serviceDealerIdData['whatsapp_data']['group_id'],
               'body' => "Task Completed" . "\n" . "Service Code: " .  $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number,
               'priority' => '10',
               'referenceId' => '',
               'msgId' => '',
               'mentions' => '',
           ];

           $url = 'https://api.ultramsg.com/' . $serviceDealerIdData['whatsapp_data']['instance_data']['instance_id'] . '/messages/chat';

           $response = Http::post($url, $params);
           }
            }
           //end

          
        
        return response()->json([
            "message" => "Service updated successfully!",
            "status" => 200,
        ], 200);

        } catch (\Exception $e) {
            // Return error response in JSON format
            return response()->json([
                "message" => 'Error: ' . $e->getMessage(),
                "status" => 500,
            ], 500);
        }
        
    }

    public function all_yes_no(){
    
        $all_yes = YesNo::select('id','yes_no_list')->get();
        return response()->json([
            "status" => 200,
            "items" => $all_yes
        ],200);
    }
    
    public function accept_decline_task(Request $request){

        try {

            $taskAcceptDecline = new TaskAcceptDeclinedNotification();
            $taskAcceptDecline->service_id = $request->service_id;
            $taskAcceptDecline->agent_id = auth()->id();
            $serviceData = Service::find($request->service_id);
           
            $agnetName = User::where('id',auth()->id())->first();
            $currentDateTime = now();
            $taskAcceptDecline->date = $currentDateTime->toDateString();
            $taskAcceptDecline->time = $currentDateTime->toTimeString();
    
            if ($request->notification_message == 'accepted') {
            $taskAcceptDecline->notification_message = $serviceData->service_code .' '. $request->notification_message . ' by the agent ' . $agnetName->name . ' on ' .  $currentDateTime->toDateString() . ', at ' . $currentDateTime->toTimeString() . ' .';
            
            }elseif ($request->notification_message == 'reached on site') {
             $taskAcceptDecline->notification_message = $serviceData->service_code .' ' . 'agent ' . $agnetName->name .' ' . $request->notification_message . ' on ' .  $currentDateTime->toDateString() . ', at ' . $currentDateTime->toTimeString() . ' .';
                                                                            
             
            }elseif ($request->notification_message == 'task started') {
                $taskAcceptDecline->notification_message = $serviceData->service_code .' agent '. $agnetName->name .' ' .  $request->notification_message . ' on ' .  $currentDateTime->toDateString() . ', at ' . $currentDateTime->toTimeString() . ' .';
                                                        
                                                        
            }
            elseif ($request->notification_message == 'task postponed') {
                $taskAcceptDecline->notification_message = $serviceData->service_code .' ' . $request->notification_message . ' due to ' . $request->reason .  ' .' . ' This was reported by Agent ' . $agnetName->name . ' on ' .  $currentDateTime->toDateString() . ', at ' . $currentDateTime->toTimeString() . ' .';
            }
            elseif ($request->notification_message == 'task canceled') {
                $taskAcceptDecline->notification_message = $serviceData->service_code .' ' . $request->notification_message . ' due to ' . $request->reason .  ' .' . ' This was reported by Agent ' . $agnetName->name . ' on ' .  $currentDateTime->toDateString() . ', at ' . $currentDateTime->toTimeString() . ' .';
    
            }elseif ($request->notification_message == 'not accepted') {
                $taskAcceptDecline->notification_message = $serviceData->service_code .' ' . $request->notification_message . ' due to ' . $request->reason .  ' .' . ' This was reported by Agent ' . $agnetName->name . ' on ' .  $currentDateTime->toDateString() . ', at ' . $currentDateTime->toTimeString() . ' .';
            }  
            else{
    
            $taskAcceptDecline->notification_message = $serviceData->service_code .' '. $request->notification_message . ' by the agent ' . $agnetName->name . ' on ' .  $currentDateTime->toDateString() . ', at ' . $currentDateTime->toTimeString() . ' .' .'Because '. $request->reason;
            }
    
            if ($request->notification_message == 'accepted') {
                $taskAcceptDecline->is_accept = '1';
            }elseif($request->notification_message == 'not accepted'){
                $taskAcceptDecline->is_accept = '0';
            }
            
            $taskAcceptDecline->reason = $request->reason;
            $taskAcceptDecline->notification_type = $request->notification_message;

            
            //fcm notification start
             
            if ($request->notification_message == 'accepted') {
               
                if ($serviceData) {
             $userDeviceToken = User::find($serviceData['created_by_user_id']);       
                    //fcm start
          
            // dd($userDeviceToken['device_token']);
            // Define the headers
            $headers = [
               'Content-Type' => 'application/json',
               'Authorization' => 'key=' . env('FIREBASE_KEY'),
           ];
       
           // Define the JSON body
           $body = [
               'registration_ids' => [
                   $userDeviceToken['device_token'],
               ],
               'notification' => [
                   'body' => $serviceData['client_name'] ."' task accepted!",
                   'title' => 'Task accepted by installer!',
                   'android_channel_id' => 'theinstallers',
                   'sound' => true,
               ],
               'data' => [
                   'notification_message' => $request->notification_message,
                
               ],
           ];
           
           // Send the POST request
           $response = Http::withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send', $body);
           //fcm end    
    
             
            }
            }
            //fcm notification end
            
            
            if ($request->notification_message == 'not accepted') {
                if($serviceData->assigned_agent_id == auth()->id()){
                    $serviceData->assigned_agent_id = null;
                    $serviceData->status = 1;
                    $serviceData->save();
                }
    
            }
    
            if ($request->notification_message == 'task canceled') {
                if($serviceData->assigned_agent_id == auth()->id()){
                    $serviceData->assigned_agent_id = null;
                    $serviceData->status = 4;
                    $serviceData->save();
                }
    
            }
    
            
            $taskAcceptDecline->save();

            //whatsapp message start
            
            $serviceDealerIdData = Service::where('id', $request->service_id)
            ->with('whatsapp_data')
            ->first();
        
         if ($serviceDealerIdData != null) {
            // Your existing parameters
            $params = [
                'token' => $serviceDealerIdData['whatsapp_data']['instance_data']['token_id'],
                'to' => $serviceDealerIdData['whatsapp_data']['group_id'],
                'priority' => '10',
                'referenceId' => '',
                'msgId' => '',
                'mentions' => '',
            ];
        
            // Conditionally set the 'body' parameter
            if ($request->notification_message == 'accepted') {
                $params['body'] = "Installer Task Accepted" . "\n" . "Service Code: " .  $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number;
            } elseif ($request->notification_message == 'reached on site') {
                $params['body'] = "Installer Reached On Site" . "\n" . "Service Code: " . $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number;
            }elseif ($request->notification_message == 'task started') {
                $params['body'] = "Installer Task Started" . "\n" . "Service Code: " . $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number;
            }elseif ($request->notification_message == 'task postponed') {
                $params['body'] = "Task Postponed" . "\n" . "Service Code: " . $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number . "\n" . "Reason: " .  $request->reason;
            }
            
            // Perform the HTTP post only if 'body' is set
            if (isset($params['body'])) {
                $url = 'https://api.ultramsg.com/' . $serviceDealerIdData['whatsapp_data']['instance_data']['instance_id'] . '/messages/chat';
                $response = Http::post($url, $params);
            }
        }
        
         //whatsapp message end
    
           
         
            return response()->json([
                "status" => 200,
                "items" => 'Successfully notified!'
            ],200);

        } catch (\Exception $e) {
            // Return error response in JSON format
            return response()->json([
                "message" => 'Error: ' . $e->getMessage(),
                "status" => 500,
            ], 500);
        }
     
       
        
    }

    
   
}
