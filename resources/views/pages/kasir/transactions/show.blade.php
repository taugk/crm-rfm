@extends('layouts.kasir')

@section('title', 'Detail Transaksi #' . $transaction->invoice_number)

@section('content')
<style>
    .detail-header {
        background: #fff;
        border-radius: var(--k-radius);
        border: 1px solid #EAECF0;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .invoice-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #EAECF0;
    }
    
    .invoice-number {
        font-family: var(--k-font-head);
        font-size: 24px;
        font-weight: 700;
        color: var(--k-dark);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        margin-bottom: 12px;
    }
    
    .info-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #98A2B3;
        margin-bottom: 4px;
    }
    
    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--k-dark);
    }
    
    .items-table {
        background: #fff;
        border-radius: var(--k-radius);
        border: 1px solid #EAECF0;
        overflow-x: auto;
        margin-bottom: 24px;
    }
    
    .items-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .items-table th {
        text-align: left;
        padding: 14px 16px;
        background: #F9FAFB;
        border-bottom: 1px solid #EAECF0;
        font-size: 12px;
        font-weight: 600;
        color: #667085;
    }
    
    .items-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #EAECF0;
        font-size: 13px;
        color: #344054;
    }
    
    .summary-card {
        background: #fff;
        border-radius: var(--k-radius);
        border: 1px solid #EAECF0;
        padding: 20px;
        width: 320px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        font-size: 14px;
    }
    
    .summary-row.total {
        font-size: 18px;
        font-weight: 700;
        color: var(--k-accent);
        border-top: 2px solid #EAECF0;
        margin-top: 8px;
        padding-top: 16px;
    }
    
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }
    
    .btn-print {
        padding: 12px 24px;
        background: #fff;
        border: 1px solid var(--k-accent);
        color: var(--k-accent);
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-print:hover {
        background: var(--k-accent-soft);
    }
    
    .btn-back {
        padding: 12px 24px;
        background: #F4F6FB;
        border: 1px solid #EAECF0;
        color: #344054;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .btn-back:hover {
        background: #EAECF0;
    }
    
    .flex-between {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
        flex-wrap: wrap;
    }
</style>

<div class="k-heading">
    <h2><i class="bi bi-receipt"></i> Detail Transaksi</h2>
    <p>Informasi lengkap transaksi penjualan</p>
</div>

<div class="detail-header">
    <div class="invoice-title">
        <div>
            <div class="invoice-number">#{{ $transaction->invoice_number }}</div>
            <div class="info-label" style="margin-top: 8px;">INVOICE</div>
        </div>
        <span class="badge-status {{ $transaction->status }}">
            @if($transaction->status == 'completed')
                <i class="bi bi-check-circle"></i> Selesai
            @elseif($transaction->status == 'pending')
                <i class="bi bi-clock"></i> Pending
            @else
                <i class="bi bi-x-circle"></i> Dibatalkan
            @endif
        </span>
    </div>
    
    <div class="info-grid">
        <div>
            <div class="info-item">
                <div class="info-label">Tanggal Transaksi</div>
                <div class="info-value">
                    {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d F Y H:i') }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Metode Pembayaran</div>
                <div class="info-value">
                    <i class="bi bi-{{ $transaction->payment_method == 'cash' ? 'cash-stack' : ($transaction->payment_method == 'qris' ? 'qr-code' : 'bank2') }}"></i>
                    {{ ucfirst($transaction->payment_method) }}
                </div>
            </div>
        </div>
        
        <div>
            <div class="info-item">
                <div class="info-label">Pelanggan</div>
                <div class="info-value">
                    {{ $transaction->customer->name ?? 'Guest' }}
                    @if($transaction->customer && $transaction->customer->type == 'member')
                        <span style="font-size: 11px; color: #2E90FA;">(Member)</span>
                    @endif
                </div>
            </div>
            @if($transaction->customer && $transaction->customer->phone)
            <div class="info-item">
                <div class="info-label">Kontak</div>
                <div class="info-value">{{ $transaction->customer->phone }}</div>
            </div>
            @endif
        </div>
        
        @if($transaction->promotion)
        <div>
            <div class="info-item">
                <div class="info-label">Kode Promo</div>
                <div class="info-value">
                    <span class="badge-promo">{{ $transaction->promotion->promo_code }}</span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Diskon Promo</div>
                <div class="info-value" style="color: #12B76A;">
                    -Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}
                </div>
            </div>
        </div>
        @endif
        
        @if($transaction->notes)
        <div>
            <div class="info-item">
                <div class="info-label">Catatan</div>
                <div class="info-value">{{ $transaction->notes }}</div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="flex-between">
    <div class="items-table" style="flex: 1;">
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $detail)
                <tr>
                    <td>
                        <strong>{{ $detail->productDetail->product->name }}</strong>
                        @if($detail->productDetail->variant)
                            <br><small style="color: #98A2B3;">Varian: {{ $detail->productDetail->variant }}</small>
                        @endif
                    </td>
                    <td>Rp {{ number_format($detail->price_at_purchase, 0, ',', '.') }}</td>
                    <td>{{ $detail->quantity }}</td>
                    <td>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="summary-card">
        <div class="summary-row">
            <span>Subtotal</span>
            <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
        </div>
        @if($transaction->discount_amount > 0)
        <div class="summary-row">
            <span>Diskon</span>
            <span style="color: #12B76A;">-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="summary-row">
            <span>PPN (11%)</span>
            <span>Rp {{ number_format($transaction->tax_total, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row total">
            <span>TOTAL</span>
            <span>Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span>
        </div>
    </div>
</div>

<div class="action-buttons">
    <a href="{{ route('kasir.transactions.print', $transaction->id) }}" class="btn-print" target="_blank">
        <i class="bi bi-printer"></i> Cetak Invoice
    </a>
    <a href="{{ route('kasir.transactions.history') }}" class="btn-back">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

@endsection

@section('scripts')
<script>
    function printInvoice() {
        window.print();
    }
</script>
@endsection