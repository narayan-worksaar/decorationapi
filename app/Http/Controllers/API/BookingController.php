<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InstallationDetail;
use App\Models\MeasurementDetail;
use App\Models\Service;
use App\Models\TaskType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

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
    
        $all_on_pending_booking = Service::
        orderBy('id', 'DESC')
        ->where('created_by_user_id', auth()->id())
        ->where('status','pending')
        ->with('measurements_details')
        ->with('installation_details')
        ->with('tasktype')
        ->paginate(10);

        return response()->json([
            "status" => 200,
            "items" => $all_on_pending_booking
        ],200);
    }

}
