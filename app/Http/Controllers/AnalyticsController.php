<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Exports\SimpleStatsExport;  
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Shared\Html;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        
        $latestTicket = \App\Models\Ticket::latest()->first();
        $defaultLat   = $latestTicket->latitude  ?? 15.9285;
        $defaultLng   = $latestTicket->longitude ?? 120.3487;

        // limit options for UI
        $violationOptions = \App\Models\Violation::select('id','violation_name')
            ->orderBy('violation_name')
            ->limit(12)
            ->get();

        // Important: pass to the blade, no inline <script> needed
        return view('admin.analytics.index', compact('defaultLat','defaultLng','violationOptions'));
    }
    public function latest(Request $request)
    {
        // ----- Parse filters -----
        [$from, $to] = $this->parseDateRange($request->input('from'), $request->input('to'));
        $violationIds = collect($request->input('violations', []))->filter()->map('intval')->values();
        $status = $request->string('status')->lower()->toString(); // 'paid' | 'unpaid' | ''

        // base query
        $q = DB::table('tickets')->whereBetween('issued_at', [$from, $to]);

        // status filter
        if ($status === 'paid')   $q->where('status_id', 2);
        if ($status === 'unpaid') $q->where('status_id', 1);

        // optional join if you have a pivot for violations
        if ($violationIds->isNotEmpty() && Schema::hasTable('ticket_violation')) {
            $q->join('ticket_violation', 'ticket_violation.ticket_id', '=', 'tickets.id')
              ->whereIn('ticket_violation.violation_id', $violationIds);
        }

        // counts
        $paid   = (clone $q)->where('status_id', 2)->count();
        $unpaid = (clone $q)->where('status_id', 1)->count();

        // monthly counts across the selected range
        $monthly = (clone $q)
            ->selectRaw('DATE_FORMAT(issued_at, "%Y-%m") as ym, COUNT(*) as c')
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('c','ym');

        // build continuous month labels between from..to
        $labels = [];
        $cursor = Carbon::parse($from)->startOfMonth();
        $end    = Carbon::parse($to)->startOfMonth();
        while ($cursor <= $end) {
            $labels[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }
        $monthlySeries = collect($labels)->mapWithKeys(fn($ym)=>[$ym => (int)($monthly[$ym] ?? 0)]);

        // hotspots (by lat/lng)
        $hotspots = (clone $q)
            ->whereNotNull('tickets.latitude')
            ->whereNotNull('tickets.longitude')
            ->selectRaw('COALESCE(tickets.location,"Unknown") AS area, tickets.latitude, tickets.longitude, COUNT(*) AS c')
            ->groupBy('area','tickets.latitude','tickets.longitude')
            ->orderByDesc('c')
            ->get();

        return response()->json([
            'paid'           => (int)$paid,
            'unpaid'         => (int)$unpaid,
            'monthlyCounts'  => $monthlySeries, // keys = 'YYYY-MM'
            'hotspots'       => $hotspots,
        ]);
    }

     // NEW: tickets listing for a clicked hotspot (exact lat/lng match)
    public function hotspotTickets(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        [$from, $to] = $this->parseDateRange($request->input('from'), $request->input('to'));
        $violationIds = collect($request->input('violations', []))->filter()->map('intval')->values();

        $q = DB::table('tickets')
            ->whereBetween('issued_at', [$from, $to])
            ->where('latitude', $request->lat)
            ->where('longitude', $request->lng)
            ->select('id','issued_at','location','status_id');

        if ($violationIds->isNotEmpty() && Schema::hasTable('ticket_violation')) {
            $q->join('ticket_violation', 'ticket_violation.ticket_id', '=', 'tickets.id')
              ->whereIn('ticket_violation.violation_id', $violationIds);
        }

        $rows = $q->orderByDesc('issued_at')->limit(50)->get()
            ->map(function($r){
                return [
                    'id'        => $r->id,
                    'issued_at' => Carbon::parse($r->issued_at)->format('Y-m-d H:i'),
                    'location'  => $r->location ?? 'Unknown',
                    'status'    => (int)$r->status_id === 2 ? 'Paid' : 'Unpaid',
                ];
            });

        return response()->json(['tickets' => $rows]);
    }

    // helper: accept "YYYY-MM" or "YYYY-MM-DD" for from/to; defaults last 3 months
    protected function parseDateRange(?string $from, ?string $to): array
    {
        $defaultTo   = Carbon::now()->endOfDay();
        $defaultFrom = (clone $defaultTo)->copy()->subMonths(2)->startOfMonth(); // last 3 months window

        $f = $from ? (strlen($from)===7 ? Carbon::parse($from.'-01')->startOfMonth() : Carbon::parse($from)->startOfDay()) : $defaultFrom;
        $t = $to   ? (strlen($to)===7   ? Carbon::parse($to.'-01')->endOfMonth()  : Carbon::parse($to)->endOfDay())   : $defaultTo;

        return [$f, $t];
    }

    public function download(Request $request, $format)
    {
        // ---- Parse filters (reuse your helper; if missing, paste the parseDateRange() from earlier) ----
        [$from, $to] = $this->parseDateRange($request->input('from'), $request->input('to'));
        $status = strtolower((string)$request->input('status', '')); // '', 'paid', 'unpaid'
        $violationCodes = collect($request->input('violations', []))
            ->filter(fn($v) => trim($v) !== '')
            ->map(fn($v) => strtoupper(trim((string)$v)))
            ->values();

        $q = DB::table('tickets')->whereBetween('issued_at', [$from, $to]);

        if ($status === 'paid')   $q->where('status_id', 2);
        if ($status === 'unpaid') $q->where('status_id', 1);

        if ($violationCodes->isNotEmpty()) {
            $q->where(function($sub) use ($violationCodes) {
                foreach ($violationCodes as $code) {
                    $sub->orWhereRaw("FIND_IN_SET(?, REPLACE(tickets.violation_codes,' ',''))", [$code]);
                }
            });
        }

        $paid   = (clone $q)->where('status_id', 2)->count();
        $unpaid = (clone $q)->where('status_id', 1)->count();
        $total  = $paid + $unpaid;

        // Hotspots by human area (good for tabular reporting)
        $hotspots = (clone $q)
            ->selectRaw('COALESCE(location,"Unknown") AS area, COUNT(*) AS c')
            ->groupBy('area')
            ->orderByDesc('c')
            ->get();

        // Meta
        $tz    = 'Asia/Manila';
        $now   = now()->setTimezone($tz);
        $cover = $from->format('Y-m-d') . ' to ' . $to->format('Y-m-d');
        $genOn = $now->format('F d, Y h:i A');

        if ($format === 'xlsx') {
            // --- EXCEL (styled) ---
            $export = new \App\Exports\FormalStatsExport([
                'paid'    => $paid,
                'unpaid'  => $unpaid,
                'total'   => $total,
                'hotspots'=> $hotspots,
                'cover'   => $cover,
                'generated_on' => $genOn,
            ]);
            $fname = 'POSO_Analytics_' . $now->format('Ymd_His') . '.xlsx';
            return Excel::download($export, $fname);
        }

        // --- WORD (header with background, footer, tables) ---
        $phpWord = new PhpWord();
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::EN_US));
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop'    => 900,
            'marginBottom' => 900,
            'marginLeft'   => 1000,
            'marginRight'  => 1000,
        ]);

        // Header (green background with white text). Optional logo if present.
        $header = $section->addHeader();
        $tableH = $header->addTable(['borderSize'=>0, 'cellMargin'=>80, 'width'=>100*50]);
        $rowH   = $tableH->addRow();
        $cellH  = $rowH->addCell(100*50, ['bgColor'=>'1B5E20']); // POSO green
        $tr     = $cellH->addTextRun(['alignment'=>Jc::CENTER]);
        // If you placed a logo at resources/reports/logo.png, uncomment:
        // $cellH->addImage(resource_path('reports/logo.png'), ['height'=>24, 'alignment'=>Jc::CENTER]);
        $tr->addText('Public Order and Safety Office (POSO)', ['bold'=>true,'color'=>'FFFFFF','size'=>12]);
        $tr->addText('Digital Ticketing — Analytics Report',  ['color'=>'FFFFFF','size'=>10]);

        // Footer (page x of y)
        $footer = $section->addFooter();
        $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', ['size'=>9], ['alignment'=>Jc::CENTER]);

        // Title block
        $section->addTextBreak(1);
        $section->addText('Analytics Insights', ['bold'=>true,'size'=>16], ['alignment'=>Jc::CENTER]);
        $section->addText("Coverage: {$cover}    |    Generated: {$genOn}", ['color'=>'555555'], ['alignment'=>Jc::CENTER]);
        $section->addTextBreak(1);

        // Metrics table
        $table = $section->addTable([
            'alignment' => Jc::CENTER,
            'cellMargin'=> 80,
            'borderColor' => 'DDDDDD',
            'borderSize'  => 6,
            'width'       => 100*50,
        ]);
        $row = $table->addRow();
        $row->addCell(5000, ['bgColor'=>'F2F2F2'])->addText('Metric', ['bold'=>true]);
        $row->addCell(5000, ['bgColor'=>'F2F2F2'])->addText('Value',  ['bold'=>true]);

        $table->addRow(); $table->addCell(5000)->addText('Paid Tickets');   $table->addCell(5000)->addText((string)$paid);
        $table->addRow(); $table->addCell(5000)->addText('Unpaid Tickets'); $table->addCell(5000)->addText((string)$unpaid);
        $table->addRow(); $table->addCell(5000)->addText('Total Tickets');  $table->addCell(5000)->addText((string)$total);

        $section->addTextBreak(1);

        // Hotspots table (top 10)
        $section->addText('Top Hotspots (by location)', ['bold'=>true,'size'=>12]);
        $table2 = $section->addTable([
            'alignment' => Jc::LEFT,
            'cellMargin'=> 80,
            'borderColor' => 'DDDDDD',
            'borderSize'  => 6,
            'width'       => 100*50,
        ]);
        $r = $table2->addRow();
        $r->addCell(7000, ['bgColor'=>'F2F2F2'])->addText('Area', ['bold'=>true]);
        $r->addCell(3000, ['bgColor'=>'F2F2F2'])->addText('Tickets', ['bold'=>true]);

        if ($hotspots->isEmpty()) {
            $table2->addRow();
            $table2->addCell(7000)->addText('No geo-tagged tickets in this coverage.');
            $table2->addCell(3000)->addText('-');
        } else {
            foreach ($hotspots->take(10) as $h) {
                $row = $table2->addRow();
                $row->addCell(7000)->addText($h->area ?? 'Unknown');
                $row->addCell(3000)->addText((string)$h->c);
            }
        }

        $section->addTextBreak(1);

        // Data-driven suggestions (reuse your smarter logic)
        $lines = [];
        if ($hotspots->count() > 0) {
            $top = $hotspots->take(3)->map(fn($h) => "{$h->area} ({$h->c})")->implode(', ');
            $lines[] = "Hotspots: {$top}. Recommend increased patrols and random checkpoints in these areas.";
        }
        if ($total > 0) {
            $unpaidPct = round(($unpaid / $total) * 100, 1);
            $lines[] = "Unpaid rate is {$unpaidPct}% ({$unpaid}/{$total}). Implement notification reminders and a 7-day follow-up.";
        }
        if (empty($lines)) {
            $lines[] = "Maintain current deployment; no significant trends in the selected coverage.";
        }
        $section->addText('Recommendations', ['bold'=>true,'size'=>12]);
        foreach ($lines as $p) {
            $section->addListItem($p, 0, null, ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED]);
        }

        // Output
        $fname = 'POSO_Analytics_' . $now->format('Ymd_His') . '.docx';
        $tmp   = tempnam(sys_get_temp_dir(), 'rpt').'.docx';
        $phpWord->save($tmp, 'Word2007');

        return response()->download($tmp, $fname)->deleteFileAfterSend();
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
