<?php

namespace App\Http\Controllers\Voyager;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

use App\Exports\ServiceExport;
use App\Models\AgentAssigned;
use App\Models\Coordinate;
use App\Models\Service;
use App\Models\ServiceUpdatedByAgent;
use App\Models\Status;
use App\Models\TaskAcceptDeclinedNotification;
use App\Models\TaskType;
use App\Models\User;
use App\Models\Whatsappgroup;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use Illuminate\Database\QueryException;


class ServiceController extends VoyagerBaseController
{
    use BreadRelationshipParser;

    public function index(Request $request)
    {

        $allStatus = Status::get();
        $allServiceCenter = Coordinate::get();
        $allAgent = User::where('role_id',3)->get();
        $allDealer = User::where('role_id',4)->get();
         //get user id
         $loggedInUserId =  auth()->id();
         $loggedInUserCoordinate = User::where('id',$loggedInUserId)->select('coordinate')->first();

        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];


        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model::select($dataType->name.'.*');
            //try to export filter wise


            if ($request->has('action')) {
                // Check if the action is filter or export
                $action = $request->input('action');

                if ($action === 'filter') {
                    // Handle filter logic
                    $start_date = $request->input('start_date');
                    $end_date = $request->input('end_date');
                    $status_data = $request->input('status_data');
                    $service_center = $request->input('service_center');
                    $agent_data = $request->input('agent_data');
                    $dealer_data = $request->input('dealer_data');

                    // Apply status filter if provided
                    
                    if ($status_data) {
                        $query->where('status', $status_data);
                    }

                    if ($service_center) {
                        $query->where('coordinate', $service_center);
                    }

                     // Apply agent filter if provided
                     if ($agent_data) {
                        $query->where('assigned_agent_id', $agent_data);
                    }

                     // Apply Dealer filter if provided
                     if ($dealer_data) {
                        $query->where(function ($query) use ($dealer_data) {
                            $query->where('created_by_user_id', $dealer_data)
                                  ->orWhere('employee_of', $dealer_data);
                        });
                    }

                     // Apply date filter if both start and end dates are provided
                    if ($start_date && $end_date) {
                              // Convert date format using Carbon
        
                     $start_date = Carbon::createFromFormat('Y-m-d', $start_date)->format('d/m/Y');
                     $end_date = Carbon::createFromFormat('Y-m-d', $end_date)->format('d/m/Y');
        
                        // dd($start_date ." start and end date ". $end_date);
                        // $query->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                        $query->whereBetween('date_time', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                    }
                } elseif ($action === 'export') {
                    // Handle export logic

                    $status_data = $request->input('status_data');
                    $service_center = $request->input('service_center');
                    $start_date = $request->input('start_date');
                    $end_date = $request->input('end_date');
                    if ($start_date && $end_date) {
                    $start_date = Carbon::createFromFormat('Y-m-d', $start_date)->format('d/m/Y');
                    $end_date = Carbon::createFromFormat('Y-m-d', $end_date)->format('d/m/Y');
                    }
                    $agent_data = $request->input('agent_data');
                    $dealer_data = $request->input('dealer_data');
                    // Pass filter criteria to the export class
                    return Excel::download(new ServiceExport($status_data, $service_center, $start_date, $end_date, $agent_data, $dealer_data), 'service.xlsx');
                    
                }
            }


            if(isset($loggedInUserCoordinate['coordinate'])){
                $query->where('coordinate', $loggedInUserCoordinate['coordinate']);
                }

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';

                $searchField = $dataType->name.'.'.$search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name.'.*',
                        'joined.'.$row->details->label.' as '.$orderBy,
                    ])->leftJoin(
                        $row->details->table.' as joined',
                        $dataType->name.'.'.$row->details->column,
                        'joined.'.$row->details->key
                    );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        if(isset($loggedInUserCoordinate['coordinate'])){
         $agentData= User::where('role_id',3)->where('coordinate', $loggedInUserCoordinate['coordinate'])->get();
          }else{
         $agentData= User::where('role_id',3)->get();
        }


        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn',
            'agentData',
            'allStatus',
            'allAgent',
            'allDealer',
            'allServiceCenter'

        ));
    }


    public function view_agent_task ($id){

        $agentTaskData = ServiceUpdatedByAgent::where('service_id',$id)
        ->with('serviceData')
        ->with('statusData')
        ->with('userData')
        ->get();
        return view('vendor.voyager.services.view_agent_task', compact('agentTaskData'));

    }

    public function view_agent_task_details ($id){

        $agentTaskDetails = ServiceUpdatedByAgent::
        with('serviceData')
        ->with('statusData')
        ->with('userData')
        ->find($id);

        return view('vendor.voyager.services.view_agent_task_deatils', compact('agentTaskDetails'));

    }

    public function show_agent_list($id){

        $serviceData = Service::find($id);
        return response()->json([
        "status" => 200,
        "items" => $serviceData
        ],200);

    }
    /*
    public function update_agent (Request $request){

        $serviceId = $request->input('ser_id');
        $updateAgent = Service::find($serviceId);

        $userDeviceToken = User::find($updateAgent['created_by_user_id']);
        dd($userDeviceToken['device_token']);


        return redirect()->back()->with([
            'message'    => __('Agent assigned successfully!'),
            'alert-type' => 'success',
        ]);
    }
    */

    public function update_agent (Request $request){

        $serviceId = $request->input('ser_id');
        $updateAgent = Service::find($serviceId);
        $userDeviceToken = User::find($updateAgent['created_by_user_id']);

        $agentDeviceToken = User::find($request->agent_id);

        $updateAgent->assigned_agent_id = $request->input('agent_id');
        $updateAgent->status = 2;
        $updateAgent->update();

        $addNewAgent = new AgentAssigned();
        $addNewAgent->service_id = $serviceId;
        $addNewAgent->assigned_by = auth()->id();
        $addNewAgent->agent = $request->agent_id;
        $currentDateTime = now();
        $addNewAgent->assigned_date = $currentDateTime->toDateString();
        $addNewAgent->assigned_time = $currentDateTime->toTimeString();
        $addNewAgent->save();

        //fcm start

        //Send notification to dealer when agent assigned start

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
                'body' => 'On-going booking',
                'title' => 'Installer Assigned',
                'android_channel_id' => 'theinstallers',
                'sound' => true,
            ],
            'data' => [
                '_id' => $updateAgent['id'],
                '_serviceCode' => $updateAgent['service_code'],
            ],
        ];
        //Send notification to dealer when agent assigned end




          // Define the JSON body
          $bodyAssignedAgent = [
            'registration_ids' => [
                $agentDeviceToken['device_token'],
            ],
            'notification' => [
                'body' => 'Task assigned of ' . $updateAgent['client_name'],
                'title' => 'New task assigned',
                'android_channel_id' => 'theinstallers',
                'sound' => true,
            ],

        ];

        // Send the POST request
        $response = Http::withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send', $body);

        // Send the POST request
        $responseAgent = Http::withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send', $bodyAssignedAgent);

        //Send notification to agent when task assigned

        //fcm end


        return redirect()->back()->with([
            'message'    => __('Agent assigned successfully!'),
            'alert-type' => 'success',
        ]);
    }


    public function show(Request $request, $id)
    {


        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType, true);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'read', $isModelTranslatable);

        $view = 'voyager::bread.read';

        if (view()->exists("voyager::$slug.read")) {
            $view = "voyager::$slug.read";
        }
        $agentServiceUpdatedData= ServiceUpdatedByAgent::where('service_id',$dataTypeContent->id)
        ->with('userData')
        ->with('yesNoData')
        ->with('statusData')
        ->with('formImageData')
        ->with('siteImageData')

        ->get();

        $installerNotification= TaskAcceptDeclinedNotification::where('service_id',$dataTypeContent->id)
        ->with('installerName')
        ->get();

        $assignedAgentHistory= AgentAssigned::where('service_id',$dataTypeContent->id)
        ->with('agent_name')
        ->with('assignedByUser')
        ->get();

        // dd($agentServiceUpdatedData);

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted','agentServiceUpdatedData','installerNotification','assignedAgentHistory'));
    }

    public function read_notification (Request $request){

        $updateNotification = TaskAcceptDeclinedNotification::find($request->id);
        $updateNotification->is_read = 1;
        $updateNotification->update();

        return redirect()->back()->with([
            'message'    => __('Successfully read!'),
            'alert-type' => 'success',
        ]);
    }


    public function create(Request $request)
    {

        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
                            ? new $dataType->model_name()
                            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }



        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    /**
     * POST BRE(A)D - Store data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());



        // Set the 'service_code' value before saving
        $data->service_code = $this->generateServiceCode();
        $data->date_time = date('d/m/Y H:i', strtotime($request->date_time));
        $data->task_type_id = $request->task_type_id;
        $data->payment_mode_id = $request->payment_mode_id;
        $data->payment_mode_id = $request->payment_mode_id;
        $data->created_by_user_id = $request->created_by_user_id;
        $data->coordinate = $request->coordinate;
        $data->status = $request->status;
        $data->employee_of = $request->employee_of;
        $data->save();

         //Start whatsapp group message

         $groupIdData = Whatsappgroup::where('dealer_id', $request->created_by_user_id)
         ->with('instance_data')
         ->first();
         $taskTypeData=TaskType::find($request->task_type_id);


         if($groupIdData != null){
             // Your existing parameters
        // Convert address to latitude and longitude
        $geocodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
        $geocodingParams = [
            'address' => urlencode($data->address),
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
            $addressWithLink = $data->address . (" . $googleMapsUrl . ");

        $params = [
            'token' => $groupIdData['instance_data']['token_id'],
            'to' => $groupIdData->group_id,
            'body' => "New Booking: " . $data->service_code . "\n" . "Client Name: " . $data->client_name . "\n" . "Mobile No: " .$data->client_mobile_number . "\n" . "Service Date & Time: " .$data->date_time . "\n" . "Service Type: " .$taskTypeData->task_name . "\n" . "Address: " .$addressWithLink,
            'priority' => '10',
            'referenceId' => '',
            'msgId' => '',
            'mentions' => '',
        ];

        $url = 'https://api.ultramsg.com/' . $groupIdData['instance_data']['instance_id'] . '/messages/chat';

        $response = Http::post($url, $params);
        }
    }

        //end whatsapp group message




        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }
        protected function generateServiceCode()
        {

            $existingRowsCount = Service::count();
            // $idNumber = $existingRowsCount + 1;
            $idNumber = $existingRowsCount;
            $currentYear = date('Y');
            $serviceCode = "SCode-{$currentYear}-{$idNumber}";
            return $serviceCode;
        }


        public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    // POST BR(E)AD
    public function update(Request $request, $id)
    {
      

        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Compatibility with Model binding.
        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $model = app($dataType->model_name);
        $query = $model->query();
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
            $query = $query->{$dataType->scope}();
        }
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $query->withTrashed();
        }

        $data = $query->findOrFail($id);

        // Check permission
        $this->authorize('edit', $data);

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        // Get fields with images to remove before updating and make a copy of $data
        $to_remove = $dataType->editRows->where('type', 'image')
            ->filter(function ($item, $key) use ($request) {
                return $request->hasFile($item->field);
            });
        $original_data = clone($data);

        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

        // Delete Images
        $this->deleteBreadImages($original_data, $to_remove);

        $data->date_time = date('d/m/Y H:i', strtotime($request->date_time));
        $data->task_type_id = $request->task_type_id;
        $data->payment_mode_id = $request->payment_mode_id;
        $data->payment_mode_id = $request->payment_mode_id;
        $data->created_by_user_id = $request->created_by_user_id;
        $data->coordinate = $request->coordinate;
        $data->status = $request->status;
        $data->employee_of = $request->employee_of;
        $data->save();
          
        //Start whatsapp group message

        $groupIdData = Whatsappgroup::where('dealer_id', $request->created_by_user_id)
        ->with('instance_data')
        ->first();
        $taskTypeData=TaskType::find($request->task_type_id);


        if($groupIdData != null){
            // Your existing parameters
             // Convert address to latitude and longitude
             $geocodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
             $geocodingParams = [
                 'address' => urlencode($data->address),
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
                $addressWithLink = $data->address . (" . $googleMapsUrl . ");       

            $params = [
                'token' => $groupIdData['instance_data']['token_id'],
                'to' => $groupIdData->group_id,
                'body' => "Booking Updated: " . $data->service_code . "\n" . "Client Name: " . $data->client_name . "\n" . "Mobile No: " .$data->client_mobile_number . "\n" . "Service Date & Time: " .$data->date_time . "\n" . "Service Type: " .$taskTypeData->task_name . "\n" . "Address: " . $addressWithLink,
                'priority' => '10',
                'referenceId' => '',
                'msgId' => '',
                'mentions' => '',
            ];

       $url = 'https://api.ultramsg.com/' . $groupIdData['instance_data']['instance_id'] . '/messages/chat';

       $response = Http::post($url, $params);
       }
    }

       //end whatsapp group message
       

        event(new BreadDataUpdated($dataType, $data));

        if (auth()->user()->can('browse', app($dataType->model_name))) {
            $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Init array of IDs
        $ids = [];
        if (empty($id)) {
            // Bulk delete, get IDs from POST
            $ids = explode(',', $request->ids);
        } else {
            // Single item delete, get ID from URL
            $ids[] = $id;
        }

        $affected = 0;
        
        foreach ($ids as $id) {
            try {
                
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);

            // Check permission
            $this->authorize('delete', $data);

            $model = app($dataType->model_name);
            if (!($model && in_array(SoftDeletes::class, class_uses_recursive($model)))) {
                $this->cleanup($dataType, $data);
            }

            $res = $data->delete();

            if ($res) {
                $affected++;

                event(new BreadDataDeleted($dataType, $data));
            }
            } catch (QueryException $e) {
                // Catch the exception for integrity constraint violation
                $data = [
                    'message'    => "Can't delete due to relational data!",
                    'alert-type' => 'danger',
                ];

                return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
            }
        }

        $displayName = $affected > 1 ? $dataType->getTranslatedAttribute('display_name_plural') : $dataType->getTranslatedAttribute('display_name_singular');

        $data = $affected
            ? [
                'message'    => __('voyager::generic.successfully_deleted')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_deleting')." {$displayName}",
                'alert-type' => 'error',
            ];

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }




}
