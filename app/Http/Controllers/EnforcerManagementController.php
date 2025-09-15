<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EnforcerManagementController extends Controller
{
    //
    public function enforcerDash()
    {
        return view('enforcer.dashboard');
    }
}
