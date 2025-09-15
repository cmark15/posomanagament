<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class SimpleStatsExport implements FromCollection
{
    protected int $paid;
    protected int $unpaid;
    protected Collection $hotspots;

    /**
     * @param array{paid:int,unpaid:int,hotspots:\Illuminate\Support\Collection} $data
     */
    public function __construct(array $data)
    {
        $this->paid     = $data['paid'];
        $this->unpaid   = $data['unpaid'];
        $this->hotspots = collect($data['hotspots']);
    }

    /**
     * Return a collection of rows for the spreadsheet.
     */
    public function collection()
    {
        $rows = [
            ['Metric',        'Value'],
            ['Paid Tickets',  $this->paid],
            ['Unpaid Tickets',$this->unpaid],
            [], // blank row
            ['Hotspot Area',  'Violations Count'],
        ];

        // append each hotspot
        foreach ($this->hotspots as $h) {
            // assuming $h has properties `area` and `c`
            $rows[] = [$h->area, (int) $h->c];
        }

        return collect($rows);
    }
}
