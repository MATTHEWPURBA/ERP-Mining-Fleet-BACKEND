<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FuelExport implements WithMultipleSheets
{
    protected $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    public function sheets(): array
    {
        return [
            'Summary' => new FuelSummarySheet($this->report['summary']),
            'Monthly' => new FuelMonthlySheet($this->report['monthly']),
            'Details' => new FuelDetailsSheet($this->report['records'])
        ];
    }
}

class FuelSummarySheet implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
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
                'Total Liters',
                number_format($this->summary['total_liters'], 2)
            ],
            [
                'Total Cost',
                number_format($this->summary['total_cost'], 2)
            ],
            [
                'Average Price per Liter',
                number_format($this->summary['average_price_per_liter'], 2)
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

class FuelMonthlySheet implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $monthly;

    public function __construct($monthly)
    {
        $this->monthly = $monthly;
    }

    public function array(): array
    {
        $data = [];
        
        foreach ($this->monthly as $month) {
            $data[] = [
                $month['month'],
                $month['records'],
                number_format($month['liters'], 2),
                number_format($month['cost'], 2),
                ($month['liters'] > 0) ? number_format($month['cost'] / $month['liters'], 2) : 0
            ];
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'Month',
            'Records',
            'Total Liters',
            'Total Cost',
            'Average Price per Liter'
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
        return 'Monthly';
    }
}

class FuelDetailsSheet implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
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
                number_format($record['liters'], 2),
                number_format($record['cost'], 2),
                ($record['liters'] > 0) ? number_format($record['cost'] / $record['liters'], 2) : 0,
                $record['odometer'],
                $record['date'],
                $record['creator']['name']
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
            'Liters',
            'Cost',
            'Price per Liter',
            'Odometer',
            'Date',
            'Recorded By'
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


// app/Exports/FuelExport.php