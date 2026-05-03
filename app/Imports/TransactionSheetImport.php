<?php

namespace App\Imports;

use App\Models\Customers;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Promotions;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TransactionSheetImport implements
    ToCollection,
    WithHeadingRow,
    WithStartRow,
    WithValidation,
    WithBatchInserts,
    SkipsEmptyRows,
    SkipsOnFailure,
    SkipsOnError
{
    use Importable, SkipsFailures, SkipsErrors;

    private int $importedCount = 0;
    private int $updatedCount  = 0;
    private array $formattedFailures = [];
    private string $batchId;

    public function __construct()
    {
        $this->batchId = Str::uuid()->toString();
    }

    public function startRow(): int { return 2; }
    public function batchSize(): int { return 200; }
    public function headingRow(): int { return 1; }

    public function collection(Collection $rows): void
    {
        set_time_limit(300);
        $startTime = microtime(true);
        
        Log::info('=== IMPORT TRANSACTIONS START ===', [
            'batch_id' => $this->batchId,
            'rows_count' => $rows->count(),
            'memory_usage' => memory_get_usage(true)
        ]);

        if ($rows->isEmpty()) {
            Log::warning('No rows found to import', ['batch_id' => $this->batchId]);
            return;
        }

        $firstRow = $rows->first();
        $isSimpleFormat = isset($firstRow['tanggal']) && isset($firstRow['nama_tagihan']) && isset($firstRow['produk']);
        
        Log::info('Format detected', [
            'batch_id' => $this->batchId,
            'is_simple_format' => $isSimpleFormat,
            'first_row_keys' => array_keys($firstRow->toArray())
        ]);

        try {
            if ($isSimpleFormat) {
                $this->importSimpleFormat($rows);
            } else {
                $this->importStandardFormat($rows);
            }
        } catch (\Exception $e) {
            Log::error('Import failed with exception', [
                'batch_id' => $this->batchId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        $duration = round(microtime(true) - $startTime, 2);
        Log::info('=== IMPORT TRANSACTIONS COMPLETE ===', [
            'batch_id' => $this->batchId,
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'duration_seconds' => $duration,
            'memory_usage' => memory_get_usage(true)
        ]);
    }

    /**
     * Format simple: Tanggal, Nama Tagihan, Harga, Produk
     * REVISI: Sekarang memproses setiap baris secara individu (Satu baris = Satu Invoice)
     */
    private function importSimpleFormat(Collection $rows): void
    {
        Log::info('Processing simple format (Individual Row Mode)', ['batch_id' => $this->batchId]);
        
        foreach ($rows as $index => $row) {
            $tanggal = trim($row['tanggal'] ?? '');
            $nama = trim($row['nama_tagihan'] ?? '');
            $produk = trim($row['produk'] ?? '');
            $harga = (float) ($row['harga'] ?? 0);

            if (empty($nama)) {
                Log::debug('Skipping row with empty customer name', ['index' => $index]);
                continue;
            }

            try {
                DB::transaction(function () use ($tanggal, $nama, $produk, $harga, $index) {
                    // 1. Dapatkan atau buat customer
                    $customer = $this->resolveCustomerByName($nama);

                    // 2. Generate invoice number unik (Setiap baris panggil generate baru)
                    $invoiceNumber = $this->generateInvoiceNumber();

                    // 3. Hitung subtotal (hanya untuk satu baris ini)
                    $subtotal = $harga;
                    $discount = 0;
                    $tax = round($subtotal * 0.11, 2);
                    $total = round($subtotal - $discount + $tax, 2);

                    // 4. Resolve Product Detail
                    $productDetail = $this->resolveProductDetailSimple($produk, $harga);

                    // 5. Buat transaksi
                    $transaction = Transaction::create([
                        'invoice_number'   => $invoiceNumber,
                        'customer_id'      => $customer->id,
                        'subtotal'         => round($subtotal, 2),
                        'discount_amount'  => $discount,
                        'tax_total'        => $tax,
                        'total_price'      => $total,
                        'status'           => 'completed',
                        'transaction_date' => $this->parseDateTime($tanggal),
                        'payment_method'   => 'cash',
                        'notes'            => 'Imported from simple format (Individual line)',
                    ]);

                    // 6. Buat detail transaksi
                    $transaction->details()->create([
                        'product_detail_id' => $productDetail->id,
                        'quantity' => 1,
                        'price_at_purchase' => $harga,
                        'subtotal' => $harga
                    ]);

                    // 7. Update customer info
                    $customer->update([
                        'type' => 'member',
                        'last_purchase_at' => $transaction->transaction_date,
                    ]);

                    $this->importedCount++;
                    
                    Log::debug('Row processed into invoice', [
                        'index' => $index,
                        'invoice' => $invoiceNumber,
                        'customer' => $nama
                    ]);
                });
            } catch (\Exception $e) {
                Log::error('Failed to process individual row', [
                    'index' => $index,
                    'nama' => $nama,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    /**
     * Format standar (tetap mengelompokkan berdasarkan invoice_number yang sudah ada di Excel)
     */
    private function importStandardFormat(Collection $rows): void
    {
        Log::info('Processing standard format', ['batch_id' => $this->batchId]);
        
        $grouped = $rows->groupBy(fn ($r) => trim($r['invoice_number'] ?? ''));
        Log::info('Groups created', [
            'batch_id' => $this->batchId,
            'total_groups' => count($grouped)
        ]);

        foreach ($grouped as $invoiceNumber => $lines) {
            if (empty($invoiceNumber)) {
                Log::warning('Skipping row with empty invoice_number', ['batch_id' => $this->batchId]);
                continue;
            }

            try {
                DB::transaction(function () use ($invoiceNumber, $lines) {
                    $first = $lines->first();
                    $customer = $this->resolveCustomer($first);
                    $promotion = $this->resolvePromotion($first['promotion_code'] ?? null);

                    $detailData = [];
                    $subtotal   = 0;

                    foreach ($lines as $line) {
                        $productDetail = $this->resolveProductDetail($line);
                        $qty   = max(1, (int) ($line['quantity'] ?? 1));
                        $price = (float) ($line['price_at_purchase'] ?? $productDetail->product->price ?? 0);
                        $lineSubtotal = (float) ($line['subtotal'] ?? 0);
                        if ($lineSubtotal <= 0) {
                            $lineSubtotal = $qty * $price;
                        }
                        $subtotal += $lineSubtotal;

                        $detailData[] = [
                            'product_detail_id' => $productDetail->id,
                            'quantity'          => $qty,
                            'price_at_purchase' => $price,
                            'subtotal'          => $lineSubtotal,
                        ];
                    }

                    $discount = (float) ($first['discount_amount'] ?? 0);
                    $tax      = (float) ($first['tax_total'] ?? ($subtotal * 0.11));
                    $total = max(0, $subtotal - $discount + $tax);

                    $transaction = Transaction::updateOrCreate(
                        ['invoice_number' => $invoiceNumber],
                        [
                            'customer_id'      => $customer->id,
                            'promotion_id'     => $promotion?->id,
                            'subtotal'         => round($subtotal, 2),
                            'discount_amount'  => round($discount, 2),
                            'tax_total'        => round($tax, 2),
                            'total_price'      => round($total, 2),
                            'status'           => $this->normalizeStatus($first['status'] ?? 'completed'),
                            'transaction_date' => $this->parseDateTime($first['transaction_date'] ?? now()),
                            'payment_method'   => $first['payment_method'] ?? null,
                            'notes'            => $first['notes'] ?? null,
                        ]
                    );

                    if ($transaction->wasRecentlyCreated) {
                        $this->importedCount++;
                    } else {
                        $this->updatedCount++;
                    }

                    $transaction->details()->delete();
                    foreach ($detailData as $d) {
                        $transaction->details()->create($d);
                    }

                    $customer->update([
                        'type' => 'member',
                        'last_purchase_at' => $transaction->transaction_date,
                    ]);
                });
            } catch (\Exception $e) {
                Log::error('Failed to process standard group', [
                    'invoice_number' => $invoiceNumber,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    // ================= CUSTOMER RESOLVERS =================

    private function resolveCustomerByName(string $name): Customers
    {
        $customer = Customers::where('name', $name)->first();
        if (!$customer) {
            $customer = Customers::create([
                'name'  => $name,
                'type'  => 'walk in',
                'status'=> 'active',
                'role'  => 'customer',
                'password' => bcrypt('member123'),
                'remember_token' => Str::random(10),
            ]);
        }
        return $customer;
    }

    private function resolveCustomer($row): Customers
    {
        $name = strtolower(trim($row['customer_name'] ?? ''));
        $identifier = trim($row['customer_phone_or_email'] ?? '');

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $email = $isEmail ? $identifier : null;
        $phone = $isEmail ? null : $identifier;

        $customer = null;

        if ($phone) {
            $customer = Customers::where('phone', $phone)->first();
        }
        if (!$customer && $email) {
            $customer = Customers::where('email', $email)->first();
        }
        if (!$customer) {
            $customer = Customers::where('name', $name)
                ->where(function ($q) use ($email, $phone) {
                    if ($email) $q->where('email', $email);
                    if ($phone) $q->orWhere('phone', $phone);
                })
                ->first();
        }

        if (!$customer) {
            $customer = Customers::create([
                'name'  => $name ?: $identifier,
                'email' => $email,
                'phone' => $phone,
                'type'  => 'member',
                'status'=> 'active',
                'role'  => 'customer',
                'password' => bcrypt('member123'),
                'remember_token' => Str::random(10),
            ]);
        } else {
            $customer->update([
                'name'  => $customer->name ?: $name,
                'email' => $customer->email ?: $email,
                'phone' => $customer->phone ?: $phone,
                'type'  => 'member',
            ]);
        }
        return $customer;
    }

    // ================= PRODUCT RESOLVERS =================

    private function resolveProductDetailSimple(string $productName, float $price): ProductDetail
    {
        $product = Product::whereRaw('LOWER(name) = ?', [strtolower($productName)])->first();
        if (!$product) {
            $category = Category::firstOrCreate(
                ['name' => 'General'],
                ['kd_category' => 'CAT-GEN', 'status' => 'active']
            );
            $product = Product::create([
                'name'        => $productName,
                'sku'         => 'AUTO-' . strtoupper(Str::random(6)),
                'category_id' => $category->id,
                'price'       => $price,
                'status'      => 'active',
            ]);
        } else {
            if ($product->price != $price) {
                $product->price = $price;
                $product->save();
            }
        }

        $detail = ProductDetail::where('product_id', $product->id)->whereNull('variant')->first();
        if (!$detail) {
            $detail = ProductDetail::create([
                'product_id' => $product->id,
                'variant'    => null,
                'stock'      => 0,
                'cost_price' => $price,
            ]);
        }
        return $detail;
    }

    private function resolveProductDetail($line): ProductDetail
    {
        $name    = trim($line['product_name'] ?? '');
        $variant = trim($line['variant'] ?? '');
        $price   = (float) ($line['price_at_purchase'] ?? 0);

        $product = Product::where('name', 'like', "%$name%")->first();
        if (!$product) {
            $category = $this->resolveCategory($line['category'] ?? null);
            $product = Product::create([
                'name'        => $name,
                'sku'         => 'AUTO-' . strtoupper(Str::random(6)),
                'category_id' => $category->id,
                'price'       => $price,
                'status'      => 'active',
            ]);
        }

        $detail = ProductDetail::where('product_id', $product->id)
            ->when($variant, fn ($q) => $q->where('variant', $variant))
            ->when(!$variant, fn ($q) => $q->whereNull('variant'))
            ->first();

        if (!$detail) {
            $detail = ProductDetail::create([
                'product_id' => $product->id,
                'variant'    => $variant ?: null,
                'stock'      => 0,
                'cost_price' => $price,
            ]);
        }
        return $detail;
    }

    private function resolveCategory(?string $name): Category
    {
        $name = trim($name ?: 'General');
        return Category::firstOrCreate(
            ['name' => $name],
            [
                'kd_category' => 'CAT-' . strtoupper(Str::random(5)),
                'status'      => 'active',
            ]
        );
    }

    private function resolvePromotion(?string $code): ?Promotions
    {
        if (!$code) return null;
        return Promotions::where('code', trim($code))->first();
    }

    // ================= VALIDATION =================
    public function rules(): array
    {
        return [];
    }

    // ================= HELPER =================
    private function normalizeStatus($value): string
    {
        return match (strtolower(trim($value))) {
            'pending' => 'pending',
            'cancelled', 'canceled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'completed',
        };
    }

    private function parseDateTime($value): string
    {
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                ->format('Y-m-d H:i:s');
        }
        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Exception $e) {
            return now()->toDateTimeString();
        }
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        // Pastikan order by invoice_number desc agar mendapat urutan terbaru
        $last = Transaction::where('invoice_number', 'like', $prefix . '%')
                    ->orderBy('invoice_number', 'desc')
                    ->value('invoice_number');

        if ($last) {
            $lastNumber = (int) substr($last, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        return $prefix . $newNumber;
    }

    // ================= RESULT =================
    public function getImportedCount(): int { return $this->importedCount; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getFormattedFailures(): array { return $this->formattedFailures; }
}