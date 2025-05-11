<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UtilizationExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $utilization;

    public function __construct($utilization)
    {
        $this->utilization = $utilization;
    }

    public function array(): array
    {
        $data = [];
        
        foreach ($this->utilization as $item) {
            $data[] = [
                $item['vehicle']['registration_no'],
                $item['vehicle']['vehicleType']['name'],
                $item['vehicle']['location']['name'],
                $item['vehicle']['status'],
                $item['total_bookings'],
                $item['booked_hours'],
                $item['total_hours'],
                $item['utilization_rate'] . '%'
            ];
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'Registration No',
            'Vehicle Type',
            'Location',
            'Current Status',
            'Total Bookings',
            'Booked Hours',
            'Total Hours Available',
            'Utilization Rate'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}



// app/exports/UtilizationExport.php