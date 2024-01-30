<?php

namespace App\Exports;

use App\Models\Service;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpParser\Node\Expr\Cast\Array_;

class ServiceExport implements FromCollection, WithHeadings
{
    protected $status_data;
    protected $service_center;
    protected $start_date;
    protected $end_date;
    protected $agent_data;
    protected $dealer_data;

    // $agent_data = $request->input('agent_data');
    //                 $dealer_data = $request->input('dealer_data');

    public function __construct($status_data, $service_center, $start_date, $end_date, $agent_data, $dealer_data)
    
    {
        $this->status_data = $status_data;
        $this->service_center = $service_center;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->agent_data = $agent_data;
        $this->dealer_data = $dealer_data;

    }

    public function collection()
    {
        
        $query = Service::select('service_code', 'client_name', 'client_mobile_number','date_time','assigned_agent_id','created_by_user_id','status','coordinate');
        

         if ($this->status_data) {
             $query->where('status', $this->status_data);
         }

         if ($this->service_center) {
            $query->where('coordinate', $this->service_center);
         }

 
         if (!empty($this->start_date) && !empty($this->end_date)) {
            $query->whereBetween('date_time', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
        }

        if ($this->agent_data) {
            $query->where('assigned_agent_id', $this->agent_data);
        }

         
        if ($this->dealer_data) {
            $query->where(function ($query) {
                $query->where('created_by_user_id', $this->dealer_data)
                      ->orWhere('employee_of', $this->dealer_data);
            });
        }

        // Load the 'statusData' relationship for each service
        $services = $query->with('statusData')->with('serviceCentreData')->with('InstallerData')->with('CreatedByData')->get();
                

          // Transform the data and include the status list
          $transformedServices = $services->map(function ($service) {
            return [
                'service_code' => $service->service_code,
                'client_name' => $service->client_name,
                'client_mobile_number' => $service->client_mobile_number,
                'date_time' => $service->date_time,
                'assigned_agent_id' => $service->InstallerData->name ?? '',
                'created_by_user_id' => $service->CreatedByData->name ?? '',
                'status' => $service->statusData->status_list,
                'coordinate' => $service->serviceCentreData->place ?? '',
                
            ];
        });

        return collect($transformedServices->toArray());
         
    }

    public function headings():array
    {
        return [
            'Service Code',
            'Client Name',
            'Client Mobile Number',
            'Booking Date Time',
            'Installer Name',
            'Created By',
            'Status',
            'Service Centre',
        ];
    }
}
