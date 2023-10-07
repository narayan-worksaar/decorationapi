<?php

namespace App\Http\Controllers\Voyager;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

use App\Models\AgentAssigned;
use App\Models\Service;
use App\Models\ServiceUpdatedByAgent;
use App\Models\TaskAcceptDeclinedNotification;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;


class ServiceController extends VoyagerBaseController
{
    use BreadRelationshipParser;

    public function index(Request $request)
    {
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
            'agentData'
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
        
        // dd($agentServiceUpdatedData);

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted','agentServiceUpdatedData'));
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

   

}
