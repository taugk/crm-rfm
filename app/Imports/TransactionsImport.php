<?php

namespace App\Imports;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Customers;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TransactionsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Kelompokkan data berdasarkan invoice agar tidak duplikat header
        $groupedTransactions = $rows->groupBy('invoice_number');

        DB::transaction(function () use ($groupedTransactions) {
            
            // 1. Ambil atau Buat Kategori Default
            $defaultCategory = Category::firstOrCreate(
                ['name' => 'Uncategorized'],
                [
                    'kd_category' => 'CTG-' . strtoupper(Str::random(5)),
                    'slug'        => 'uncategorized'
                ]
            );

            foreach ($groupedTransactions as $invoiceNumber => $items) {
                if (empty($invoiceNumber)) continue;

                $firstItem = $items->first();
                $customerName = trim($firstItem['customer_name']);

                // 2. Logic Customer: Cari atau Buat
                $customer = Customers::firstOrCreate(
                    ['name' => $customerName],
                    [
                        'email' => strtolower(Str::slug($customerName)) . rand(10, 99) . '@example.com'
                    ]
                );

                // 3. Hitung Total & Subtotal untuk Invoice ini
                $calculatedSubtotal = 0;
                foreach ($items as $item) {
                    $calculatedSubtotal += ($item['quantity'] * $item['price_per_item']);
                }

                // 4. Simpan Header Transaksi
                $transaction = Transaction::create([
                    'invoice_number'   => $invoiceNumber,
                    'customer_id'      => $customer->id,
                    'transaction_date' => $this->transformDate($firstItem['date_yyyy_mm_dd']),
                    'subtotal'         => $calculatedSubtotal,
                    'discount_total'   => 0,
                    'tax_total'        => 0,
                    'total_price'      => $calculatedSubtotal,
                    'status'           => strtolower(trim($firstItem['status'] ?? 'pending')),
                    'payment_method'   => trim($firstItem['payment_method'] ?? 'Cash'),
                    'notes'            => $firstItem['notes'] ?? null,
                ]);

                // 5. Simpan Detail Produk (Auto Create Product & Details)
                foreach ($items as $item) {
                    $productName = trim($item['product_name']);
                    $price = $item['price_per_item'];

                    // A. Cari atau Buat Produk (Isi price, sku, category_id)
                    $product = Product::firstOrCreate(
                        ['name' => $productName],
                        [
                            'slug'        => Str::slug($productName),
                            'sku'         => 'SKU-' . strtoupper(Str::random(6)),
                            'category_id' => $defaultCategory->id, 
                            'price'       => $price, // Mengisi field 'price' di tabel products yang error tadi
                            'description' => 'Auto-generated from import',
                        ]
                    );

                    // B. Cari atau Buat Product Detail (Varian)
                    $productDetail = ProductDetail::firstOrCreate(
                        ['product_id' => $product->id],
                        [
                            'price' => $price,
                            'stock' => 0,
                        ]
                    );

                    // C. Simpan Detail Transaksi
                    TransactionDetail::create([
                        'transaction_id'    => $transaction->id,
                        'product_detail_id' => $productDetail->id,
                        'quantity'          => $item['quantity'],
                        'price_at_purchase' => $price,
                        'subtotal'          => $item['quantity'] * $price,
                    ]);
                }
            }
        });
    }

    /**
     * Helper Konversi Tanggal
     */
    private function transformDate($value)
    {
        if (empty($value)) return now();
        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return now();
        }
    }
}