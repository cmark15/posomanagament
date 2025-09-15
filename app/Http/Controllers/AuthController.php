<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationData;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //
    public function showLogin(){

        return view("auth.login");
    }

    public function login(Request $request){

        $validated = $request->validate([
            "username"=> "required|string",
            "password"=> "required|string"
        ]);
        
          if (Auth::guard('admin')->attempt($validated)) {
            $request->session()->regenerate();  // Regenerate session
            return redirect()->route('admin.dashboard'); // Redirect to admin dashboard
        }

        throw ValidationException::withMessages([
            'credentials'=> 'Incorrect password or username.'
        ]);
    }
    
    
    public function logout(Request $request){
        Auth::guard('admin')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.showLogin');
    }
}

