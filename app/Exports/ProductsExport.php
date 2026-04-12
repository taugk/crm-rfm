<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $request;

    // Kita tangkap data request (filter) melalui constructor
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        // Gunakan with() untuk eager loading agar query cepat
        $query = Product::query()->with(['category', 'details']);

        // Filter berdasarkan Pencarian (SKU atau Nama)
        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan Kategori
        if ($this->request->filled('category')) {
            $category = $this->request->category;
            $query->whereHas('category', function($q) use ($category) {
                // Mencocokkan nama kategori (karena di JS kita pakai strtolower nama kategori)
                $q->where('name', 'like', "%{$category}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Nama Produk',
            'Kategori',
            'Harga Jual',
            'Stok',
            'Harga Modal',
            'Varian',
            'Tanggal Masuk',
            'Tanggal Kadaluarsa'
        ];
    }

    public function map($p): array
    {
        // Karena relasi details adalah hasOne
        $detail = $p->details; 

        return [
            $p->sku,
            $p->name,
            $p->category->name ?? '-',
            $p->price,
            $detail->stock ?? 0,
            $detail->cost_price ?? 0,
            $detail->variant ?? '-',
            $detail->date_in ?? '-',
            $detail->expired_date ?? '-',
        ];
    }
}