<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LocalTestTicketController extends Controller
{
    //
    private function buildPayload(array $data): array
    {
        // normalize names your JS expects
        $first  = $data['first_name']  ?? 'Juan';
        $middle = $data['middle_name'] ?? '';
        $last   = $data['last_name']   ?? 'Dela Cruz';

        $violations = [];
        foreach (($data['violations'] ?? []) as $code) {
            $violations[] = [
                'code' => $code,
                'name' => "Violation {$code}",
                'fine' => 0.00,
            ];
        }

        return [
            'ticket' => [
                'ticket_number' => 'T-' . Carbon::now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'issued_at'     => Carbon::now()->toDateTimeString(),
                'is_impounded'  => (bool) ($data['is_impounded'] ?? false),
            ],
            'violator' => [
                'first_name'     => $first,
                'middle_name'    => $middle,
                'last_name'      => $last,
                'birthdate'      => $data['birthdate']      ?? null,
                'address'        => $data['address']        ?? 'San Carlos City, Pangasinan',
                'license_number' => $data['license_number'] ?? ($data['license_num'] ?? null),
            ],
            'vehicle' => [
                'plate_number' => $data['plate_number'] ?? ($data['plate_num'] ?? 'ABC-1234'),
                'vehicle_type' => $data['vehicle_type'] ?? 'Motorcycle',
                'is_owner'     => filter_var($data['is_owner'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'owner_name'   => $data['owner_name'] ?? trim("$first $middle $last"),
            ],
            'violations' => $violations,
            'credentials' => [
                'username' => 'V' . random_int(100000, 999999),
                'password' => Str::random(8),
            ],
            'enforcer' => [
                'badge_num' => $data['badge_num'] ?? 'TEST-001',
            ],
            'last_apprehended_at' => null,
        ];
    }

    public function store(Request $request)
    {
        // Only short-circuit in Local Test Mode
        if (!filter_var(env('POSO_LOCAL_TEST', false), FILTER_VALIDATE_BOOLEAN)) {
            return response()->json(['message' => 'Local test mode disabled.'], 403);
        }

        // Accept multipart/form-data (FormData)
        $data = $request->all();

        // Must have at least one violation code to match your JS flow
        if (empty($data['violations']) || !is_array($data['violations'])) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => ['violations' => ['Select at least one violation.']]
            ], 422);
        }

        return response()->json($this->buildPayload($data), 201);
    }

    public function sync(Request $request)
    {
        // Only short-circuit in Local Test Mode
        if (!filter_var(env('POSO_LOCAL_TEST', false), FILTER_VALIDATE_BOOLEAN)) {
            return response()->json(['message' => 'Local test mode disabled.'], 403);
        }

        // Accept JSON from SW
        $data = $request->json()->all() ?: $request->all();
        if (empty($data['violations']) || !is_array($data['violations'])) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => ['violations' => ['Select at least one violation.']]
            ], 422);
        }

        return response()->json($this->buildPayload($data), 201);
    }
}
