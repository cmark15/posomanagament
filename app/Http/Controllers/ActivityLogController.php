<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    //
    public function index(Request $request)
    {
        // Map short actor types to FQCNs for filtering
        $actorTypeMap = [
            'admin'    => \App\Models\Admin::class,
            'enforcer' => \App\Models\Enforcer::class,
        ];
        $actorTypeFqcn = $actorTypeMap[$request->get('actor_type')] ?? null;

        $logs = ActivityLog::query()
            ->with(['actor','subject'])
            ->action($request->get('action'))
            ->actorType($actorTypeFqcn)
            ->dateFrom($request->get('date_from'))
            ->dateTo($request->get('date_to'))
            ->search($request->get('q'))
            ->latest('created_at')
            ->paginate(5)
            ->withQueryString();

        return view('admin.logs.activity', [
            'logs' => $logs,
            'filters' => [
                'action'     => $request->get('action', ''),
                'actor_type' => $request->get('actor_type', ''),
                'date_from'  => $request->get('date_from', ''),
                'date_to'    => $request->get('date_to', ''),
                'q'          => $request->get('q', ''),
            ],
        ]);
    }
}
