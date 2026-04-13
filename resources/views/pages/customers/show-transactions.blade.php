@extends('layouts.customers')

@section('title', 'Detail Transaksi #' . $transaction->invoice_number)

@section('content')
<style>
    .receipt-card {
        border-radius: 24px;
        background: #fff;
        border: 1px solid #f1f5f9;
        position: relative;
    }
    
    /* Efek visual pembatas dashed */
    .receipt-divider {
        border-top: 2px dashed #e2e8f0;
        margin: 24px 0;
        position: relative;
    }

    .info-label {
        color: #94a3b8;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-value {
        color: #1e293b;
        font-weight: 700;
        font-size: 14px;
    }

    .product-img-detail {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 12px;
        background: #f1f5f9;
    }

    .total-section {
        background: #f8fafc;
        border-radius: 16px;
        padding: 20px;
    }

    .badge-status-lg {
        padding: 8px 16px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-8">
        {{-- Header & Back Button --}}
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('customers.transactions') }}" class="btn btn-white border rounded-circle shadow-sm me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-arrow-left text-dark"></i>
            </a>
            <div>
                <h4 class="fw-800 text-dark mb-0">Detail Transaksi</h4>
                <p class="text-muted small mb-0">Invoice #{{ $transaction->invoice_number }}</p>
            </div>
        </div>

        <div class="card receipt-card shadow-sm p-4 p-md-5">
            {{-- Status & Logo Area --}}
            <div class="text-center mb-5">
                <div class="mb-3">
                    @php
                        $statusStyles = [
                            'completed' => 'bg-success-subtle text-success',
                            'pending'   => 'bg-warning-subtle text-warning',
                            'cancelled' => 'bg-danger-subtle text-danger',
                        ][$transaction->status] ?? 'bg-secondary-subtle text-secondary';
                    @endphp
                    <span class="badge-status-lg {{ $statusStyles }}">
                        {{ $transaction->status }}
                    </span>
                </div>
                <h2 class="fw-900 text-dark mb-0">Rp{{ number_format($transaction->total_price) }}</h2>
                <p class="text-muted small">Waktu Transaksi: {{ $transaction->transaction_date->format('d M Y, H:i') }} WIB</p>
            </div>

            {{-- Info Grid --}}
            <div class="row g-4 mb-2">
                <div class="col-6 col-md-3">
                    <p class="info-label">Metode Bayar</p>
                    <p class="info-value mb-0">{{ strtoupper($transaction->payment_method ?? 'Tunai') }}</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="info-label">Poin Didapat</p>
                    <p class="info-value text-primary mb-0">+{{ number_format($transaction->total_price / 1000) }} PTS</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="info-label">No. Invoice</p>
                    <p class="info-value mb-0">{{ $transaction->invoice_number }}</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="info-label">Kasir</p>
                    <p class="info-value mb-0">Sistem CoffeeHub</p>
                </div>
            </div>

            <div class="receipt-divider"></div>

            {{-- Items List --}}
            <h6 class="fw-800 text-dark mb-4">RINGKASAN PESANAN</h6>
            @foreach($transaction->details as $detail)
            <div class="d-flex align-items-center mb-4">
                <img src="{{ $detail->product_detail->product->image ? asset('storage/'.$detail->product_detail->product->image) : 'https://ui-avatars.com/api/?name='.urlencode($detail->product_detail->product->name).'&background=f1f5f9&color=6366f1' }}" class="product-img-detail me-3">
                <div class="flex-grow-1">
                    <h6 class="fw-700 text-dark mb-1">{{ $detail->product_detail->product->name }}</h6>
                    <p class="small text-muted mb-0">Rp{{ number_format($detail->price_at_purchase) }} x {{ $detail->quantity }}</p>
                </div>
                <div class="text-end">
                    <p class="fw-800 text-dark mb-0">Rp{{ number_format($detail->subtotal) }}</p>
                </div>
            </div>
            @endforeach

            <div class="receipt-divider"></div>

            {{-- Calculation Section --}}
            <div class="row g-3 mb-4">
                <div class="col-7">
                    <p class="text-muted fw-600 mb-0">Subtotal</p>
                </div>
                <div class="col-5 text-end">
                    <p class="text-dark fw-700 mb-0">Rp{{ number_format($transaction->subtotal ?? $transaction->total_price) }}</p>
                </div>
                
                @if($transaction->discount_amount > 0)
                <div class="col-7">
                    <p class="text-success fw-600 mb-0">Potongan Diskon</p>
                </div>
                <div class="col-5 text-end">
                    <p class="text-success fw-700 mb-0">-Rp{{ number_format($transaction->discount_amount) }}</p>
                </div>
                @endif

                <div class="col-7">
                    <p class="text-muted fw-600 mb-0">Pajak (PPN 11%)</p>
                </div>
                <div class="col-5 text-end">
                    <p class="text-dark fw-700 mb-0">Rp{{ number_format($transaction->tax_total ?? 0) }}</p>
                </div>
            </div>

            <div class="total-section d-flex justify-content-between align-items-center">
                <h5 class="fw-800 text-dark mb-0">Total Akhir</h5>
                <h4 class="fw-900 text-primary mb-0">Rp{{ number_format($transaction->total_price) }}</h4>
            </div>

            {{-- Footer Note --}}
            <div class="text-center mt-5">
                <p class="small text-muted mb-0">Simpan struk digital ini sebagai bukti transaksi yang sah.</p>
                <div class="mt-4 d-print-none">
                    <button onclick="window.print()" class="btn btn-light rounded-pill px-4 fw-bold me-2">
                        <i class="bi bi-printer me-2"></i> Cetak
                    </button>
                    <a href="https://wa.me/?text=Cek struk kopi saya di CoffeeHub: {{ Request::url() }}" target="_blank" class="btn btn-success rounded-pill px-4 fw-bold">
                        <i class="bi bi-whatsapp me-2"></i> Bagikan
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 mb-5 opacity-50">
            <p class="small text-muted">&copy; {{ date('Y') }} CoffeeHub Digital Loyalty System</p>
        </div>
    </div>
</div>
@endsection