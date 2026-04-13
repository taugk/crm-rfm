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

    public function startRow(): int { return 2; }
    public function batchSize(): int { return 200; }
    public function headingRow(): int { return 1; }

    public function collection(Collection $rows): void
    {
        $grouped = $rows->groupBy(fn ($r) => trim($r['invoice_number'] ?? ''));

        foreach ($grouped as $invoiceNumber => $lines) {
            if (empty($invoiceNumber)) continue;

            DB::transaction(function () use ($invoiceNumber, $lines) {
                $first = $lines->first();

                $customer  = $this->resolveCustomer($first);
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
                $tax      = (float) ($first['tax_total'] ?? 0);

                if ($tax <= 0) {
                    $tax = $subtotal * 0.11;
                }

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

                $transaction->wasRecentlyCreated
                    ? $this->importedCount++
                    : $this->updatedCount++;

                $transaction->details()->delete();
                foreach ($detailData as $d) {
                    $transaction->details()->create($d);
                }

                $customer->updateQuietly([
                    'type' => 'member',
                    'last_purchase_at' => $transaction->transaction_date,
                ]);
            });
        }
    }

    // ================= CUSTOMER =================

   private function resolveCustomer($row): Customers
{
    $name = strtolower(trim($row['customer_name'] ?? ''));
    $identifier = trim($row['customer_phone_or_email'] ?? '');

    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

    $email = $isEmail ? $identifier : null;
    $phone = $isEmail ? null : $identifier;

    // ================= PRIORITAS CEK GLOBAL =================
    $customer = null;

    if ($phone) {
        $customer = Customers::where('phone', $phone)->first();
    }

    if (!$customer && $email) {
        $customer = Customers::where('email', $email)->first();
    }

    // ================= FALLBACK: CEK BY NAME + IDENTIFIER =================
    if (!$customer) {
        $customer = Customers::where('name', $name)
            ->where(function ($q) use ($email, $phone) {
                if ($email) $q->where('email', $email);
                if ($phone) $q->orWhere('phone', $phone);
            })
            ->first();
    }

    // ================= CREATE =================
    if (!$customer) {
        $defaultPass = "member123";
        $customer = Customers::create([
            'name'  => $name ?: $identifier,
            'email' => $email,
            'phone' => $phone,
            'type'  => 'member',
            'status'=> 'active',
            'role'  => 'customer',
            'password' => bcrypt($defaultPass),
            'remember_token' => Str::random(10),
        ]);
    } else {
        // ================= UPDATE (ANTI DUPLIKAT) =================
        $customer->updateQuietly([
            'name'  => $customer->name ?: $name,
            'email' => $customer->email ?: $email,
            'phone' => $customer->phone ?: $phone,
            'type'  => 'member',
        ]);
    }

    return $customer;
}

    // ================= PRODUCT =================

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
            'kd_category' => 'CAT-' . strtoupper(Str::random(5)), // wajib
            'status'      => 'active',
        ]
    );
}

    // ================= PROMO =================

    private function resolvePromotion(?string $code): ?Promotions
    {
        return $code ? Promotions::where('code', trim($code))->first() : null;
    }

    // ================= VALIDATION =================

    public function rules(): array
    {
        return [
            'invoice_number' => ['required'],
            'customer_phone_or_email' => ['required'],
            'product_name' => ['required'],
        ];
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
        } catch (\Exception) {
            return now()->toDateTimeString();
        }
    }

    // ================= RESULT =================

    public function getImportedCount(): int { return $this->importedCount; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getFormattedFailures(): array { return $this->formattedFailures; }
}