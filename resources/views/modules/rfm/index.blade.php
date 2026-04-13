{{-- resources/views/pages/admin/rfm/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Analisis RFM')

@push('styles')
<style>
    .segment-badge {
        display: inline-flex; align-items: center;
        padding: 4px 12px; border-radius: 8px;
        font-size: 11px; font-weight: 700; white-space: nowrap;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    /* Mazer-friendly soft colors */
    .seg-champions  { background: #e0e7ff; color: #435ebe; }
    .seg-loyal      { background: #d1fae5; color: #065f46; }
    .seg-potential  { background: #fef3c7; color: #92400e; }
    .seg-at-risk    { background: #fee2e2; color: #991b1b; }
    .seg-needs      { background: #ffedd5; color: #9a3412; }
    .seg-lost       { background: #f3f4f6; color: #374151; }
    .seg-default    { background: #f1f3f9; color: #607080; }

    .stats-icon.purple { background-color: #435ebe; }
    .stats-icon.red { background-color: #ff7976; }
    .stats-icon.green { background-color: #5ddab4; }
</style>
@endpush

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Analisis RFM & Segmentasi</h3>
                <p class="text-subtitle text-muted">Segmentasi pelanggan menggunakan algoritma K-Means Clustering.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Analisis RFM</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        {{-- Toolbar --}}
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('rfm.calculate') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-cpu-fill me-2"></i> Hitung Ulang & Sinkronisasi
            </a>
        </div>

        @if(!$latestBatch)
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="avatar avatar-xl bg-light-primary mb-3">
                    <i class="bi bi-info-circle-fill text-primary" style="font-size: 2rem;"></i>
                </div>
                <h5>Belum ada data kalkulasi</h5>
                <p class="text-muted">Sistem memerlukan proses kalkulasi awal untuk menghasilkan segmen pelanggan.</p>
            </div>
        </div>
        @else

        {{-- Summary Cards --}}
        <div class="row">
            @foreach($segmentStats as $seg)
            <div class="col-6 col-lg-3 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body px-4 py-4-5">
                        <div class="row">
                            <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                <div class="stats-icon mb-2 {{ $loop->first ? 'purple' : ($loop->last ? 'red' : 'green') }}">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                            </div>
                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                <h6 class="text-muted font-semibold" style="font-size: 0.8rem;">{{ $seg->segment_name }}</h6>
                                <h6 class="font-extrabold mb-0">{{ number_format($seg->total) }}</h6>
                                <span class="badge bg-light-secondary mt-2" style="font-size: 0.6rem;">Cluster {{ $seg->cluster_id }}</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top" style="font-size: 0.75rem;">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Avg Score:</span>
                                <span class="fw-bold text-primary">{{ $seg->avg_rfm }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Avg Spend:</span>
                                <span class="fw-bold text-success">Rp {{ number_format($seg->avg_monetary / 1000, 0) }}k</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="row">
            {{-- Scatter Plot --}}
            <div class="col-12 col-xl-7">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Visualisasi Cluster</h4>
                        <div class="btn-group" id="axisToggle">
                            <button class="btn btn-sm btn-outline-primary active" data-x="recency_norm" data-y="frequency_norm">R vs F</button>
                            <button class="btn btn-sm btn-outline-primary" data-x="frequency_norm" data-y="monetary_norm">F vs M</button>
                            <button class="btn btn-sm btn-outline-primary" data-x="recency_norm" data-y="monetary_norm">R vs M</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="scatterChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Batch Info --}}
            <div class="col-12 col-xl-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h4>Batch Terakhir</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Waktu Eksekusi</span>
                                <span class="fw-bold">{{ $latestBatch->created_at->format('d M Y, H:i') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Total Data Pelanggan</span>
                                <span class="fw-bold">{{ $latestBatch->total_customers }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Iterasi K-Means</span>
                                <span class="badge bg-light-primary rounded-pill">{{ $latestBatch->actual_iterations }} / 100</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Inertia (SSE)</span>
                                <span class="text-muted">{{ number_format($latestBatch->inertia, 4) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Durasi Proses</span>
                                <span class="text-success fw-bold">{{ $latestBatch->duration_ms }} ms</span>
                            </li>
                        </ul>
                        <div class="alert alert-light-primary mt-4">
                            <i class="bi bi-shield-check me-2"></i>
                            Data ini digunakan untuk menentukan target promosi pada modul CRM.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card shadow-sm">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h4>Detail Skor Pelanggan</h4>
                <div class="card-header-action">
                    <form method="GET" class="d-flex gap-2">
                        <select name="segment" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Segmen</option>
                            @foreach($segmentStats as $seg)
                            <option value="{{ $seg->segment_name }}" {{ request('segment') == $seg->segment_name ? 'selected' : '' }}>
                                {{ $seg->segment_name }}
                            </option>
                            @endforeach
                        </select>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari..." value="{{ request('search') }}">
                        <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-lg">
                        <thead>
                            <tr>
                                <th>Pelanggan</th>
                                <th>Segmen</th>
                                <th class="text-center">R</th>
                                <th class="text-center">F</th>
                                <th class="text-center">M</th>
                                <th class="text-center">Total Score</th>
                                <th>Histori</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scores as $score)
                            <tr>
                                <td class="col-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md bg-light-primary me-3">
                                            <span class="avatar-content">{{ strtoupper(substr($score->customer->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-bold mb-0" style="font-size: 0.9rem;">{{ $score->customer->name }}</p>
                                            <p class="text-muted mb-0" style="font-size: 0.8rem;">{{ $score->customer->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $segClass = match(true) {
                                            str_contains($score->segment_name, 'Champion') => 'seg-champions',
                                            str_contains($score->segment_name, 'Loyal')    => 'seg-loyal',
                                            str_contains($score->segment_name, 'At Risk')  => 'seg-at-risk',
                                            str_contains($score->segment_name, 'Lost')     => 'seg-lost',
                                            default                                        => 'seg-default',
                                        };
                                    @endphp
                                    <span class="segment-badge {{ $segClass }}">{{ $score->segment_name }}</span>
                                </td>
                                <td class="text-center"><span class="badge bg-light-primary text-primary">{{ $score->r_score }}</span></td>
                                <td class="text-center"><span class="badge bg-light-success text-success">{{ $score->f_score }}</span></td>
                                <td class="text-center"><span class="badge bg-light-warning text-warning">{{ $score->m_score }}</span></td>
                                <td class="text-center fw-bold">{{ $score->rfm_score }}</td>
                                <td>
                                    <a href="{{ route('rfm.customer.history', $score->customer_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-4">
                    {{ $scores->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const COLORS = ['#435ebe', '#5ddab4', '#ff7976', '#57caeb', '#ffc107', '#6c757d', '#343a40', '#007bff'];

    let scatterChart;
    const scatterData = @json($scatterData);
    let currentX = 'recency_norm', currentY = 'frequency_norm';

    function buildDatasets(xKey, yKey) {
        const grouped = {};
        scatterData.forEach(d => {
            const key = d.segment_name;
            if (!grouped[key]) grouped[key] = { label: key, data: [], cluster_id: d.cluster_id };
            grouped[key].data.push({ x: d[xKey], y: d[yKey] });
        });
        return Object.values(grouped).map((g, i) => ({
            label: g.label,
            data: g.data,
            backgroundColor: COLORS[g.cluster_id % COLORS.length] + 'BB',
            pointRadius: 6,
            pointHoverRadius: 9,
        }));
    }

    function renderScatter(xKey, yKey) {
        const ctx = document.getElementById('scatterChart').getContext('2d');
        if (scatterChart) scatterChart.destroy();
        scatterChart = new Chart(ctx, {
            type: 'scatter',
            data: { datasets: buildDatasets(xKey, yKey) },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6, font: { size: 11, family: 'Nunito' } } }
                },
                scales: {
                    x: { grid: { display: false }, title: { display: true, text: 'Normalisasi (X)' } },
                    y: { grid: { color: '#f1f1f1' }, title: { display: true, text: 'Normalisasi (Y)' } }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('scatterChart')) {
            renderScatter(currentX, currentY);
            document.querySelectorAll('#axisToggle button').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#axisToggle button').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    renderScatter(this.dataset.x, this.dataset.y);
                });
            });
        }
    });
</script>
@endpush