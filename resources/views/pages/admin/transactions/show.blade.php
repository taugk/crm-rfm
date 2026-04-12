@extends('layouts.admin')

@section('title', 'Detail Transaksi - ' . $transaction->invoice_number)

@push('styles')
<style>
    :root {
        --invoice-primary: #435ebe;
        --invoice-secondary: #6c757d;
        --invoice-success: #198754;
        --invoice-bg: #f8f9fa;
    }

    /* Tampilan di Layar (Monitor) */
    .invoice-wrapper {
        background-color: white;
        border-radius: 1.5rem;
        overflow: hidden;
        box-shadow: 0 20px 27px 0 rgba(0, 0, 0, 0.05);
        margin-bottom: 3rem;
    }

    .invoice-top {
        padding: 3rem 3rem 2rem;
        background: linear-gradient(135deg, #435ebe 0%, #2e418a 100%);
        color: white;
    }

    .status-pill {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        padding: 0.5rem 1.25rem;
        border-radius: 50rem;
        font-weight: 600;
        font-size: 0.85rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .invoice-info-bar {
        padding: 2.5rem 3rem;
        background-color: white;
        border-bottom: 1px solid #edf2f9;
    }

    .info-label {
        color: var(--invoice-secondary);
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
        display: block;
    }

    .invoice-table-container {
        padding: 2rem 3rem;
    }

    .table-modern {
        border-collapse: separate;
        border-spacing: 0 1rem;
    }

    .table-modern thead th {
        background: transparent;
        border: none;
        color: var(--invoice-secondary);
        font-weight: 700;
        padding-bottom: 1.5rem;
    }

    .table-modern tbody tr {
        background-color: var(--invoice-bg);
    }

    .table-modern tbody td {
        border: none;
        padding: 1.25rem 1rem;
    }

    .table-modern tbody td:first-child { border-radius: 0.75rem 0 0 0.75rem; }
    .table-modern tbody td:last-child { border-radius: 0 0.75rem 0.75rem 0; }

    .summary-item {
        display: flex;
        justify-content: flex-end;
        gap: 4rem;
        margin-bottom: 0.75rem;
    }

    .grand-total {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 2px solid #edf2f9;
    }

    /* LOGIKA PRINT (HANYA INVOICE) */
    @media print {
        /* 1. Sembunyikan SEMUA elemen */
        body * {
            visibility: hidden;
        }

        /* 2. Tampilkan HANYA invoice-wrapper dan isinya */
        .invoice-wrapper, 
        .invoice-wrapper * {
            visibility: visible;
        }

        /* 3. Atur posisi invoice ke paling atas kertas */
        .invoice-wrapper {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }

        /* 4. Paksa warna background muncul di printer */
        .invoice-top {
            background: #435ebe !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-modern tbody tr {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .status-pill {
            border: 1px solid rgba(0,0,0,0.1) !important;
            color: #435ebe !important;
        }

        /* Hilangkan padding container bawaan template admin */
        #main, .page-content, .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
        }
    }
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            
            {{-- Tombol Navigasi (Otomatis hilang saat print) --}}
            <div class="d-flex justify-content-between align-items-center mb-4 btn-print-group">
                <a href="{{ route('admin.transactions') }}" class="btn btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-2"></i> Kembali ke Riwayat
                </a>
                <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-printer me-2"></i> Cetak Invoice
                </button>
            </div>

            {{-- AREA YANG DICETAK --}}
            <div class="invoice-wrapper">
                {{-- Header --}}
                <div class="invoice-top">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h2 class="fw-bold text-white mb-2">ALUNEA CAFE</h2>
                            <p class="mb-0 opacity-75 fw-medium">Invoice #{{ $transaction->invoice_number }}</p>
                        </div>
                        <div class="col-6 text-end">
                            <span class="status-pill text-uppercase">
                                {{ $transaction->status }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Baris Informasi --}}
                <div class="invoice-info-bar">
                    <div class="row">
                        <div class="col-4 border-end">
                            <span class="info-label">Pelanggan</span>
                            <h6 class="fw-bold mb-1">{{ $transaction->customer->name ?? 'Guest' }}</h6>
                            <p class="text-muted small mb-0">
                                {{ $transaction->customer_id == 1 ? 'Walk-in Customer' : ($transaction->customer->phone ?? '-') }}
                            </p>
                        </div>
                        <div class="col-4 border-end ps-4">
                            <span class="info-label">Waktu</span>
                            <h6 class="fw-bold mb-1">{{ $transaction->transaction_date->format('d M Y') }}</h6>
                            <p class="text-muted small mb-0">{{ $transaction->transaction_date->format('H:i') }} WIB</p>
                        </div>
                        <div class="col-4 ps-4">
                            <span class="info-label">Pembayaran</span>
                            <h6 class="fw-bold mb-1 text-primary">{{ strtoupper($transaction->payment_method) }}</h6>
                            <p class="text-muted small mb-0 uppercase">LUNAS</p>
                        </div>
                    </div>
                </div>

                {{-- Tabel Produk --}}
                <div class="invoice-table-container">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Menu Item</th>
                                    <th class="text-center">Harga</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transaction->details as $item)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark d-block">{{ $item->product_detail->product->name }}</span>
                                        <small class="text-muted">{{ $item->product_detail->variant ?? 'Regular' }}</small>
                                    </td>
                                    <td class="text-center">Rp{{ number_format($item->price_at_purchase, 0, ',', '.') }}</td>
                                    <td class="text-center fw-bold">{{ $item->quantity }}</td>
                                    <td class="text-end fw-bold text-dark">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Footer & Total --}}
                <div class="invoice-summary">
                    <div class="row">
                        <div class="col-6">
                            @if($transaction->notes)
                                <div class="bg-light p-4 rounded-4" style="border-left: 4px solid var(--invoice-primary)">
                                    <span class="info-label">Catatan:</span>
                                    <p class="small text-muted mb-0">{{ $transaction->notes }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="col-6 mt-md-0">
                            <div class="summary-item">
                                <span class="text-muted fw-medium">Subtotal</span>
                                <span class="fw-bold text-dark">Rp{{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="text-muted fw-medium">Pajak (11%)</span>
                                <span class="fw-bold text-dark">Rp{{ number_format($transaction->tax_total, 0, ',', '.') }}</span>
                            </div>
                            @if($transaction->discount_total > 0)
                                <div class="summary-item">
                                    <span class="text-success fw-medium">Diskon</span>
                                    <span class="fw-bold text-success">-Rp{{ number_format($transaction->discount_total, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="summary-item grand-total">
                                <h5 class="fw-bold mb-0">TOTAL</h5>
                                <h4 class="fw-bold text-primary mb-0">Rp{{ number_format($transaction->total_price, 0, ',', '.') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pesan Penutup --}}
                <div class="pb-5 px-3 text-center">
                    <hr class="w-25 mx-auto mb-4 opacity-10">
                    <p class="text-muted small">Terima kasih telah berkunjung ke <strong>Alunea Cafe</strong>.</p>
                </div>
            </div> {{-- End Invoice Wrapper --}}
            
        </div>
    </div>
</div>
@endsection