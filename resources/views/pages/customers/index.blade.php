@extends('layouts.customers')

@section('title', 'Member Dashboard')

@section('content')
<style>
    .point-card {
        background: var(--primary-gradient);
        color: white;
        position: relative;
        overflow: hidden;
    }
    .point-card::before {
        content: '';
        position: absolute;
        width: 200px; height: 200px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        top: -100px; right: -50px;
    }
    .icon-box {
        width: 54px; height: 54px;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
    }
    .status-badge {
        padding: 6px 14px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
    }
    .table-modern tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: 0.2s;
    }
    .table-modern tbody tr:hover {
        background-color: #f8fafc;
    }
</style>

<div class="row g-4">
    <div class="col-12 mb-2">
        <h2 class="fw-800 text-dark">Halo, {{ explode(' ', auth()->guard('customers')->user()->name)[0] }}! 👋</h2>
        <p class="text-muted">Ini ringkasan aktivitas minum kopimu bulan ini.</p>
    </div>

    <div class="col-lg-5">
        <div class="card custom-card point-card p-4 h-100 border-0 shadow-lg">
            <div class="d-flex justify-content-between">
                <div>
                    <span class="badge bg-white bg-opacity-20 rounded-pill px-3 py-2 mb-3">Loyalty Points</span>
                    <h1 class="display-3 fw-800 mb-0">{{ number_format(auth()->guard('customers')->user()->total_points) }}</h1>
                    <p class="opacity-75 fw-500 mt-2">Kumpulkan {{ 1000 - (auth()->guard('customers')->user()->total_points % 1000) }} poin lagi untuk kopi gratis!</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-4 align-self-start">
                    <i class="bi bi-stars fs-2"></i>
                </div>
            </div>
            <div class="mt-auto pt-4">
                <div class="progress" style="height: 10px; background: rgba(255,255,255,0.2); border-radius: 20px;">
                    <div class="progress-bar bg-white" style="width: 70%; border-radius: 20px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="row g-4">
            <div class="col-6">
                <div class="card custom-card p-4 h-100">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                    <p class="text-muted small fw-bold mb-1">TOTAL BELANJA</p>
                    <h4 class="fw-800 mb-0 text-dark">Rp{{ number_format(auth()->guard('customers')->user()->total_spend) }}</h4>
                </div>
            </div>
            <div class="col-6">
                <div class="card custom-card p-4 h-100">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning mb-3">
                        <i class="bi bi-cup-hot fs-4"></i>
                    </div>
                    <p class="text-muted small fw-bold mb-1">TERAKHIR NGOPI</p>
                    <h4 class="fw-800 mb-0 text-dark">
                        {{ auth()->guard('customers')->user()->last_purchase_at ? auth()->guard('customers')->user()->last_purchase_at->diffForHumans() : '-' }}
                    </h4>
                </div>
            </div>
            <div class="col-12">
                <div class="card custom-card p-4 d-flex flex-row align-items-center justify-content-between bg-light border-0">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>
                        <p class="mb-0 small fw-600">Ada penawaran spesial "Monday Coffee" buat kamu!</p>
                    </div>
                    <a href="#" class="btn btn-primary btn-sm rounded-pill px-4">Cek Promo</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-800 mb-0 text-dark">Transaksi Terakhir</h5>
            <a href="#" class="btn btn-sm btn-light rounded-pill px-3 fw-bold border">Lihat Semua <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        
        <div class="card custom-card shadow-sm border-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 border-0 text-muted small fw-bold">ID INVOICE</th>
                            <th class="py-3 border-0 text-muted small fw-bold">TANGGAL</th>
                            <th class="py-3 border-0 text-muted small fw-bold">TOTAL</th>
                            <th class="px-4 py-3 border-0 text-muted small fw-bold text-center">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                        <tr>
                            <td class="px-4 py-4 fw-800 text-primary">{{ $trx->invoice_number }}</td>
                            <td class="text-muted">
                                {{ $trx->created_at->format('d M Y') }}<br>
                                <small class="opacity-50">{{ $trx->created_at->format('H:i') }}</small>
                            </td>
                            <td class="fw-bold text-dark">Rp{{ number_format($trx->total_price) }}</td>
                            <td class="px-4 text-center">
                                <span class="status-badge bg-success-subtle text-success">SUCCESS</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <img src="https://illustrations.popsy.co/blue/coffee-break.svg" style="width: 150px;" class="mb-4 opacity-50">
                                <p class="text-muted">Belum ada transaksi. Yuk mampir ke cafe!</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection