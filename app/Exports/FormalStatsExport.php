<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class FormalStatsExport implements FromCollection, WithEvents, ShouldAutoSize
{
    protected int $paid;
    protected int $unpaid;
    protected int $total;
    protected Collection $hotspots;
    protected string $cover;
    protected string $generatedOn;

    public function __construct(array $data)
    {
        $this->paid        = (int)($data['paid'] ?? 0);
        $this->unpaid      = (int)($data['unpaid'] ?? 0);
        $this->total       = (int)($data['total'] ?? ($this->paid + $this->unpaid));
        $this->hotspots    = collect($data['hotspots'] ?? []);
        $this->cover       = (string)($data['cover'] ?? '');
        $this->generatedOn = (string)($data['generated_on'] ?? '');
    }

    public function collection()
    {
        $rows = [];
        $rows[] = ['POSO Digital Ticketing — Analytics Report'];
        $rows[] = ["Coverage: {$this->cover}    |    Generated: {$this->generatedOn}"];
        $rows[] = [''];

        $rows[] = ['Metric', 'Value'];
        $rows[] = ['Paid Tickets',   $this->paid];
        $rows[] = ['Unpaid Tickets', $this->unpaid];
        $rows[] = ['Total Tickets',  $this->total];
        $rows[] = [''];

        $rows[] = ['Top Hotspots (by location)'];
        $rows[] = ['Area', 'Tickets'];
        foreach ($this->hotspots as $h) {
            $rows[] = [$h->area ?? 'Unknown', (int)$h->c];
        }
        return collect($rows);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title + subtitle
                $sheet->mergeCells('A1:D1');
                $sheet->mergeCells('A2:D2');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold'=>true, 'color'=>['rgb'=>'FFFFFF'], 'size'=>14],
                    'alignment' => ['horizontal'=>'center'],
                    'fill' => ['fillType'=>'solid', 'startColor'=>['rgb'=>'1B5E20']]
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['color'=>['rgb'=>'555555']],
                    'alignment' => ['horizontal'=>'center']
                ]);

                // Metrics table
                $sheet->getStyle('A4:B4')->applyFromArray([
                    'font' => ['bold'=>true],
                    'fill' => ['fillType'=>'solid', 'startColor'=>['rgb'=>'F2F2F2']],
                    'borders' => ['allBorders'=>['borderStyle'=>'thin','color'=>['rgb'=>'DDDDDD']]]
                ]);
                $sheet->getStyle('A5:B7')->applyFromArray([
                    'borders' => ['allBorders'=>['borderStyle'=>'thin','color'=>['rgb'=>'DDDDDD']]]
                ]);

                // Hotspots block
                $hotStart = 9;  $hotHead = 10;  $hotFirst = 11;
                $sheet->mergeCells("A{$hotStart}:D{$hotStart}");
                $sheet->setCellValue("A{$hotStart}", 'Top Hotspots (by location)');
                $sheet->getStyle("A{$hotStart}")->applyFromArray(['font'=>['bold'=>true]]);

                $sheet->getStyle("A{$hotHead}:B{$hotHead}")->applyFromArray([
                    'font' => ['bold'=>true],
                    'fill' => ['fillType'=>'solid', 'startColor'=>['rgb'=>'F2F2F2']],
                    'borders' => ['allBorders'=>['borderStyle'=>'thin','color'=>['rgb'=>'DDDDDD']]]
                ]);

                $hotCount = $this->hotspots->count();
                if ($hotCount > 0) {
                    $hotLast = $hotFirst + $hotCount - 1;
                    $sheet->getStyle("A{$hotFirst}:B{$hotLast}")->applyFromArray([
                        'borders' => ['allBorders'=>['borderStyle'=>'thin','color'=>['rgb'=>'DDDDDD']]]
                    ]);
                } else {
                    $sheet->setCellValue("A{$hotFirst}", 'No geo-tagged tickets in this coverage.');
                    $sheet->mergeCells("A{$hotFirst}:D{$hotFirst}");
                }

                // Print header/footer
                $sheet->getHeaderFooter()->setOddHeader('&C&BPOSO Digital Ticketing — Analytics Report');
                $sheet->getHeaderFooter()->setOddFooter('&CPage &P of &N');
                $sheet->getPageSetup()->setFitToWidth(1);
            },
        ];
    }
}
    