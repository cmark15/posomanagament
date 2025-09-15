<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Ticket;
use App\Models\Enforcer;
use App\Models\Violator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminDashboardController extends Controller
{
    //
    public function adminDash()
    {
                // Summary counts
            // today in your app’s timezone
        $today = Carbon::today()->toDateString();

        // tickets issued *today*
        $ticketCount = Ticket::whereDate('issued_at', $today)->count();

        // enforcers created *today* (or: distinct enforcers who issued tickets today)
        // $enforcerCount = Ticket::whereDate('issued_at', $today)
        //                       ->distinct()
        //                       ->count('enforcer_id');
        $enforcerCount = Enforcer::whereDate('created_at', $today)->count();

        // violators registered *today*
        $violatorCount = Violator::whereDate('created_at', $today)->count();

        // recent lists, also scoped to today:
        $recentViolators = Violator::whereDate('created_at', $today)
                                   ->orderBy('created_at','desc')
                                   ->take(5)
                                   ->get();

        $recentTickets   = Ticket::whereDate('issued_at', $today)
                                 ->orderBy('issued_at','desc')
                                 ->take(5)
                                 ->get();

        return view('admin.index', compact(
            'ticketCount',
            'enforcerCount',
            'violatorCount',
            'recentViolators',
            'recentTickets'
        ));
    }
     /**
     * Show the form for editing the authenticated admin.
     */ 
    public function edit()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.profile.form', compact('admin'));
    }
     /**
     * Update the admin's profile.
     */
    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:admins,username,'.$admin->id],
            'password' => ['nullable','string','min:8','confirmed'],
        ]);

        $admin->name     = $data['name'];
        $admin->username = $data['username'];

        if (! empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }

        $admin->save();

        return redirect()
            ->route('admin.profile.edit')
            ->with('success','Profile updated successfully.');
    }
}
