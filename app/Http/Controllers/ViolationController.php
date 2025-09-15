<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Services\LogActivity;
use App\Models\Violation;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;


class ViolationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $category = $request->get('category', 'all');
        $search   = $request->get('search', '');

        $q = Violation::query();
        if ($category !== 'all') {
            $q->where('category', $category);
        }
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('violation_code', 'like', "%{$search}%")
                ->orWhere('violation_name', 'like', "%{$search}%");
            });
        }

        // IMPORTANT: variable name is $violation (singular) to match your partial
        $violation = $q->orderBy('violation_code')
                    ->paginate(5)
                    ->appends(['category' => $category, 'search' => $search]);

        $categories = Violation::select('category')->distinct()->orderBy('category')->pluck('category');

        return view('admin.violation.violationList', [
            'violation'      => $violation,
            'categories'     => $categories,
            'categoryFilter' => $category,
            'search'         => $search,
        ]);
    }
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

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
       $lastViolation = Violation::where('violation_code', 'like', 'V%')
            ->orderByDesc(DB::raw('CAST(SUBSTRING(violation_code, 2) AS UNSIGNED)'))
            ->first();

        $nextViolation = $lastViolation
            ? 'V' . str_pad(((int)substr($lastViolation->violation_code, 1)) + 1, 3, '0', STR_PAD_LEFT)
            : 'V001';

        if ($request->ajax()) {
            return view('admin.partials.addViolation', compact('nextViolation'));
        }
        return view('admin.violation.addViolation', compact('nextViolation'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $data = $request->validate([
            'violation_code' => [
                'required', 'string', 'max:20',
                Rule::unique('violations', 'violation_code')->whereNull('deleted_at'),
            ],
            'violation_name' => [
                'required', 'string', 'max:255',
                Rule::unique('violations', 'violation_name')->whereNull('deleted_at'),
            ],
            'fine_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category'    => 'required|string|max:100',
        ]);

        $v = Violation::create($data);

        // SPA: return JSON so the table JS can simply reload the partial
        return response()->json([
            'success' => true,
            'message' => 'Violation added successfully.',
            'violation' => $v,
        ], 201);
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
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
       $v = Violation::findOrFail($id);

        $data = $request->validate([
            'violation_code' => [
                'sometimes','string','max:20',
                Rule::unique('violations','violation_code')->ignore($v->id)->whereNull('deleted_at'),
            ],
            'violation_name' => [
                'sometimes','string','max:255',
                Rule::unique('violations','violation_name')->ignore($v->id)->whereNull('deleted_at'),
            ],
            'fine_amount' => 'sometimes|numeric|min:0',
            'category'    => 'sometimes|string|max:100',
            'description' => 'sometimes|nullable|string',
        ]);

        // Build actor FIRST (so we can use it inside the closure safely)
        [$actor, $role, $actorName] = $this->buildActor();

        // Snapshot for a tiny diff (optional)
        $fields = array_keys($data);
        $before = $v->only($fields);

        DB::transaction(function () use ($v, $data, $request, $fields, $before, $actor, $role, $actorName) {
            // 1) Save
            $v->update($data);

            // 2) Compute changes after save
            $v->refresh();
            $changes = [];
            foreach ($fields as $f) {
                $old = $before[$f] ?? null;
                $new = $v->$f;
                if (is_numeric($old) && is_numeric($new)) { $old=(float)$old; $new=(float)$new; }
                if ($old !== $new) $changes[$f] = ['from'=>$old,'to'=>$new];
            }

            // 3) Register AFTER-COMMIT logger (registered while tx is open)
            DB::afterCommit(function () use ($request, $v, $changes, $actor, $role, $actorName) {
                $ref  = $v->violation_code ?: $v->getKey();
                $desc = "{$role} {$actorName} updated violation (#{$ref})";

                try {
                    LogActivity::on($v)
                        ->by($actor)
                        ->event('violation.updated')
                        ->withProperties([
                            'violation_id'   => $v->id,
                            'violation_code' => $v->violation_code,
                            'changed_fields' => array_keys($changes),
                            'changes'        => $changes ?: null,
                            'actor_role'     => $role,
                        ])
                        ->fromRequest($request)
                        ->log($desc);
                } catch (\Throwable $e) {
                    Log::warning('[ActivityLog skipped] '.$e->getMessage(), [
                        'ctx' => 'violation.update',
                        'violation_id' => $v->id,
                    ]);
                }
            });
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Violation updated successfully.',
            'violation' => $v->fresh(),
        ]);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Violation $violation)
    {
        $request->validate(['admin_password' => 'required']);
        $admin = auth('admin')->user();

        if (!$admin || !Hash::check($request->admin_password, $admin->password)) {
            return response()->json(['message' => 'Invalid admin password'], 422);
        }

        // if already archived (double click / stale row), return OK
        if (method_exists($violation, 'trashed') && $violation->trashed()) {
            return response()->json(['message' => 'Already archived'], 200);
        }

        $violation->delete(); // requires SoftDeletes on model
        return response()->json(['message' => 'Violation archived'], 200);
    }
    public function partial(Request $request)
    {
        //
        $category = $request->get('category', 'all');
        $search   = $request->get('search', '');

        $q = Violation::query();
        if ($category !== 'all') $q->where('category', $category);
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('violation_code', 'like', "%{$search}%")
                  ->orWhere('violation_name', 'like', "%{$search}%");
            });
        }

        $violation = $q->orderBy('violation_code')
            ->paginate(5)
            ->appends(['category' => $category, 'search' => $search]);

        return view('admin.partials.violationTable', [
            'violation'      => $violation,
            'categoryFilter' => $category,
            'search'         => $search,
        ]);
    }
     /** Small JSON endpoint used if you prefer fetching fresh data before edit */
    public function json(Violation $violation)
    {
        return response()->json($violation);
    }
}
