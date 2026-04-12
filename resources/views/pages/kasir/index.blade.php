@extends('layouts.kasir')

@section('title', 'Dashboard Kasir')

@section('content')

<div class="page-heading">
    <h3>Dashboard Kasir</h3>
</div>

<div class="page-content">

    <!-- Welcome -->
    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5>Halo, {{ auth()->user()->name }}</h5>
                    <p class="text-muted">
                        Kelola transaksi penjualan hari ini
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- KPI -->
    <section class="row">

        <!-- Transaksi Hari Ini -->
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Transaksi Hari Ini</h6>
                    <h4>{{ $transaksiHariIni }}</h4>
                </div>
            </div>
        </div>

        <!-- Pendapatan Hari Ini -->
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Pendapatan Hari Ini</h6>
                    <h4>Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <!-- Produk Terjual -->
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Produk Terjual</h6>
                    <h4>{{ $produkTerjual }}</h4>
                </div>
            </div>
        </div>

        <!-- Pelanggan Hari Ini -->
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Pelanggan Hari Ini</h6>
                    <h4>{{ $pelangganHariIni }}</h4>
                </div>
            </div>
        </div>

    </section>

    <!-- Aksi Cepat -->
    <section class="row">
        <div class="col-lg-4">
            <a href="{{ route('kasir.create.trasaction') }}" class="card text-decoration-none">
                <div class="card-body text-center">
                    <h5>+ Input Transaksi</h5>
                </div>
            </a>
        </div>

        <div class="col-lg-4">
            <a href="#" class="card text-decoration-none">
                <div class="card-body text-center">
                    <h5>+ Tambah Pelanggan</h5>
                </div>
            </a>
        </div>

        <div class="col-lg-4">
            <a href="" class="card text-decoration-none">
                <div class="card-body text-center">
                    <h5>Riwayat Transaksi</h5>
                </div>
            </a>
        </div>
    </section>

    <!-- Grafik -->
    <section class="row">

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Trend Transaksi</h4>
                </div>
                <div class="card-body">
                    <canvas id="chart-transaksi"></canvas>
                </div>
            </div>
        </div>

        <!-- Produk Terlaris -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Produk Terlaris</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @forelse($topProduk as $produk)
                            <li class="list-group-item d-flex justify-content-between">
                                {{ $produk->name }}
                                <span class="badge bg-primary">
                                    {{ $produk->total }}
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item text-center">
                                Tidak ada data
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

    </section>

    <!-- Transaksi Terbaru -->
    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Transaksi Terbaru</h4>
                </div>
                <div class="card-body">

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransaksi as $trx)
                                <tr>
                                    <td>{{ $trx->invoice_number }}</td>
                                    <td>{{ $trx->transaction_date }}</td>
                                    <td>{{ $trx->customer->name }}</td>
                                    <td>Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        Tidak ada data
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </section>

</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('chart-transaksi');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labels) !!},
            datasets: [{
                label: 'Transaksi',
                data: {!! json_encode($dataTransaksi) !!},
                borderWidth: 2,
                fill: false
            }]
        }
    });
</script>
@endsection