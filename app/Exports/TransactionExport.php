<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransactionExport implements FromQuery, WithMapping, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $filters;

    /**
     * Menerima parameter filter dari Controller
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Query data dengan Eager Loading mendalam (nested relationship)
     */
    public function query()
    {
        // Memuat relasi details, lalu ke product_detail, lalu ke produk
        $query = Transaction::with(['customer', 'details.product_detail.product']);

        // Filter: Pencarian (Invoice atau Nama Customer)
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Status
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Filter: Rentang Tanggal
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $this->filters['end_date']);
        }

        return $query->latest('transaction_date');
    }

    /**
     * Header Tabel Excel
     */
    public function headings(): array
    {
        return [
            'No. Invoice',
            'Nama Pelanggan',
            'Tanggal Transaksi',
            'Subtotal',
            'Diskon',
            'Pajak',
            'Total Harga',
            'Metode Pembayaran',
            'Status',
            'Daftar Produk (Item x Qty)',
        ];
    }

    /**
     * Mapping data per kolom
     */
    public function map($transaction): array
    {
        // Menggabungkan detail produk dengan akses relasi bertingkat
        $productSummary = $transaction->details->map(function ($detail) {
            // Mengambil nama dari tabel produk melalui product_detail
            // Menggunakan optional agar aman jika data relasi terhapus
            $productName = optional(optional($detail->product_detail)->product)->name 
                           ?? 'Produk Tidak Ditemukan';
            
            return "{$productName} ({$detail->quantity}x)";
        })->implode(', ');

        return [
            $transaction->invoice_number,
            $transaction->customer->name ?? 'Guest',
            $transaction->transaction_date->format('d-m-Y H:i'),
            $transaction->subtotal,
            $transaction->discount_total,
            $transaction->tax_total,
            $transaction->total_price,
            strtoupper($transaction->payment_method ?? 'CASH'),
            ucfirst($transaction->status),
            $productSummary,
        ];
    }

    /**
     * Format kolom numerik (D=Subtotal, E=Diskon, F=Pajak, G=Total)
     */
    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    /**
     * Styling Header
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '435EBE']
                ],
            ],
        ];
    }
}