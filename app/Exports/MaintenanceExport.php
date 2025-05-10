<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenanceExport implements WithMultipleSheets
{
    protected $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    public function sheets(): array
    {
        return [
            'Summary' => new MaintenanceSummarySheet($this->report['summary']),
            'Details' => new MaintenanceDetailsSheet($this->report['records'])
        ];
    }
}

class MaintenanceSummarySheet implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    public function array(): array
    {
        return [
            [
                'Total Records',
                $this->summary['total_records']
            ],
            [
                'Total Cost',
                number_format($this->summary['total_cost'], 2)
            ],
            [
                'Scheduled Maintenance Count',
                $this->summary['scheduled_count']
            ],
            [
                'Unscheduled Maintenance Count',
                $this->summary['unscheduled_count']
            ],
            [
                'Scheduled Maintenance Cost',
                number_format($this->summary['scheduled_cost'], 2)
            ],
            [
                'Unscheduled Maintenance Cost',
                number_format($this->summary['unscheduled_cost'], 2)
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Value'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}

class MaintenanceDetailsSheet implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function array(): array
    {
        $data = [];
        
        foreach ($this->records as $record) {
            $data[] = [
                $record['id'],
                $record['vehicle']['registration_no'],
                $record['vehicle']['vehicleType']['name'],
                $record['vehicle']['location']['name'],
                $record['type'],
                $record['description'],
                number_format($record['cost'], 2),
                $record['date'],
                $record['next_date'] ?? 'N/A'
            ];
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Vehicle',
            'Vehicle Type',
            'Location',
            'Maintenance Type',
            'Description',
            'Cost',
            'Date',
            'Next Scheduled Date'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Details';
    }
}
