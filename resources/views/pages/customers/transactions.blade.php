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
    }
    .search-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    .search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    /* Transaction Item Styling */
    .trx-item {
        border-radius: 20px;
        background: #fff;
        padding: 20px;
        margin-bottom: 16px;
        border: 1px solid rgba(0,0,0,0.03);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .trx-item:hover {
        transform: scale(1.01);
        border-color: var(--primary);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .icon-shape {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    /* Status Pill */
    .badge-status {
        font-weight: 700;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 6px 12px;
        border-radius: 8px;
        text-transform: uppercase;
    }
</style>

<div class="row">
    <div class="col-12 mb-4 d-md-flex justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active fw-bold text-primary">Transaksi</li>
                </ol>
            </nav>
            <h3 class="fw-800 text-dark mb-0">Riwayat Pesanan</h3>
        </div>
        <div class="mt-3 mt-md-0">
            <button class="btn btn-outline-dark rounded-pill px-4 fw-bold">
                <i class="bi bi-download me-2"></i> Ekspor PDF
            </button>
        </div>
    </div>

    <div class="col-12 mb-5">
        <div class="card custom-card p-3 border-0 shadow-sm">
            <div class="row g-3">
                <div class="col-md-6 position-relative">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="form-control search-input" placeholder="Cari nomor invoice atau menu...">
                </div>
                <div class="col-md-3">
                    <select class="form-select search-input py-2">
                        <option selected>Semua Status</option>
                        <option>Berhasil</option>
                        <option>Pending</option>
                    </ol>
                </div>
                <div class="col-md-3">
                    <select class="form-select search-input py-2">
                        <option selected>30 Hari Terakhir</option>
                        <option>3 Bulan Terakhir</option>
                        <option>Tahun Ini</option>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-10 mx-auto">
        @forelse($transactions as $trx)
        <div class="trx-item shadow-sm">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="icon-shape bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <h6 class="fw-800 text-dark mb-0">{{ $trx->invoice_number }}</h6>
                            <small class="text-muted">{{ $trx->created_at->format('d M Y • H:i') }} WIB</small>
                        </div>
                        <div class="text-end">
                            <h5 class="fw-800 text-dark mb-0">Rp{{ number_format($trx->total_price) }}</h5>
                            <span class="badge-status bg-success-subtle text-success">Berhasil</span>
                        </div>
                    </div>
                    <hr class="my-2 opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-box-seam text-muted me-2 small"></i>
                            <span class="small text-muted">{{ rand(1, 5) }} Items dipesan</span>
                        </div>
                        <a href="#" class="btn btn-link btn-sm text-decoration-none p-0 fw-bold">Detail <i class="bi bi-chevron-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5">
            <img src="https://illustrations.popsy.co/blue/stop-waiting.svg" style="width: 200px;" class="mb-4">
            <h5 class="fw-bold">Belum Ada Transaksi</h5>
            <p class="text-muted">Sepertinya kamu belum memesan kopi hari ini.</p>
            <a href="#" class="btn btn-primary rounded-pill px-5">Pesan Sekarang</a>
        </div>
        @endforelse

        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled"><a class="page-link border-0 rounded-circle mx-1" href="#"><i class="bi bi-chevron-left"></i></a></li>
                <li class="page-item active"><a class="page-link border-0 rounded-circle mx-1" href="#">1</a></li>
                <li class="page-item"><a class="page-link border-0 rounded-circle mx-1" href="#">2</a></li>
                <li class="page-item"><a class="page-link border-0 rounded-circle mx-1" href="#"><i class="bi bi-chevron-right"></i></a></li>
            </ul>
        </nav>
    </div>
</div>
@endsection