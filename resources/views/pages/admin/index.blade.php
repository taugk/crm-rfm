@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')

<div class="page-heading">
    <h3>Dashboard Admin</h3>
</div>

<div class="page-content">

    <!-- Welcome -->
    <section class="row">
        <div class="col-12 col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5>Selamat Datang, {{ auth()->user()->nama ?? auth()->user()->name }}</h5>
                    <p class="text-subtitle text-muted">
                        Sistem CRM & Analisis RFM
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistik -->
    <section class="row">

        <!-- Total Pelanggan -->
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon purple me-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h6 class="text-muted">Pelanggan</h6>
                            <h6 class="font-extrabold mb-0">{{ $totalPelanggan ?? 0 }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Produk -->
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon blue me-3">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div>
                            <h6 class="text-muted">Produk</h6>
                            <h6 class="font-extrabold mb-0">{{ $totalProduk ?? 0 }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon green me-3">
                            <i class="bi bi-cart-check-fill"></i>
                        </div>
                        <div>
                            <h6 class="text-muted">Transaksi</h6>
                            <h6 class="font-extrabold mb-0">{{ $totalTransaksi ?? 0 }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pendapatan -->
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon red me-3">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <h6 class="text-muted">Pendapatan</h6>
                            <h6 class="font-extrabold mb-0">
                                Rp {{ number_format($totalPendapatan ?? 0, 0, ',', '.') }}
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>

    <!-- Chart / Grafik -->
    <section class="row">

        <!-- Grafik Penjualan -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Grafik Penjualan</h4>
                </div>
                <div class="card-body">
                    <canvas id="chart-penjualan"></canvas>
                </div>
            </div>
        </div>

        <!-- Segmentasi RFM -->
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Segmentasi RFM</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group">

                        <li class="list-group-item d-flex justify-content-between">
                            Champions
                            <span class="badge bg-success">{{ $rfm['champions'] ?? 0 }}</span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between">
                            Loyal
                            <span class="badge bg-primary">{{ $rfm['loyal'] ?? 0 }}</span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between">
                            At Risk
                            <span class="badge bg-danger">{{ $rfm['at_risk'] ?? 0 }}</span>
                        </li>

                    </ul>
                </div>
            </div>
        </div>

    </section>

</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('chart-penjualan');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labels ?? []) !!},
            datasets: [{
                label: 'Penjualan',
                data: {!! json_encode($dataPenjualan ?? []) !!},
                borderWidth: 2,
                fill: false
            }]
        }
    });
</script>
@endsection