<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Whatsappgroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class WhatsappController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    public function guard()
    {
        return Auth::guard('api');

    }



    public function send_whatsapp_message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "dealer_id" => "required|numeric",
            "body_data" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $groupIdData = Whatsappgroup::where('dealer_id', $request->dealer_id)
                ->with('instance_data')
                ->first();

            // Ensure that the instance data and token are available
            if (!$groupIdData || !$groupIdData['instance_data']['token_id']) {
                return response()->json([
                    "message" => "Invalid dealer or missing token",
                    "status" => 400,
                ], 400);
            }

            // Your existing parameters
            $params = [
                'token' => $groupIdData['instance_data']['token_id'],
                'to' => $groupIdData->group_id,
                'body' => $request->body_data,
                'priority' => '10',
                'referenceId' => '',
                'msgId' => '',
                'mentions' => '',
            ];

            $url = 'https://api.ultramsg.com/' . $groupIdData['instance_data']['instance_id'] . '/messages/chat';

            $response = Http::post($url, $params);

            if ($response->successful()) {
                // Return success response in JSON format
                return response()->json([
                    "message" => "Message sent successfully",
                    "status" => 200,
                    // "response_body" => $response->json()
                ], 200);
            } else {
                // Return error response in JSON format
                return response()->json([
                    "message" => 'Unexpected HTTP status: ' . $response->status() . ' ' . $response->body(),
                    "status" => $response->status(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Return error response in JSON format
            return response()->json([
                "message" => 'Error: ' . $e->getMessage(),
                "status" => 500,
            ], 500);
        }
    }

    /*
    public function store_service_booking(Request $request)
    {
        try {

            $taskTypeData=TaskType::find($request->task_type_id);
            
            $existingRowsCount = Service::count();
            $idNumber = $existingRowsCount + 1;
            
            $loggedInUser = User::find(auth()->id());
            
            $addNewService = new Service();
            $addNewService->fill($request->all()); 
            $addNewService->save();
    
            $currentYear = date('Y');
            
            $addNewService->service_code = "SCode-{$currentYear}-{$idNumber}";
            $addNewService->created_by_user_id = auth()->id();
    
            if($loggedInUser->employee_of_dealer_id != null){
                $addNewService->employee_of = $loggedInUser->employee_of_dealer_id;
                $addNewService->dealer_id = $loggedInUser->employee_of_dealer_id;
                $addNewService->save(); 
            }
            if($loggedInUser->employee_of_dealer_id != null){
                $addNewService->dealer_id = $loggedInUser->employee_of_dealer_id;
                $addNewService->save(); 
            }else{
                $addNewService->dealer_id = auth()->id();
                $addNewService->save(); 
            }
    
            $addNewService->save();

            //tryin to send whatsapp group message

            $groupIdData = Whatsappgroup::where('dealer_id', $addNewService->dealer_id)
            ->with('instance_data')
            ->first();

            // dd($groupIdData);

            if($groupIdData != null){
                 // Your existing parameters
    
            // Convert address to latitude and longitude
            $geocodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
            $geocodingParams = [
                'address' => urlencode($addNewService->address),
                'key' => env('PLACE_API_KEY'),
            ];
            $geocodingResponse = Http::get($geocodingUrl, $geocodingParams);
            $geocodingData = $geocodingResponse->json();   
            
            if ($geocodingResponse->successful() && isset($geocodingData['results'][0]['geometry']['location'])) {
                $latitude = $geocodingData['results'][0]['geometry']['location']['lat'];
                $longitude = $geocodingData['results'][0]['geometry']['location']['lng'];

                // Construct Google Maps URL
                
                $googleMapsUrl = 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude . '&z=35';

                // Create a clickable link in the address
                $addressWithLink = $addNewService->address . (" . $googleMapsUrl . ");
                
                $params = [
                    'token' => $groupIdData['instance_data']['token_id'],
                    'to' => $groupIdData->group_id,
                    'body' => "New Booking: " . $addNewService->service_code . "\n" . "Client Name: " . $addNewService->client_name . "\n" . "Mobile No: " .$addNewService->client_mobile_number . "\n" . "Service Date & Time: " .$addNewService->date_time . "\n" . "Service Type: " .$taskTypeData->task_name . "\n" . "Address: " . $addressWithLink,
                    'priority' => '10',
                    'referenceId' => '',
                    'msgId' => '',
                    'mentions' => '',
                ];

                $url = 'https://api.ultramsg.com/' . $groupIdData['instance_data']['instance_id'] . '/messages/chat';

                $response = Http::post($url, $params);
            }

            }

            //end

            return response()->json([
                "message" => "New service booking is successful!",
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
    */

     //whatsapp message start
            
//      $serviceDealerIdData = Service::where('id', $request->service_id)
//      ->with('whatsapp_data')
//      ->first();
 
//   if ($serviceDealerIdData != null) {
//      // Your existing parameters
//      $params = [
//          'token' => $serviceDealerIdData['whatsapp_data']['instance_data']['token_id'],
//          'to' => $serviceDealerIdData['whatsapp_data']['group_id'],
//          'priority' => '10',
//          'referenceId' => '',
//          'msgId' => '',
//          'mentions' => '',
//      ];
 
//      // Conditionally set the 'body' parameter
//      if ($request->notification_message == 'accepted') {
//          $params['body'] = "Installer Task Accepted" . "\n" . "Service Code: " .  $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number;
//      } elseif ($request->notification_message == 'reached on site') {
//          $params['body'] = "Installer Reached On Site" . "\n" . "Service Code: " . $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number;
//      }elseif ($request->notification_message == 'task started') {
//          $params['body'] = "Installer Task Started" . "\n" . "Service Code: " . $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number;
//      }elseif ($request->notification_message == 'task postponed') {
//          $params['body'] = "Task Postponed" . "\n" . "Service Code: " . $serviceDealerIdData->service_code . "\n" . "Client Name: " . $serviceDealerIdData->client_name . "\n" . "Mobile No: " . $serviceDealerIdData->client_mobile_number . "\n" . "Reason: " .  $request->reason;
//      }
     
//      // Perform the HTTP post only if 'body' is set
//      if (isset($params['body'])) {
//          $url = 'https://api.ultramsg.com/' . $serviceDealerIdData['whatsapp_data']['instance_data']['instance_id'] . '/messages/chat';
//          $response = Http::post($url, $params);
//      }
//  }
 
  //whatsapp message end



}
