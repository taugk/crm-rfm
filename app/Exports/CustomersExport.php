<?php

namespace App\Exports;

use App\Models\Customers;
use Maatwebsite\Excel\Concerns\FromQuery; // Gunakan FromQuery untuk performa lebih baik
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class CustomersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Customers::query();

        // Terapkan filter berdasarkan parameter yang dikirim
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return ['ID', 'Nama', 'Email', 'Telepon', 'Poin', 'Status', 'Alamat'];
    }

    public function map($customer): array
    {
        return [
            '#PLG-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT),
            $customer->name,
            $customer->email ?? '-',
            $customer->phone,
            $customer->total_points,
            ucfirst($customer->status),
            $customer->full_address,
        ];
    }
}