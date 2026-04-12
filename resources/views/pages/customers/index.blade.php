@extends('layouts.customers')

@section('title', 'Dashboard Member')

@section('content')
<div class="row g-4">
    <div class="col-12 mb-2">
        <h4 class="fw-bold mb-0">Selamat Datang, {{ auth()->guard('customers')->user()->name }}! 👋</h4>
        <p class="text-muted small">Cek poin dan riwayat ngopi kamu di sini.</p>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm overflow-hidden" 
             style="background: linear-gradient(135deg, #435ebe 0%, #6c5ce7 100%); color: white; border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75 small fw-bold text-uppercase" style="letter-spacing: 1px;">Total Poin Kamu</p>
                        <h1 class="display-5 fw-bold mb-0">{{ number_format(auth()->guard('customers')->user()->total_points) }}</h1>
                        <p class="mt-2 mb-0 small opacity-75">Tukarkan dengan segelas kopi gratis!</p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-3 rounded-circle">
                        <i class="bi bi-star-fill fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="row g-3">
            <div class="col-6">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 15px;">
                    <div class="text-primary mb-2"><i class="bi bi-wallet2 fs-3"></i></div>
                    <div class="text-muted small fw-bold">Total Belanja</div>
                    <div class="h5 fw-bold mb-0">Rp {{ number_format(auth()->guard('customers')->user()->total_spend) }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 15px;">
                    <div class="text-warning mb-2"><i class="bi bi-calendar-check fs-3"></i></div>
                    <div class="text-muted small fw-bold">Kunjungan Terakhir</div>
                    <div class="h5 fw-bold mb-0">
                        {{ auth()->guard('customers')->user()->last_purchase_at ? auth()->guard('customers')->user()->last_purchase_at->diffForHumans() : 'Belum ada' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Riwayat Transaksi Terakhir</h5>
            <a href="{{ route('customers.transactions') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
        </div>
        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 px-4 py-3 small text-muted">INVOICE</th>
                                <th class="border-0 py-3 small text-muted">TANGGAL</th>
                                <th class="border-0 py-3 small text-muted">TOTAL</th>
                                <th class="border-0 px-4 py-3 small text-muted text-center">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Gunakan @forelse untuk handle data kosong --}}
                            @forelse($transactions as $trx)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="fw-bold text-primary">{{ $trx->invoice_number }}</span>
                                </td>
                                <td class="text-muted small">
                                    {{ $trx->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="fw-bold">
                                    Rp {{ number_format($trx->total_price) }}
                                </td>
                                <td class="px-4 text-center">
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">Selesai</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <img src="https://illustrations.popsy.co/blue/shopping-cart.svg" style="width: 150px;" class="mb-3">
                                    <p class="text-muted small">Belum ada riwayat ngopi. Yuk ke cafe!</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection