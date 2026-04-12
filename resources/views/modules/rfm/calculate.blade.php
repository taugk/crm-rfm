{{-- resources/views/pages/admin/rfm/calculate.blade.php --}}
@extends('layouts.admin')

@section('title', 'Konfigurasi Kalkulasi RFM')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Konfigurasi Kalkulasi RFM</h3>
                <p class="text-subtitle text-muted">Atur parameter clustering dan rentang waktu data transaksi.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('rfm.index') }}">RFM Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Hitung Ulang</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4>Parameter Analisis</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('rfm.store') }}" id="calcForm">
                            @csrf

                            <div class="form-group mb-4">
                                <label class="form-label fw-bold">Jumlah Cluster (K)</label>
                                <p class="text-muted small">Tentukan berapa banyak kelompok pelanggan yang ingin dibentuk.</p>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" id="kRange" name="k_clusters" min="2" max="10"
                                        value="{{ old('k_clusters', $lastBatch?->k_clusters ?? 5) }}"
                                        class="form-range flex-grow-1" 
                                        oninput="document.getElementById('kVal').textContent = this.value">
                                    <span class="badge bg-primary fs-5" id="kVal" style="min-width:45px;">
                                        {{ old('k_clusters', $lastBatch?->k_clusters ?? 5) }}
                                    </span>
                                </div>
                                @error('k_clusters')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>

                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-bold">Dari Tanggal</label>
                                        <input type="date" name="date_from" class="form-control"
                                            value="{{ old('date_from', now()->subYears(2)->toDateString()) }}">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-bold">Sampai Tanggal</label>
                                        <input type="date" name="date_to" class="form-control"
                                            value="{{ old('date_to', now()->toDateString()) }}">
                                    </div>
                                </div>
                            </div>

                            @if($lastBatch)
                            <div class="alert alert-light-secondary border-0 small py-2 px-3 mb-4">
                                <i class="bi bi-clock-history me-2"></i>
                                Kalkulasi terakhir: <strong>{{ $lastBatch->created_at->format('d M Y') }}</strong>
                            </div>
                            @endif

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="bi bi-play-circle-fill me-2"></i> Jalankan Algoritma
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Elbow Method Optimizer</h4>
                        <button class="btn btn-sm btn-outline-primary" id="elbowBtn">
                            <i class="bi bi-graph-up me-1"></i> Analisis SSE
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light-primary border-0 small mb-4">
                            <i class="bi bi-lightbulb-fill me-2"></i>
                            <strong>Tips:</strong> Pilih nilai K pada titik "siku" grafik (di mana penurunan SSE mulai mendatar). Klik pada titik grafik untuk memilih nilai K secara otomatis.
                        </div>
                        
                        <div style="height: 300px;">
                            <canvas id="elbowChart"></canvas>
                        </div>

                        <div id="elbowLoader" class="text-center d-none py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Sedang mensimulasikan clustering K=2 hingga K=8...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    let elbowChart;
    const elbowBtn = document.getElementById('elbowBtn');
    const elbowLoader = document.getElementById('elbowLoader');
    const chartCanvas = document.getElementById('elbowChart');

    elbowBtn.addEventListener('click', async function() {
        this.disabled = true;
        chartCanvas.classList.add('d-none');
        elbowLoader.classList.remove('d-none');

        try {
            const res = await fetch('{{ route("rfm.elbow") }}?max_k=8');
            const data = await res.json();
            const ctx = chartCanvas.getContext('2d');
            
            if (elbowChart) elbowChart.destroy();
            
            elbowChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => 'K=' + d.k),
                    datasets: [{
                        label: 'SSE (Inertia)',
                        data: data.map(d => d.sse),
                        borderColor: '#435ebe',
                        backgroundColor: 'rgba(67, 94, 190, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: '#435ebe',
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `Inertia: ${ctx.parsed.y.toLocaleString()}`
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: false,
                            grid: { color: '#f1f1f1' },
                            title: { display: true, text: 'Within-Cluster Sum of Squares' }
                        },
                        x: { grid: { display: false } }
                    },
                    onClick(e, els) {
                        if (els.length) {
                            const k = data[els[0].index].k;
                            document.getElementById('kRange').value = k;
                            document.getElementById('kVal').textContent = k;
                            
                            // Visual feedback
                            const kValBadge = document.getElementById('kVal');
                            kValBadge.classList.add('animate__animated', 'animate__pulse');
                            setTimeout(() => kValBadge.classList.remove('animate__animated', 'animate__pulse'), 500);
                        }
                    }
                }
            });

            chartCanvas.classList.remove('d-none');
        } catch(e) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Pastikan sudah ada batch kalkulasi yang sukses sebelumnya untuk melakukan analisis Elbow.'
            });
        } finally {
            elbowLoader.classList.add('d-none');
            this.disabled = false;
        }
    });

    document.getElementById('calcForm').addEventListener('submit', function() {
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses Data...';
    });
</script>
@endpush