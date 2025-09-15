<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Violator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ViolatorManagementController extends Controller
{
    //
    public function violatorDash(){

        $violator = Auth::guard('violator')->user();
        $loginSuccess = session('login_success', false);

        // eager-load anything you need in the table
        $tickets = $violator
            ->tickets()
            ->with(['vehicle','status','confiscationType','violations'])
            ->orderBy('issued_at','desc')
            ->get();
        $activeNames = ['pending','unpaid'];
        $active    = $tickets->filter(fn($t) => in_array(optional($t->status)->name, $activeNames));
        $completed = $tickets->reject(fn($t) => in_array(optional($t->status)->name, $activeNames));

        $overdue = $active->first(function ($ticket) {
            $issuedDate = Carbon::parse($ticket->issued_at);
            $today      = Carbon::now();   // Asia/Manila per config

            $daysCount = 0;
            while ($issuedDate->lt($today)) {
                // only count Mon–Fri
                if (! in_array($issuedDate->dayOfWeek, [
                        Carbon::SATURDAY,
                        Carbon::SUNDAY
                    ])) {
                    $daysCount++;
                }
                $issuedDate->addDay();
            }

            return $daysCount > 3;
        }) !== null;

        // 4) Render view with all three variables
        return view('violator.dashboard', compact(
            'violator',
            'active',
            'completed',
            'loginSuccess',
        ))->with('ticket_overdue', $overdue);
    }

}
