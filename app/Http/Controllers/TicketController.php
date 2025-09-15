<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Vehicle;
use App\Models\Violator;
use App\Models\Enforcer;
use Illuminate\Http\Request;
use App\Models\Violation;
use App\Models\TicketStatus;
use App\Models\ConfiscationType;
use App\Models\Flag;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\LogActivity;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('enforcer.dashboard');
    }
    // Build the currently logged-in actor (Admin or Enforcer)
    private function buildActor(): array
    {
        if (auth('enforcer')->check()) {
            $actor = auth('enforcer')->user();
            $name  = trim(($actor->fname ?? '').' '.($actor->mname ?? '').' '.($actor->lname ?? ''));
            $name  = trim(preg_replace('/\s+/', ' ', $name)) ?: ($actor->badge_num ?? 'Unknown');
            $label = 'Enforcer';
            // Optionally show badge no.
            $display = $name . ($actor->badge_num ? " ({$actor->badge_num})" : '');
            return [$actor, $label, $display];
        }
        if (auth('admin')->check()) {
            $actor = auth('admin')->user();
            $name  = $actor->name ?? trim(($actor->fname ?? '').' '.($actor->lname ?? ''));
            $label = 'Admin';
            return [$actor, $label, $name ?: 'Admin'];
        }
        // Fallback (system tasks)
        return [null, 'System', 'System'];
    }
    public function storeJson(Request $request)
    {
        // JSON background sync endpoint (CSRF-exempt)
        try {
            return $this->persistTicket($request);
        } catch (\Throwable $e) {
            Log::error('storeJson 500', ['m' => $e->getMessage(), 'f'=>$e->getFile(), 'l'=>$e->getLine()]);
            return response()->json(['message'=>'Server error while issuing ticket.'], 500);
        }
    }
    private function persistTicket(Request $req)
    {
        // ✅ Relaxed rules to match what you actually send during offline
        $data = $req->validate([
            'first_name'     => 'required|string',
            'last_name'      => 'required|string',
            'license_number' => 'nullable|string',
            'plate_number'   => 'nullable|string',
            'vehicle_type'   => 'required|string',
            'violations'     => 'array|min:1',
            'location'       => 'nullable|string',     // was required — loosened
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'client_uuid'    => 'nullable|string|max:64', // don’t force uuid()
            'enforcer_id'    => 'nullable|integer',       // ✅ accept from payload
        ]);

        // ✅ No-session fallback (offline sync)
        $enforcerId = auth('enforcer')->id() ?: ($data['enforcer_id'] ?? null);
        if (!$enforcerId) {
            // for LOCAL TESTING ONLY: default to first enforcer
            $enforcerId = Enforcer::value('id'); // null-safe if table empty
        }

        // idempotency (optional if you pass client_uuid)
        $uuid = $data['client_uuid'] ?? $req->header('X-Idempotency-Key');

        if ($uuid) {
            $existing = Ticket::where('client_uuid', $uuid)->first();
            if ($existing) {
                return response()->json($this->printerPayload($existing), 200);
            }
        }

        // upsert violator/vehicle (as you already do) …
        $violator = Violator::firstOrCreate(
            ['license_number' => $data['license_number'] ?? null],
            [
                'first_name'  => $data['first_name'],
                'middle_name' => $req->input('middle_name'),
                'last_name'   => $data['last_name'],
                'address'     => $req->input('address'),
                'birthdate'   => $req->input('birthdate'),
            ]
        );

        $vehicle = Vehicle::firstOrCreate(
            ['plate_number' => $data['plate_number'] ?? null],
            [
                'violator_id' => $violator->id,
                'vehicle_type'=> $data['vehicle_type'],
                'is_owner'    => (bool)$req->input('is_owner', true),
                'owner_name'  => $req->input('owner_name'),
            ]
        );

        $ticket = DB::transaction(function () use ($req, $violator, $vehicle, $uuid, $enforcerId) {
            $t = Ticket::create([
                'violator_id'  => $violator->id,
                'vehicle_id'   => $vehicle->id,
                'ticket_number'=> 'SC-' . now()->format('YmdHis') . '-' . mt_rand(100,999),
                'issued_at'    => now(),
                'location'     => $req->input('location'),
                'latitude'     => $req->input('latitude'),
                'longitude'    => $req->input('longitude'),
                'enforcer_id'  => $enforcerId,          // ✅ never NULL now
                'client_uuid'  => $uuid,
                'is_impounded' => (bool)$req->boolean('is_impounded'),
            ]);

            $codes = (array)$req->input('violations', []);
            if ($codes) {
                $viols = Violation::whereIn('violation_code', $codes)->get();
                $t->violations()->sync($viols->pluck('id'));
            }
            return $t;
        });

        return response()->json($this->printerPayload($ticket), 201);
    }

    private function printerPayload(\App\Models\Ticket $ticket): array
    {
        $ticket->load(['violator','vehicle','violations','enforcer']);
        // Shape exactly what your printer JS expects
        return [
        'ticket' => [
            'ticket_number' => $ticket->ticket_number,
            'issued_at'     => $ticket->issued_at?->toDateTimeString(),
            'is_impounded'  => (bool)$ticket->is_impounded,
        ],
        'violator' => [
            'first_name'    => $ticket->violator->first_name,
            'middle_name'   => $ticket->violator->middle_name,
            'last_name'     => $ticket->violator->last_name,
            'birthdate'     => $ticket->violator->birthdate,
            'address'       => $ticket->violator->address,
            'license_number'=> $ticket->violator->license_number,
        ],
        'vehicle' => [
            'plate_number'  => $ticket->vehicle->plate_number,
            'vehicle_type'  => $ticket->vehicle->vehicle_type,
            'is_owner'      => (bool)$ticket->vehicle->is_owner,
            'owner_name'    => $ticket->vehicle->owner_name,
        ],
        'violations' => $ticket->violations->map(fn($v) => [
            'code' => $v->violation_code,
            'name' => $v->violation_name,
            'fine' => $v->fine_amount,
        ])->values(),
        // generate or fetch if you already created them:
        'credentials' => [
            'username' => $ticket->username ?? 'N/A',
            'password' => $ticket->password ?? 'N/A',
        ],
        'last_apprehended_at' => null,
        'enforcer' => [
            'badge_num' => $ticket->enforcer->badge_num ?? '',
        ],
        ];
    }

    // Normalize violator full name from various schemas
    private function violatorFullName(?Violator $v): string
    {
        if (!$v) return 'Unknown violator';
        $candidates = [
            trim(($v->first_name ?? '').' '.($v->middle_name ?? '').' '.($v->last_name ?? '')),
            trim(($v->fname ?? '').' '.($v->mname ?? '').' '.($v->lname ?? '')),
            $v->name ?? '',
        ];
        $name = collect($candidates)->first(fn($n) => $n && trim($n) !== '');
        return trim(preg_replace('/\s+/', ' ', $name ?: 'Violator'));
    }

    public function suggestions(Request $request)
    {
        $term = $request->get('q', '');

         $matches = Violator::with('vehicles')
        // ▶️ Search first/middle/last name or the full concatenated name
        ->where(function($query) use ($term) {
            $query->where('first_name', 'like', "%{$term}%")
                  ->orWhere('middle_name', 'like', "%{$term}%")
                  ->orWhere('last_name', 'like', "%{$term}%")
                  ->orWhere(DB::raw("CONCAT_WS(' ', first_name, middle_name, last_name)"), 'like', "%{$term}%");
        })
        // ▶️ Also search license number
        ->orWhere('license_number', 'like', "%{$term}%")
        // ▶️ And any related vehicle’s plate number
        ->orWhereHas('vehicles', function($q) use ($term) {
            $q->where('plate_number', 'like', "%{$term}%");
        })
        ->limit(5)
        ->get()
        ->map(function($v) {
            // build a full name for the JSON payload
            $fullName = trim(implode(' ', array_filter([
                $v->first_name,
                $v->middle_name,
                $v->last_name,
            ])));

            return [
                'id'             => $v->id,
                'first_name'           => $v ->first_name,
                'middle_name'          => $v->middle_name,
                'last_name'            => $v->last_name,
                'license_number' => $v->license_number,
                'plate_number'   => $v->vehicles->pluck('plate_number')->first() ?? null,
            ];
        });

        return response()->json($matches);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
        $grouped = Violation::orderBy('category')
                        ->orderBy('violation_name')
                        ->get()
                        ->groupBy('category');
        
        $violator = null;   
        if ($request->filled('violator_id')) {
            $violator = Violator::with('vehicles')
            ->find($request->input('violator_id'));
        }              
        return view('enforcer.issueTicket', [
            'violationGroups' => $grouped,
            'violator'        => $violator,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $d = $request->validate([
            'first_name'    => 'nullable|string|max:50',
            'middle_name'   => 'nullable|string|max:50',
            'last_name'     => 'nullable|string|max:50',
            'address'       => 'nullable|string|min:2',
            'birthdate'     => 'nullable|date',
            'license_num'   => 'nullable|string|max:50|min:8',
            'plate_num'     => 'required|string|max:50|min:5',
            'vehicle_type'  => 'required|string',
            'is_owner'      => 'sometimes|boolean',
            'flags'         => 'array',           // you can also validate an incoming flags[] if you switch to that
            'flags.*'       => 'exists:flags,id',
            'owner_name'    => 'nullable|string|max:255',
            'violations'    => 'required|array|min:1',
            'location'      => 'nullable|string',
            'latitude'      => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
            'confiscation_type_id'   => 'nullable|exists:confiscation_types,id',
        ]);
        // 2) Violator (firstOrNew then save)

        $violator = Violator::firstOrNew(['license_number' => $d['license_num']]);
        if (! $violator->exists) {
            $violator->first_name      = $d['first_name'];
            $violator->middle_name      = $d['middle_name'];
            $violator->last_name      = $d['last_name'];
            $violator->address   = $d['address'];
            $violator->birthdate = $d['birthdate'];
        }
        $violator->save();

        $existingPlate = Vehicle::where('plate_number', $d['plate_num'])->first();
        if ($existingPlate && $existingPlate->violator_id !== $violator->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The plate number is already registered under another violator.',
                ], 422);
            }

            return back()
                ->withInput()
                 ->with('duplicate_error', 'The plate number is already registered under another violator.');
        }

        // possibly create credentials
        if ($violator->wasRecentlyCreated) {
            $violator->username = 'user'.rand(1000,9999);
        
            // generate an 8-char password
            $rawPwd = 'violator'.rand(1000,9999); // default
        
            // set the default password
            $violator->defaultPassword = Hash::make($rawPwd);
            
            $violator->save();
        
            // return the plain text so you can e.g. email it
            $creds = [
              'username' => $violator->username,
              'password' => $rawPwd,
            ];
        } else {
            $creds = ['username'=>'Existing','password'=>'Existing'];
        }

        // 3) Vehicle
        $vehicle = Vehicle::firstOrCreate(
            ['plate_number' => $d['plate_num']],
            [
                'violator_id'  => $violator->id,
                'vehicle_type' => $d['vehicle_type'],
                'is_owner'     => $d['is_owner'] ?? true,
                'owner_name'   => $d['owner_name'],
            ]
        );

        $ticket = DB::transaction(function() use($d, $violator, $vehicle) {
            // a) Lock the enforcer row
            $enf = Enforcer::lockForUpdate()->find(auth()->guard('enforcer')->id());
    
            // b) Find the highest ticket_number they’ve already used
            $last = Ticket::where('enforcer_id', $enf->id)
                          ->lockForUpdate()
                          ->max('ticket_number');
    
            // c) Compute the next one
            if ($last !== null) {
                $next = $last + 1;
            } else {
                $next = $enf->ticket_start;
            }
    
            // d) Enforce their range
            if ($next > $enf->ticket_end) {
                throw ValidationException::withMessages([
                    'ticket_number' => "You’ve hit your allocated range ({$enf->ticket_start}–{$enf->ticket_end})."
                ]);
            }
            // 4) Ticket – note: no more violation_codes JSON
            $t = Ticket::create([
                'enforcer_id'            => $enf->id,
                'ticket_number'          => $next,
                'violator_id'            => $violator->id,
                'vehicle_id'             => $vehicle->vehicle_id,
                'violation_codes'        => json_encode($d['violations']),
                'location'               => $d['location'],
                'issued_at'              => now(),
                'offline'                => false,
                'status_id'              => TicketStatus::where('name','pending')->value('id'),
                'latitude'               => $d['latitude'] ?? null,
                'longitude'              => $d['longitude'] ?? null,
                'confiscation_type_id'   => $d['confiscation_type_id'],
            ]);

            $t->flags()->sync($d['flags'] ?? []);
            return $t;
        });

        // public function violations() { return $this->belongsToMany(Violation::class,'ticket_violation','ticket_id','violation_code','id','violation_code'); }
        $violationIds = Violation::whereIn('violation_code', $d['violations'])
                         ->pluck('id')
                         ->all();
        $ticket->violations()->sync($violationIds);
        // Get actor/role/name
        // actor/role/name
        [$actor, $role, $actorName] = $this->buildActor();

        // violator
        $violator     = $ticket->violator ?? \App\Models\Violator::find($ticket->violator_id);
        $violatorName = $this->violatorFullName($violator);

        // log only after final commit
        DB::afterCommit(function () use ($ticket, $violator, $violatorName, $actor, $role, $actorName) {
            try {
                LogActivity::on($ticket)
                    ->by($actor)
                    ->event('ticket.issued')
                    ->withProperties([
                        'ticket_id'   => $ticket->id,
                        'violator_id' => $violator?->id,
                        'violator'    => $violatorName,
                        'actor_role'  => $role,
                    ])
                    ->fromRequest()
                    ->log("{$role} {$actorName} issued a ticket (#{$ticket->id}) to {$violatorName}");
            } catch (\Throwable $e) {
                Log::warning('[ActivityLog skipped] '.$e->getMessage());
            }
        });

        // 7) Last apprehended before this ticket
        $prev = Ticket::where('violator_id',$violator->id)
                      ->where('id','!=',$ticket->id)
                      ->orderBy('issued_at','desc')
                      ->first();
        $lastAt = $prev
            ? $prev->issued_at->format('d M Y, H:i')
            : null;

        // 8) Build JSON payload (using relationships)
        $violations = Violation::whereIn('violation_code', json_decode($ticket->violation_codes))
                                     ->get()
                                     ->map(fn($v) => [
                                         'name'   => $v->violation_name,
                                         'fine'   => $v->fine_amount,
                                     ]);
        // >>> NEW: prefer what the enforcer typed for display ONLY (do not change DB)
        $violatorDisplay = [
            'first_name'     => $d['first_name']  ?? $violator->first_name,
            'middle_name'    => $d['middle_name'] ?? $violator->middle_name,
            'last_name'      => $d['last_name']   ?? $violator->last_name,
            'address'        => $d['address']     ?? $violator->address,
            'birthdate'      => $d['birthdate']   ?? $violator->birthdate,
            'license_number' => $violator->license_number, // keep canonical license
        ];                             

        return response()->json([
            'ticket' => [
                'id'                  => $ticket->id,
                'ticket_number'       => $ticket->ticket_number,
                'issued_at'           => optional($ticket->issued_at)->timezone('Asia/Manila')->format('d M Y, H:i'),
                'location'            => $ticket->location,
                'status'              =>  optional($ticket->status)->name      ?? 'Unknown',
                'confiscated'         =>  optional($ticket->confiscationType)->name ?? 'None',
                'is_impounded'        => (bool) $ticket->is_impounded,
                'is_resident'         => (bool) $ticket->is_resident,
                'flags'               => $ticket->flags->pluck('key')->all(),
            ],
            'last_apprehended_at' => $lastAt,
            'enforcer' => [
                'name'      => auth()->guard('enforcer')->user()->fname
                                .' '.auth()->guard('enforcer')->user()->lname,
                'badge_num' => auth()->guard('enforcer')->user()->badge_num,
            ],
            'violator'   => $violatorDisplay,
            'vehicle' => [
                'plate_number' => $vehicle->plate_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'is_owner'     => $vehicle->is_owner ? 'Yes' : 'No',
                'owner_name'   => $vehicle->owner_name,
            ],
            'violations'  => $violations,
            'credentials' => $creds,
        ]);
    
    }
    public function checkLicense(Request $request)
    {
        $license = trim((string)$request->query('license', ''));
        if ($license === '') {
            return response()->json(['exists' => false]);
        }

        $v = Violator::where('license_number', $license)->first();
        if (! $v) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'id'     => $v->id,
            'name'   => $this->violatorFullName($v),
        ]);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $violators = Violator::with([
            // load all vehicles (for your "Add Violation" dropdown)
            'vehicles',
            // load tickets *and* for each, load its vehicle, ordered by issued_at
            'tickets' => function($q) {
                $q->with('vehicle')
                ->orderBy('issued_at','desc');
            }
        ])->findOrFail($id);

        return view('enforcer.violatorDetails', compact('violators'));
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
