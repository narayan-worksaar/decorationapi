<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InstallationDetail;
use App\Models\MeasurementDetail;
use App\Models\PaymentMode;
use App\Models\Service;
use App\Models\TaskType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;
use App\Models\Whatsappgroup;
use Illuminate\Support\Facades\Http;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    public function guard()
    {
        return Auth::guard('api');

    }
    /*
    //using this fucntion we can get only fields name in array format
    public function all_service_booking_fields(){

        $tableColumns = Schema::getColumnListing('services');
        $columnsInfo = [];
        foreach ($tableColumns as $column) {
            $columnsInfo[] = $column;
            
        }
        return response()->json([
            "status" => 200,
            "fields" => $columnsInfo
        ], 200);
       
    }
    */
    
    public function all_service_booking_fields(){

        $tableColumns = Schema::getColumnListing('services');
        $columnsInfo = [];
        foreach ($tableColumns as $column) {
              // Skip 'id', 'created_at', and 'updated_at' fields
        if ($column === 'id' || $column === 'service_code' || $column === 'status' || $column === 'slug' || $column === 'meta_data' || $column === 'image' || $column === 'client_alternate_mobile_number' || $column === 'date_time' || $column === 'task_type_id' || $column === 'measurement' || $column === 'descriptions' || $column === 'action_type_id' || $column === 'assigned_agent_id' || $column === 'dealer_id' || $column === 'service_charge' || $column === 'created_at' || $column === 'updated_at') {
            continue;
        }
            $columnType = $this->getColumnType('services', $column);
            $columnsInfo[] = [
                'field_name' => $column,
                'type' => $columnType,
            ];
        }
        return response()->json([
            "status" => 200,
            "fields" => $columnsInfo
        ], 200);
       
    }
    

    private function getColumnType($table, $column)
    {
        $tablePrefix = DB::getTablePrefix();
        $columnType = DB::select("SHOW COLUMNS FROM {$tablePrefix}{$table} WHERE Field = ?", [$column]);

        if (!empty($columnType)) {
        // return $columnType[0]->Type;    
       // Extract the type name without length
        $type = $columnType[0]->Type;
        $typeName = preg_replace('/\(\d+\)/', '', $type);
        return $typeName;
            
        }

        return null;
    }

    public function store_service_booking(Request $request)
    {
        

        try {
            $validator = Validator::make($request->all(),[
                "address" => "required",
                "client_name" => "required",
                "client_mobile_number" => "required",
                "date_time" => "required",
                "task_type_id" => "required",
                "type_of_measurement" => "required",
                "type_of_material" => "required",
                "payment_mode_id" => "required",
                
                
            ]);
            if($validator->fails()){
                return response()->json($validator->errors(), 400);
            }

            $taskTypeData=TaskType::find($request->task_type_id);
            
            $existingRowsCount = Service::count();
            $idNumber = $existingRowsCount + 1;
            $loggedInUser = User::find(auth()->id());
            
            $addNewService = new Service();
            $currentYear = date('Y');
            $addNewService->service_code = "SCode-{$currentYear}-{$idNumber}";

            $addNewService->address = $request->address;
            $addNewService->landmark = $request->landmark;
            $addNewService->client_name = $request->client_name;
            $addNewService->client_email_address = $request->client_email_address;
            $addNewService->client_mobile_number = $request->client_mobile_number;
            $addNewService->date_time = $request->date_time;
            $addNewService->task_type_id = $request->task_type_id;
            $addNewService->type_of_measurement = $request->type_of_measurement;
            $addNewService->type_of_material = $request->type_of_material;
            $addNewService->quantity = $request->quantity;
            $addNewService->payment_mode_id = $request->payment_mode_id;
            $addNewService->remarks = $request->remarks;
            $addNewService->notes = $request->notes;
            $addNewService->coordinate = $request->coordinate; 
            $addNewService->created_by_user_id = auth()->id();
            
            $addNewService->save();
    
    
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

   
    public function task_type(){
    
        $tasktypedata = TaskType::select('id','task_name','task_status')->where('task_status','active')->get();
        return response()->json([
            "status" => 200,
            "items" => $tasktypedata
        ],200);
    }

    public function all_measurement_details_fields(){

        $tableColumns = Schema::getColumnListing('measurement_details');
        // rsort($tableColumns);
        $columnsInfo = [];
        foreach ($tableColumns as $column) {
              // Skip 'id', 'created_at', and 'updated_at' fields
        if ($column === 'id' || $column === 'service_id' || $column === 'task_type_id' || $column === 'surface_details' || $column === 'surface_condition_status' || $column === 'material_code' || $column === 'type_of_material' || $column === 'remarks' || $column === 'created_by_user_id' || $column === 'created_at' || $column === 'updated_at') {
            
            continue;
        }
            $columnType = $this->getColumnType('measurement_details', $column);
            $columnsInfo[] = [
                'field_name' => $column,
                'type' => $columnType,
            ];
        }
        return response()->json([
            "status" => 200,
            "fields" => $columnsInfo
        ], 200);
       
    }

    public function all_pending_booking(Request $request) {
        $searchQuery = $request->input('search');
        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');

            // Convert date format using Carbon
        // if ($fromDate) {
        //     $fromDate = Carbon::createFromFormat('d-m-Y', $fromDate)->format('Y-m-d');
        // }

        // if ($toDate) {
        //     $toDate = Carbon::createFromFormat('d-m-Y', $toDate)->format('Y-m-d');
        // }
       
        $all_completed_booking = Service::orderBy('created_at', 'DESC')
            ->where(function ($query) {
                $query->where('created_by_user_id', auth()->id())
                    ->orWhere('employee_of', auth()->id());
            })
            ->where('status', 1)
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where('service_code', 'like', '%' . $searchQuery . '%')
                    ->orWhere('client_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('client_email_address', 'like', '%' . $searchQuery . '%')
                    ->orWhere('client_mobile_number', 'like', '%' . $searchQuery . '%');
            })
        
            ->with('tasktype')
            ->with('serviceCreator')
            ->paginate(10);

            if ($fromDate && $toDate) {
                $all_completed_booking=Service::whereBetween('date_time', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                ->where(function ($query) {
                    $query->where('created_by_user_id', auth()->id())
                        ->orWhere('employee_of', auth()->id());
                })
                ->where('status', 1)
                ->with('tasktype')
                ->with('serviceCreator')
                ->paginate(10);
            }
            $all_completed_booking = $all_completed_booking;
        
        return response()->json([
            "status" => 200,
            "items" => $all_completed_booking
        ], 200);
    }

    public function payment_mode(){
    
        $paymentMode = PaymentMode::select('id','payment_method_name')->where('status','active')->get();
        return response()->json([
            "status" => 200,
            "items" => $paymentMode
        ],200);
    }

    public function get_service_details(Request $request){
        
        $validator = Validator::make($request->all(),[
            "service_id" => "required|numeric",
            
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        $single_service = Service::where('id',$request->service_id)
        ->with('tasktype')
        ->with('serviceCreator')
        ->with('paymentMode')
        ->get();
        return response()->json([
            "status" => 200,
            "items" => $single_service
        ],200);
    }

    public function update_booked_service(Request $request)
    {
        $validator = Validator::make($request->all(),[
                "address" => "required",
                "client_name" => "required",
                "client_mobile_number" => "required",
                "date_time" => "required",
                "task_type_id" => "required",
                "type_of_measurement" => "required",
                "type_of_material" => "required",
                "payment_mode_id" => "required",
       
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        $serviceUpdate = Service::find($request->id);
        
        if(!$serviceUpdate){

            return response()->json([
                "message" => "Data not found.",
            ],403);

        }

        try {
        $serviceUpdate->address = $request->address;
        $serviceUpdate->landmark = $request->landmark;
        $serviceUpdate->client_name = $request->client_name;
        $serviceUpdate->client_email_address = $request->client_email_address;
        $serviceUpdate->client_mobile_number = $request->client_mobile_number;
        $serviceUpdate->date_time = $request->date_time;
        $serviceUpdate->task_type_id = $request->task_type_id;
        $serviceUpdate->type_of_measurement = $request->type_of_measurement;   
        $serviceUpdate->type_of_material = $request->type_of_material;
        $serviceUpdate->quantity = $request->quantity;     
        $serviceUpdate->payment_mode_id = $request->payment_mode_id;
        $serviceUpdate->remarks = $request->remarks;
        $serviceUpdate->notes = $request->notes; 
        if($request->coordinate != null){
            $serviceUpdate->coordinate = $request->coordinate; 
        }
        
        $serviceUpdate->save();

        
         //tryin to send whatsapp group message    
        $taskTypeData=TaskType::find($serviceUpdate->task_type_id);   
        
        $groupIdData = Whatsappgroup::where('dealer_id', $serviceUpdate->dealer_id)
        ->with('instance_data')
        ->first();

        // dd($groupIdData);

        if($groupIdData != null){
             // Your existing parameters
             // Convert address to latitude and longitude
            $geocodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
            $geocodingParams = [
                'address' => urlencode($serviceUpdate->address),
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
                $addressWithLink = $serviceUpdate->address . (" . $googleMapsUrl . ");     

            $params = [
                'token' => $groupIdData['instance_data']['token_id'],
                'to' => $groupIdData->group_id,
                'body' => "Booking Updated: " . $serviceUpdate->service_code . "\n" . "Client Name: " . $serviceUpdate->client_name . "\n" . "Mobile No: " .$serviceUpdate->client_mobile_number . "\n" . "Service Date & Time: " .$serviceUpdate->date_time . "\n" . "Service Type: " .$taskTypeData->task_name . "\n" . "Address: " . $addressWithLink,
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
            "message" => "Updated successfully",
            "status" => 200,
            "item" =>$serviceUpdate
        ],200);
        } catch (\Exception $e) {
            // Return error response in JSON format
            return response()->json([
                "message" => 'Error: ' . $e->getMessage(),
                "status" => 500,
            ], 500);
        }

        

    }

      
    
    public function all_assigned_service(Request $request){
      
        $all_assigned_service = Service::orderBy('id', 'DESC')
        ->where('assigned_agent_id', auth()->id())
            ->where('status', 2)
            ->with('tasktype')
            ->with('serviceCreator')
            ->with('serviceAccept')
            
            ->paginate(10);

        return response()->json([
            "status" => 200,
            "items" => $all_assigned_service
        ], 200);
    }
    

    
    
    
    public function all_on_going_booking(){
      
        $all_on_going_booking = Service::orderBy('id', 'DESC')
        ->where(function ($query) {
            $query->where('created_by_user_id', auth()->id())
              ->orWhere('employee_of', auth()->id());
            })
            ->where('status', 2)
            ->with('tasktype')
            ->with('serviceCreator')
            ->with('assigned_agent')
            ->paginate(10);

        return response()->json([
            "status" => 200,
            "items" => $all_on_going_booking
        ], 200);
    }
    

    
    
    
    
        

    public function get_on_going_service_details(Request $request){
        
        $validator = Validator::make($request->all(),[
            "service_id" => "required|numeric",
            
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        $single_service = Service::where('id',$request->service_id)
        ->with('tasktype')
        ->with('serviceCreator')
        ->with('paymentMode')
        ->with('assigned_agent')
        ->get();
        return response()->json([
            "status" => 200,
            "items" => $single_service
        ],200);
    }
    /*
    public function all_completed_booking(){
      
        $all_completed_booking = Service::orderBy('id', 'DESC')
        ->where(function ($query) {
            $query->where('created_by_user_id', auth()->id())
              ->orWhere('employee_of', auth()->id());
            })
            ->where('status', 3)
            ->with('tasktype')
            ->with('serviceCreator')
            ->paginate(10);

        return response()->json([
            "status" => 200,
            "items" => $all_completed_booking
        ], 200);
    }
    */

   
    

    public function all_completed_booking(Request $request) {
        $searchQuery = $request->input('search');
        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');

                 
        $all_completed_booking = Service::orderBy('created_at', 'DESC')
            ->where(function ($query) {
                $query->where('created_by_user_id', auth()->id())
                    ->orWhere('employee_of', auth()->id());
            })
            ->where('status', 3)
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where('service_code', 'like', '%' . $searchQuery . '%')
                    ->orWhere('client_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('client_email_address', 'like', '%' . $searchQuery . '%')
                    ->orWhere('client_mobile_number', 'like', '%' . $searchQuery . '%');
            })
        
            ->with('tasktype')
            ->with('serviceCreator')
            ->paginate(10);

            if ($fromDate && $toDate) {
                $all_completed_booking=Service::whereBetween('date_time', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                ->where('status', 3)
                ->with('tasktype')
                ->with('serviceCreator')
                ->paginate(10);
            }
            $all_completed_booking = $all_completed_booking;
        
        return response()->json([
            "status" => 200,
            "items" => $all_completed_booking
        ], 200);
    }
   
     
    
    
    

    public function get_booking_completed_details(Request $request){
        
        $validator = Validator::make($request->all(),[
            "service_id" => "required|numeric",
            
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        $single_service = Service::where('id',$request->service_id)
        ->with('tasktype')
        ->with('serviceCreator')
        ->with('paymentMode')
        // ->with('task_completed_by_agent')
        ->with('task_upadated_by_agent')
        ->get();
        return response()->json([
            "status" => 200,
            "items" => $single_service
        ],200);
    }

    public function all_completed_service_of_agent(Request $request){
      
        $all_assigned_service = Service::orderBy('updated_at', 'DESC')
        ->where('assigned_agent_id', auth()->id())
            ->where('status', 3)
            ->with('tasktype')
            ->with('serviceCreator')
            ->with('serviceAccept')
            
            ->paginate(10);

        return response()->json([
            "status" => 200,
            "items" => $all_assigned_service
        ], 200);
    }

    public function duplicate_service_booking(Request $request)
    {
        $validator = Validator::make($request->all(),[
            "booking_id" => "required",
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
       
      
        try {
        $serviceDuplicate = Service::find($request->booking_id);
       

        $existingRowsCount = Service::count();
        $idNumber = $existingRowsCount + 1;
        $currentYear = date('Y');

        $loggedInUser = User::find(auth()->id());
        
        $addNewService = new Service();
        $addNewService->service_code = "SCode-{$currentYear}-{$idNumber}";
        $addNewService->client_name = $serviceDuplicate->client_name;
        $addNewService->client_email_address = $serviceDuplicate->client_email_address;
        $addNewService->client_mobile_number = $serviceDuplicate->client_mobile_number;
        $addNewService->date_time = $serviceDuplicate->date_time;
        $addNewService->task_type_id = $serviceDuplicate->task_type_id;
        $addNewService->remarks = $serviceDuplicate->remarks;
        $addNewService->notes = $serviceDuplicate->notes; 
        $addNewService->payment_mode_id = $serviceDuplicate->payment_mode_id;
        $addNewService->address = $serviceDuplicate->address;
        $addNewService->type_of_measurement = $serviceDuplicate->type_of_measurement;
        $addNewService->type_of_material = $serviceDuplicate->type_of_material;
        $addNewService->status = $serviceDuplicate->status;
        $addNewService->coordinate = $serviceDuplicate->coordinate;
        $addNewService->landmark = $serviceDuplicate->landmark;
        $addNewService->quantity = $serviceDuplicate->quantity;
        $addNewService->save();
        
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

        return response()->json([
            "message" => "Duplicate successfully",
            "status" => 200,
            "item" =>$addNewService
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
