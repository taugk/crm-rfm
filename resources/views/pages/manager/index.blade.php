@extends('layouts.manager')

@section('title', 'Dashboard Manager')

@section('content')

<div class="page-heading">
    <h3>Dashboard Manager</h3>
</div>

<div class="page-content">

    <!-- Welcome -->
    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5>Halo, {{ auth()->user()->nama ?? auth()->user()->name }}</h5>
                    <p class="text-muted">
                        Ringkasan performa bisnis & analisis pelanggan
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- KPI -->
    <section class="row">

        <!-- Revenue -->
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Revenue</h6>
                    <h4>Rp {{ number_format($totalPendapatan ?? 0, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <!-- Transaksi -->
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Transaksi</h6>
                    <h4>{{ $totalTransaksi ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <!-- Pelanggan Aktif -->
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Pelanggan Aktif</h6>
                    <h4>{{ $pelangganAktif ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <!-- Avg Order -->
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Avg Order Value</h6>
                    <h4>
                        Rp {{ number_format($avgOrder ?? 0, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>

    </section>

    <!-- Grafik -->
    <section class="row">

        <!-- Grafik Revenue -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Trend Revenue</h4>
                </div>
                <div class="card-body">
                    <canvas id="chart-revenue"></canvas>
                </div>
            </div>
        </div>

        <!-- RFM Insight -->
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Customer Insight (RFM)</h4>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <strong>Champions</strong>
                        <div class="progress">
                            <div class="progress-bar bg-success"
                                style="width: {{ $rfm['champions'] ?? 0 }}%">
                                {{ $rfm['champions'] ?? 0 }}%
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Loyal</strong>
                        <div class="progress">
                            <div class="progress-bar bg-primary"
                                style="width: {{ $rfm['loyal'] ?? 0 }}%">
                                {{ $rfm['loyal'] ?? 0 }}%
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>At Risk</strong>
                        <div class="progress">
                            <div class="progress-bar bg-danger"
                                style="width: {{ $rfm['at_risk'] ?? 0 }}%">
                                {{ $rfm['at_risk'] ?? 0 }}%
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </section>

    <!-- Top Produk -->
    <section class="row">

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>Top Produk</h4>
                </div>
                <div class="card-body">

                    <ul class="list-group">
                        @forelse($topProduk ?? [] as $produk)
                            <li class="list-group-item d-flex justify-content-between">
                                {{ $produk->nama }}
                                <span class="badge bg-primary">
                                    {{ $produk->total_terjual }}
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

        <!-- Recent Transaksi -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>Transaksi Terbaru</h4>
                </div>
                <div class="card-body">

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransaksi ?? [] as $trx)
                                <tr>
                                    <td>{{ $trx->tanggal }}</td>
                                    <td>{{ $trx->pelanggan }}</td>
                                    <td>
                                        Rp {{ number_format($trx->total, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">
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
    const ctx = document.getElementById('chart-revenue');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labels ?? []) !!},
            datasets: [{
                label: 'Revenue',
                data: {!! json_encode($dataRevenue ?? []) !!},
                borderWidth: 2,
                fill: false
            }]
        }
    });
</script>
@endsection