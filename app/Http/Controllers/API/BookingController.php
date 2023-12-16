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
            $params = [
                'token' => $groupIdData['instance_data']['token_id'],
                'to' => $groupIdData->group_id,
                'body' => "New Booking: " . $addNewService->service_code . "\n" . "Client Name: " . $addNewService->client_name . "\n" . "Mobile No: " .$addNewService->client_mobile_number . "\n" . "Service Date & Time: " .$addNewService->date_time . "\n" . "Service Type: " .$taskTypeData->task_name . "\n" . "Address: " .$addNewService->address,
                'priority' => '10',
                'referenceId' => '',
                'msgId' => '',
                'mentions' => '',
            ];

            $url = 'https://api.ultramsg.com/' . $groupIdData['instance_data']['instance_id'] . '/messages/chat';

            $response = Http::post($url, $params);
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
 
    
    /*
    public function store_service_booking(Request $request)
    {
        
        $addNewService = new Service();
        $addNewService->fill($request->except('measure','install'));
        $addNewService->save();

        $currentYear = date('Y');
        $idNumber = $addNewService->id;
        $addNewService->service_code = "SCode-{$currentYear}-{$idNumber}";
        $addNewService->created_by_user_id = auth()->id();
        
        $addNewService->save();

        
        if ($request->has('measure')) {
            $measurements = $request->input('measure');
            // echo "<pre>";
            // print_r($measurements);
            // die;

            foreach ($measurements as $measurement) {
                $measure = new MeasurementDetail();
                $measure->service_id = $addNewService->id; 
                $measure->task_type_id = $addNewService->task_type_id; 
                $measure->created_by_user_id = $addNewService->created_by_user_id; 
                $measure->no_of_rolls_box_sqft = $measurement['no_of_rolls_box_sqft'];
                $measure->no_of_surface = $measurement['no_of_surface'];
                $measure->surface_details = $measurement['surface_details'];
                $measure->surface_condition_status = $measurement['surface_condition_status'];
                $measure->material_code = $measurement['material_code'];
                $measure->type_of_material = $measurement['type_of_material'];
                $measure->remarks = $measurement['remarks'];
                $measure->save();
            }
        }

        if ($request->has('install')) {
            $installations = $request->input('install');
        
            foreach ($installations as $installation) {
                $instal = new InstallationDetail();
                $instal->service_id = $addNewService->id; 
                $instal->task_type_id = $addNewService->task_type_id; 
                $instal->created_by_user_id = $addNewService->created_by_user_id; 
                $instal->no_of_rolls_box_sqft = $installation['no_of_rolls_box_sqft'];
                $instal->no_of_surface = $installation['no_of_surface'];
                $instal->surface_details = $installation['surface_details'];
                $instal->surface_condition_status = $installation['surface_condition_status'];
                $instal->material_code = $installation['material_code'];
                $instal->type_of_material = $installation['type_of_material'];
                $instal->remarks = $installation['remarks'];
                $instal->save();
            }
        }

        return response()->json([
            "message" => "New service booking is successful!",
            "status" => 200,
        ], 200);
    }
    */

        


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

    public function all_pending_booking(Request $request){
      
        $all_on_pending_booking = Service::orderBy('id', 'DESC')
        ->where(function ($query) {
            $query->where('created_by_user_id', auth()->id())
              ->orWhere('employee_of', auth()->id());
            })
            ->where('status', 1)
            ->with('tasktype')
            ->with('serviceCreator')
            ->paginate(10);

        return response()->json([
            "status" => 200,
            "items" => $all_on_pending_booking
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
            "task_type_id" => "required",
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
        $serviceUpdate->client_name = $request->client_name;
        $serviceUpdate->client_email_address = $request->client_email_address;
        $serviceUpdate->client_mobile_number = $request->client_mobile_number;
        $serviceUpdate->date_time = $request->date_time;
        $serviceUpdate->task_type_id = $request->task_type_id;
        $serviceUpdate->type_of_measurement = $request->type_of_measurement;   
        $serviceUpdate->type_of_material = $request->type_of_material;    
        $serviceUpdate->payment_mode_id = $request->payment_mode_id;
        $serviceUpdate->remarks = $request->remarks;
        $serviceUpdate->notes = $request->notes; 
        $serviceUpdate->coordinate = $request->coordinate;    
        $serviceUpdate->save();

        
         //tryin to send whatsapp group message    
        $taskTypeData=TaskType::find($serviceUpdate->task_type_id);   
        
        $groupIdData = Whatsappgroup::where('dealer_id', $serviceUpdate->dealer_id)
        ->with('instance_data')
        ->first();

        // dd($groupIdData);

        if($groupIdData != null){
             // Your existing parameters
        $params = [
            'token' => $groupIdData['instance_data']['token_id'],
            'to' => $groupIdData->group_id,
            'body' => "Booking Updated: " . $serviceUpdate->service_code . "\n" . "Client Name: " . $serviceUpdate->client_name . "\n" . "Mobile No: " .$serviceUpdate->client_mobile_number . "\n" . "Service Date & Time: " .$serviceUpdate->date_time . "\n" . "Service Type: " .$taskTypeData->task_name . "\n" . "Address: " .$serviceUpdate->address,
            'priority' => '10',
            'referenceId' => '',
            'msgId' => '',
            'mentions' => '',
        ];

        $url = 'https://api.ultramsg.com/' . $groupIdData['instance_data']['instance_id'] . '/messages/chat';

        $response = Http::post($url, $params);
    
        
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

}
