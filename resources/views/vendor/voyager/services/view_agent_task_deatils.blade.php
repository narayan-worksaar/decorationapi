@extends('voyager::master')

@section('page_title', __('Agent Task details'))

@section('content')
    <h1 class="page-title">
    <i class="voyager-lock"></i> View Agent Task Details &nbsp;
                                
    </a>
    </h1>
    <a href="{{ url('/admin/services') }}" class="btn btn-primary btn-add-new">
        <i class="voyager-angle-left"></i> <span>Back</span>
    </a>

    <div class="page-content read container-fluid">
        <div class="row">
           
            <div class="col-md-12">

                <div class="panel panel-bordered" style="padding-bottom:5px;">
                <!-- 1st data -->
                     <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Service Form</h3>
                </div>

                <div class="panel-body" style="padding-top:0;">
                    @if(isset($agentTaskDetails['form']))
                    <a href="{{ asset('storage/images/'.$agentTaskDetails['form']) }}" download>
                        <img src="{{ asset('storage/images/'.$agentTaskDetails['form']) }}" width="100px" height="100px" alt="Service Form">
                    </a>
                   @else
                   <img src="{{ asset('storage/images/noimage.png') }}" width="100px" height="100px" alt="Service Form">
                   @endif

                 
                
                </div>
                <hr style="margin:0;">
                <!-- end 1st data -->

                    <!-- 1st data -->
                    <div class="panel-heading" style="border-bottom:0;">
                            <h3 class="panel-title">Service Code</h3>
                    </div>

                    <div class="panel-body" style="padding-top:0;">
                    <p>{{ $agentTaskDetails['serviceData']['service_code'] }}</p>
                    </div>
                    <hr style="margin:0;">
                    <!-- end 1st data -->
                    
                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Remarks</h3>
                    </div>

                    <div class="panel-body" style="padding-top:0;">
                    <p>{{ $agentTaskDetails['remarks'] }}</p>
                    </div>
                    <hr style="margin:0;">

                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Status</h3>
                    </div>

                    <div class="panel-body" style="padding-top:0;">
                    <p>{{ $agentTaskDetails['statusData']['status_list'] }}</p>   
                    </div>
                    <hr style="margin:0;">

                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Created By</h3>
                    </div>

                    <div class="panel-body" style="padding-top:0;">
                    <p>{{ $agentTaskDetails['userData']['name'] }}</p>   
                    </div>
                    <hr style="margin:0;">

                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Created Date</h3>
                    </div>

                    <div class="panel-body" style="padding-top:0;">
                    <p>{{ date('d-M-Y', strtotime($agentTaskDetails->created_date)) }}</p>   
                    </div>
                    <hr style="margin:0;">

                    <!-- 2nd data start -->
                    <div class="panel-heading" style="border-bottom:0;">
                    <h3 class="panel-title">Created Time</h3>
                    </div>

                    <div class="panel-body" style="padding-top:0;">
                    <p> {{ \Carbon\Carbon::parse($agentTaskDetails->created_time)->format('g:i:s A')}}</p>
                    </div>
                    <!-- end 2nd data -->
                                            
                </div>
            </div>

        </div>
    </div>
@stop
