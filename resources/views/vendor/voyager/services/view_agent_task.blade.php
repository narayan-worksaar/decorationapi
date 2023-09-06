@extends('voyager::master')

@section('content')
<h1 class="page-title">
    <i class="voyager-meh"></i> View Agent Task &nbsp;
                                
    </a>
    </h1>
    <a href="{{ url('/admin/services') }}" class="btn btn-primary btn-add-new">
        <i class="voyager-angle-left"></i> <span>Back</span>
    </a>
    <div class="page-content browse container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sl No</th>
                                        <th>Service Code</th>
                                        <th>Form</th>
                                        <th>Remark</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Created Date</th>
                                        <th>Created Time</th>
                                        <th class="actions text-right dt-not-orderable sorting_disabled">Action</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($agentTaskData as $agent)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{ $agent['serviceData']['service_code'] }}</td>
                                            @if(isset($agent['form']))
                                            <td>
                                            <a href="{{ asset('storage/images/'.$agent['form']) }}" download>
                                             <img src="{{ asset('storage/images/'.$agent['form']) }}" width="50px" height="50px" alt="Service Form">
                                            </a>
                                            </td>
                                            @else
                                            <td>
                                            <img src="{{ asset('storage/images/noimage.png') }}" width="50px" height="50px" alt="Service Form">    
                                            </td>
                                            @endif
                                            <td>{{ Illuminate\Support\Str::limit($agent->remarks, $limit = 10, $end = '...') }}</td>
                                            @if(isset($agent['status']))
                                            <td>{{ $agent['statusData']['status_list'] }}</td>
                                            <td>{{ $agent['userData']['name'] }}</td>
                                            <td>{{ date('d-M-Y', strtotime($agent->created_date)) }}</td>
                                            <td>
                                            {{ \Carbon\Carbon::parse($agent->created_time)->format('g:i:s A')}}
                                            </td>
                                            @endif
                                            <td>
                                                <a href="{{ url('/admin/view-agent-task-details/'.$agent->id) }}" title="View agent task" class="btn btn-sm btn-warning pull-right view">
                                                    <i class="voyager-eye"></i> <span class="hidden-xs hidden-sm">View</span>
                                                </a>   
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $(document).ready(function () {
            // Initialize DataTable
            var table = $('#dataTable').DataTable({
                // Add any configuration options you need
            });
        });
    </script>
@stop
