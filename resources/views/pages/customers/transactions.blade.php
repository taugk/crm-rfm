@extends('layouts.customers')

@section('title', 'Riwayat Transaksi')

@section('content')
<style>
    /* Filter Bar Styling */
    .search-input {
        border-radius: 12px;
        padding: 12px 20px 12px 45px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
        background-color: #fff;
    }
    .search-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        outline: none;
    }
    .search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        z-index: 5;
    }

    /* Transaction Item Styling */
    .trx-item {
        border-radius: 20px;
        background: #fff;
        padding: 24px;
        margin-bottom: 20px;
        border: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .trx-item:hover {
        transform: translateY(-4px);
        border-color: var(--primary);
        box-shadow: 0 12px 24px rgba(0,0,0,0.06);
    }

    .icon-shape {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .product-list-item {
        font-size: 12px;
        color: #475569;
        background: #f1f5f9;
        border-radius: 8px;
        padding: 4px 10px;
        margin-right: 5px;
        margin-bottom: 5px;
        display: inline-block;
        font-weight: 600;
    }

    /* Status Pill */
    .badge-status {
        font-weight: 700;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 6px 14px;
        border-radius: 10px;
        text-transform: uppercase;
    }

    /* Pagination Custom */
    .pagination .page-link {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-weight: 600;
        border: none;
        margin: 0 4px;
        border-radius: 10px !important;
    }
    .pagination .page-item.active .page-link {
        background: var(--primary-gradient);
        color: white;
    }
</style>

<div class="row">
    {{-- Header Section --}}
    <div class="col-12 mb-4 d-md-flex justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('customers.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active fw-bold text-primary">Riwayat Transaksi</li>
                </ol>
            </nav>
            <h3 class="fw-800 text-dark mb-0">Pesanan Saya</h3>
        </div>
        <div class="mt-3 mt-md-0">
            <button id="exportPdfBtn" class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-file-earmark-pdf me-2"></i> Ekspor Laporan
            </button>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="col-12 mb-4">
        <div class="card custom-card p-3 border-0 shadow-sm">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-6 position-relative">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search" class="form-control search-input" placeholder="Cari nomor invoice..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select search-input py-2">
                        <option value="">Semua Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="period" class="form-select search-input py-2">
                        <option value="30">30 Hari Terakhir</option>
                        <option value="90">3 Bulan Terakhir</option>
                        <option value="365">Tahun Ini</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- List Section --}}
    <div class="col-lg-10 mx-auto">
        @forelse($transactions as $trx)
        <div class="trx-item shadow-sm border-start border-4 {{ $trx->status == 'completed' ? 'border-success' : ($trx->status == 'cancelled' ? 'border-danger' : 'border-warning') }}">
            <div class="row align-items-center">
                <div class="col-auto d-none d-sm-block">
                    @php
                        $iconClass = [
                            'completed' => 'bg-success bg-opacity-10 text-success',
                            'pending' => 'bg-warning bg-opacity-10 text-warning',
                            'cancelled' => 'bg-danger bg-opacity-10 text-danger'
                        ][$trx->status] ?? 'bg-primary bg-opacity-10 text-primary';
                    @endphp
                    <div class="icon-shape {{ $iconClass }}">
                        <i class="bi {{ $trx->status == 'completed' ? 'bi-check2-circle' : 'bi-receipt-cutoff' }}"></i>
                    </div>
                </div>
                <div class="col">
                    {{-- Baris Info Utama --}}
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-800 text-dark mb-1">#{{ $trx->invoice_number }}</h6>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-calendar3 me-1"></i> {{ $trx->transaction_date->format('d M Y') }} 
                                <span class="mx-2 text-silver">|</span> 
                                <i class="bi bi-clock me-1"></i> {{ $trx->transaction_date->format('H:i') }} WIB
                            </p>
                        </div>
                        <div class="text-end">
                            <h5 class="fw-800 text-primary mb-1">Rp{{ number_format($trx->total_price) }}</h5>
                            @php
                                $badgeClass = [
                                    'completed' => 'bg-success-subtle text-success',
                                    'pending' => 'bg-warning-subtle text-warning',
                                    'cancelled' => 'bg-danger-subtle text-danger'
                                ][$trx->status] ?? 'bg-secondary-subtle text-secondary';
                            @endphp
                            <span class="badge-status {{ $badgeClass }}">{{ $trx->status }}</span>
                        </div>
                    </div>

                    {{-- Daftar Produk --}}
                    <div class="mt-3 mb-2">
                        <div class="product-summary">
                            @foreach($trx->details as $detail)
                                <span class="product-list-item">
                                    {{ $detail->product_detail->product->name }} 
                                    <span class="text-primary mx-1">x{{ $detail->quantity }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-3 opacity-25">

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-light text-dark border fw-600 rounded-pill px-3">
                                <i class="bi bi-cup-hot me-2 text-primary"></i>{{ $trx->details->count() }} Items
                            </span>
                            @if($trx->payment_method)
                                <span class="ms-3 small text-muted d-none d-md-inline">
                                    <i class="bi bi-credit-card me-1"></i> {{ strtoupper($trx->payment_method) }}
                                </span>
                            @endif
                        </div>
                        <a href="{{ route('customers.show.transactions', $trx->id) }}" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
                            Detail <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 card custom-card border-0 shadow-sm">
            <img src="https://illustrations.popsy.co/blue/stop-waiting.svg" style="width: 250px;" class="mb-4 mx-auto">
            <h4 class="fw-800">Belum Ada Transaksi</h4>
            <p class="text-muted px-4">Yuk mampir ke cafe dan nikmati menu favoritmu!</p>
            <div class="mt-3">
                <a href="{{ route('customers.dashboard') }}" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-primary">Pesan Sekarang</a>
            </div>
        </div>
        @endforelse

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-5">
            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- JS PDF Libraries --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
document.getElementById('exportPdfBtn').addEventListener('click', function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4'); // Landscape agar kolom produk muat

    // 1. Header Laporan
    doc.setFontSize(20);
    doc.setTextColor(30);
    doc.text('Laporan Riwayat Transaksi - CoffeeHub', 14, 20);
    
    doc.setFontSize(11);
    doc.setTextColor(100);
    doc.text('Nama Member: {{ auth()->guard("customers")->user()->name }}', 14, 28);
    doc.text('Tanggal Cetak: ' + new Date().toLocaleString('id-ID'), 14, 34);

    // 2. Scraping Data
    const rows = [];
    const items = document.querySelectorAll('.trx-item');
    
    items.forEach(item => {
        const invoice = item.querySelector('h6').innerText;
        const date = item.querySelector('.text-muted').innerText;
        const total = item.querySelector('h5').innerText;
        const status = item.querySelector('.badge-status').innerText;
        
        // Gabungkan list produk menjadi satu string
        const products = [];
        item.querySelectorAll('.product-list-item').forEach(p => {
            products.push(p.innerText.trim());
        });
        const productText = products.join(', ');
        
        rows.push([invoice, date, productText, total, status]);
    });

    // 3. Render Tabel
    doc.autoTable({
        startY: 42,
        head: [['Invoice', 'Waktu Transaksi', 'Item Produk', 'Total Harga', 'Status']],
        body: rows,
        theme: 'striped',
        headStyles: { 
            fillColor: [99, 102, 241],
            halign: 'center'
        },
        columnStyles: {
            2: { cellWidth: 100 }, // Kolom produk lebih lebar
            3: { halign: 'right' },
            4: { halign: 'center' }
        },
        styles: { font: 'helvetica', fontSize: 9, cellPadding: 4 },
    });

    // 4. Download
    doc.save('Riwayat_Transaksi_{{ date("Ymd_His") }}.pdf');
});
</script>
@endpush