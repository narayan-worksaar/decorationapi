<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
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
        if ($column === 'id' || $column === 'service_code' || $column === 'status' || $column === 'slug' || $column === 'meta_data' || $column === 'created_at' || $column === 'updated_at') {
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
        $addNewService->fill($request->all());
        $addNewService->save();

        // Dynamically set the service_code
        $currentYear = date('Y');
        $idNumber = $addNewService->id;
        $addNewService->service_code = "SCode-{$currentYear}-{$idNumber}";
        $addNewService->save();
       
        return response()->json([
            "message" => "New service booking is successfull!",
            "status" => 200,
            // "newService" => $addNewService,
            
        ], 200);

      
    }

}
