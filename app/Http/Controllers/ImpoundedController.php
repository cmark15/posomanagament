<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Flag;
use App\Models\ReleasedVehicle;

class ImpoundedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tickets = Ticket::with(['violator','vehicle'])
        ->whereHas('flags', fn($q) => $q->where('key','is_impounded'))
        ->orderBy('issued_at','desc')
        ->paginate(5);

        // Show only tickets that actually have a released_vehicles row
        $releasedTickets = Ticket::with(['violator','vehicle','releasedVehicle'])
            ->whereHas('releasedVehicle')
            ->join('released_vehicles', 'released_vehicles.ticket_id', '=', 'tickets.id')
            ->orderBy('released_vehicles.released_at', 'desc')
            ->select('tickets.*')
            ->get();

        return view('admin.impound.impoundedVehicle', compact('tickets', 'releasedTickets'));
    }

    public function resolve(Request $request)
    {
        $data = $request->validate([
            'ticket_id'        => 'required|exists:tickets,id',
            'reference_number' => 'required|digits:8|unique:released_vehicles,reference_number',
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);

        if ($ticket->releasedVehicle) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This vehicle has already been released.'
            ], 422);
        }

        $ticket->releasedVehicle()->create([
            'reference_number' => $data['reference_number'],
            'released_at'      => now(),
        ]);

        $impoundedFlagId = Flag::where('key','is_impounded')->value('id');
        if ($impoundedFlagId) {
            $ticket->flags()->detach($impoundedFlagId);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Vehicle successfully released!'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
