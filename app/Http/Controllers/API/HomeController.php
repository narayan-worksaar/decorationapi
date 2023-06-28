<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserType;

class HomeController extends Controller
{
    public function user_types(Request $request){
    
        $usertypedata = UserType::whereIn('id', [2, 3])->select('id','usertype')->get();
        return response()->json([
            "status" => 200,
            "data" => $usertypedata
        ],200);
    }
}
