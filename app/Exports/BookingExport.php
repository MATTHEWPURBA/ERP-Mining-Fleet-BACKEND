<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $bookings;

    public function __construct($bookings)
    {
        $this->bookings = $bookings;
    }

    public function collection()
    {
        return $this->bookings;
    }

    public function headings(): array
    {
        return [
            'ID',
            'User',
            'Vehicle',
            'Vehicle Type',
            'Location',
            'Purpose',
            'Start Date',
            'End Date',
            'Status',
            'Passengers',
            'Notes',
            'Created At'
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->id,
            $booking->user->name,
            $booking->vehicle->registration_no,
            $booking->vehicle->vehicleType->name,
            $booking->vehicle->location->name,
            $booking->purpose,
            $booking->start_date->format('Y-m-d H:i'),
            $booking->end_date->format('Y-m-d H:i'),
            $booking->status,
            $booking->passengers,
            $booking->notes,
            $booking->created_at->format('Y-m-d H:i')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}


// app/Exports/BookingExport.php