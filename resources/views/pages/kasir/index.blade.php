@extends('layouts.kasir')

@section('title', 'Dashboard Kasir')

@section('content')
<div class="page-content">
    <section class="row">
        <div class="col-12">
            <!-- Welcome Message -->
            <div class="card bg-primary mb-4 border-0 shadow-sm" style="background: linear-gradient(90deg, #E8531A 0%, #ff7844 100%) !important;">
                <div class="card-body px-4 py-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-xl bg-white-50 p-1 me-3">
                            <span class="avatar-content fs-2">👋</span>
                        </div>
                        <div class="ms-1">
                            <h4 class="text-white mb-1">Selamat Datang, {{ auth()->user()->name }}!</h4>
                            <p class="text-white-50 mb-0">Kelola transaksi penjualan BrewCRM dengan mudah hari ini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stat Cards KPI -->
        <div class="col-12 col-lg-12">
            <div class="row">
                <div class="col-6 col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-5 d-flex justify-content-start ">
                                    <div class="stats-icon blue mb-2">
                                        <i class="iconly-boldDocument"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-7">
                                    <h6 class="text-muted font-semibold">Transaksi</h6>
                                    <h6 class="font-extrabold mb-0">{{ $transaksiHariIni ?? 0 }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-5 d-flex justify-content-start ">
                                    <div class="stats-icon green mb-2">
                                        <i class="iconly-boldWallet"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-7">
                                    <h6 class="text-muted font-semibold">Pendapatan</h6>
                                    <h6 class="font-extrabold mb-0">Rp{{ number_format($pendapatanHariIni ?? 0, 0, ',', '.') }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-5 d-flex justify-content-start ">
                                    <div class="stats-icon orange mb-2" style="background-color: #ff9f43;">
                                        <i class="iconly-boldBag"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-7">
                                    <h6 class="text-muted font-semibold">Produk</h6>
                                    <h6 class="font-extrabold mb-0">{{ $produkTerjual ?? 0 }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-5 d-flex justify-content-start ">
                                    <div class="stats-icon purple mb-2">
                                        <i class="iconly-boldUser"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-7">
                                    <h6 class="text-muted font-semibold">Pelanggan</h6>
                                    <h6 class="font-extrabold mb-0">{{ $pelangganHariIni ?? 0 }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aksi Cepat -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Aksi Cepat</h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <a href="{{ route('kasir.pos.index') }}" class="btn btn-light-primary w-100 py-3 border-dashed">
                                        <i class="bi bi-cart-plus-fill fs-3 d-block mb-2"></i>
                                        Transaksi Baru
                                    </a>
                                </div>
                                <div class="col-6 col-md-3">
                                    <a href="{{ route('kasir.members.create') }}" class="btn btn-light-success w-100 py-3 border-dashed">
                                        <i class="bi bi-person-plus-fill fs-3 d-block mb-2"></i>
                                        Tambah Member
                                    </a>
                                </div>
                                <div class="col-6 col-md-3">
                                    <a href="{{ route('kasir.transactions.history') }}" class="btn btn-light-info w-100 py-3 border-dashed">
                                        <i class="bi bi-clock-history fs-3 d-block mb-2"></i>
                                        Riwayat Jual
                                    </a>
                                </div>
                                <div class="col-6 col-md-3">
                                    <a href="{{ route('kasir.members.check') }}" class="btn btn-light-warning w-100 py-3 border-dashed">
                                        <i class="bi bi-search fs-3 d-block mb-2"></i>
                                        Cek Member
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Grafik -->
                <div class="col-12 col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Trend Transaksi (7 Hari Terakhir)</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="chart-transaksi" style="width: 100%; max-height: 350px;"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Produk Terlaris -->
                <div class="col-12 col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Produk Terlaris</h4>
                        </div>
                        <div class="card-body">
                            @forelse($topProduk ?? [] as $produk)
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar avatar-md bg-light-primary me-3">
                                    <span class="avatar-content"><i class="bi bi-cup-hot text-primary"></i></span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $produk->name }}</h6>
                                    <p class="text-muted mb-0 text-sm">BrewCRM Category</p>
                                </div>
                                <div>
                                    <span class="badge bg-light-primary text-primary">{{ $produk->total }} Terjual</span>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-5">
                                <p class="text-muted">Data belum tersedia</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaksi Terbaru Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Transaksi Terbaru</h4>
                            <a href="{{ route('kasir.transactions.history') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-lg">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Tanggal</th>
                                            <th>Pelanggan</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentTransaksi ?? [] as $trx)
                                        <tr>
                                            <td class="col-3">
                                                <div class="d-flex align-items-center">
                                                    <p class="font-bold mb-0">#{{ $trx->invoice_number }}</p>
                                                </div>
                                            </td>
                                            <td class="col-auto">
                                                <p class=" mb-0 text-sm">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('d M Y, H:i') }}</p>
                                            </td>
                                            <td class="col-auto">
                                                <p class=" mb-0">{{ $trx->customer->name ?? 'Guest' }}</p>
                                            </td>
                                            <td class="col-auto text-end">
                                                <p class="font-bold mb-0 text-primary">Rp{{ number_format($trx->total_price ?? 0, 0, ',', '.') }}</p>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5">Belum ada transaksi hari ini.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('chart-transaksi');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($labels ?? []) !!},
                    datasets: [{
                        label: 'Jumlah Transaksi',
                        data: {!! json_encode($dataTransaksi ?? []) !!},
                        borderColor: '#E8531A',
                        backgroundColor: 'rgba(232, 83, 26, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#E8531A'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f5f5f5' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });
</script>
@endsection