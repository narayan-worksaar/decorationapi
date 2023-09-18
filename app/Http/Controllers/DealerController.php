<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DealerController extends Controller
{
    public function index()
    {
        
        $dealerUsers = User::where('role_id', 4)->get();
        return view('dealer.dealer');
        // return view('dealer', compact('dealerUsers', 'roles'));
    }
}
