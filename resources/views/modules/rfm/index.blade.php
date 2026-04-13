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
    
    .table-sm-custom td, .table-sm-custom th {
        padding: 0.4rem;
        font-size: 0.85rem;
    }
    .bg-light-blue { background-color: #f0f5ff; }
</style>
@endpush

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Analisis RFM & Segmentasi</h3>
                <p class="text-subtitle text-muted">Data mentah vs Normalisasi K-Means.</p>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('rfm.calculate') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-cpu-fill me-2"></i> Hitung Ulang & Sinkronisasi
            </a>
        </div>

        @if(!$latestBatch)
            <div class="card"><div class="card-body text-center py-5"><h5>Belum ada data kalkulasi</h5></div></div>
        @else

        {{-- Summary Cards --}}
        <div class="row">
            @foreach($segmentStats as $seg)
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon mb-2 {{ $loop->first ? 'purple' : 'green' }} me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <h6 class="text-muted font-semibold small mb-0">{{ $seg->segment_name }}</h6>
                                <h5 class="font-extrabold mb-0">{{ number_format($seg->total) }}</h5>
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
                    <div class="card-header d-flex justify-content-between">
                        <h4>Visualisasi Distribusi Cluster</h4>
                        <div class="btn-group" id="axisToggle">
                            <button class="btn btn-sm btn-outline-primary active" data-x="recency_norm" data-y="frequency_norm">R vs F</button>
                            <button class="btn btn-sm btn-outline-primary" data-x="frequency_norm" data-y="monetary_norm">F vs M</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 350px;"><canvas id="scatterChart"></canvas></div>
                    </div>
                </div>
            </div>

            {{-- Radar Chart: Pola DNA Cluster --}}
            <div class="col-12 col-xl-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h4>Pola Karakteristik (DNA)</h4></div>
                    <div class="card-body text-center">
                        <div style="height: 250px;"><canvas id="radarChart"></canvas></div>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm-custom table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Cluster</th>
                                        <th>R_Avg</th>
                                        <th>F_Avg</th>
                                        <th>M_Avg</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($latestBatch->final_centroids ?? [] as $idx => $center)
                                    <tr>
                                        <td><span class="badge bg-primary">C-{{ $idx }}</span></td>
                                        <td>{{ number_format($center[0], 2) }}</td>
                                        <td>{{ number_format($center[1], 2) }}</td>
                                        <td>{{ number_format($center[2], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Data Table --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header"><h4>Detail Perhitungan Pelanggan</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-lg">
                        <thead>
                            <tr class="text-center align-middle">
                                <th rowspan="2" class="text-start">Pelanggan</th>
                                <th rowspan="2">Segmen</th>
                                <th colspan="3" class="bg-light">Data Mentah (Actual)</th>
                                <th colspan="3" class="bg-light-blue">Normalisasi (Input)</th>
                                <th rowspan="2">Score</th>
                            </tr>
                            <tr class="text-center small">
                                <th class="bg-light">Recency</th><th class="bg-light">Freq</th><th class="bg-light">Monetary</th>
                                <th class="bg-light-blue">R_Norm</th><th class="bg-light-blue">F_Norm</th><th class="bg-light-blue">M_Norm</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scores as $score)
                            <tr class="align-middle">
                                <td>
                                    <span class="font-bold d-block" style="font-size: 0.9rem;">{{ $score->customer->name }}</span>
                                    <small class="text-muted">{{ $score->customer->email }}</small>
                                </td>
                                <td>
                                    @php
                                        $segClass = match(true) {
                                            str_contains($score->segment_name, 'Champion') => 'seg-champions',
                                            str_contains($score->segment_name, 'Loyal')    => 'seg-loyal',
                                            str_contains($score->segment_name, 'At Risk')  => 'seg-at-risk',
                                            default => 'seg-default',
                                        };
                                    @endphp
                                    <span class="segment-badge {{ $segClass }}">{{ $score->segment_name }}</span>
                                </td>
                                {{-- Raw Data --}}
                                <td class="text-center small">{{ $score->recency_days }}d</td>
                                <td class="text-center small">{{ $score->frequency }}x</td>
                                <td class="text-center small">Rp{{ number_format($score->monetary/1000) }}k</td>
                                {{-- Normalized Data --}}
                                <td class="text-center text-primary fw-bold small">{{ number_format($score->recency_norm, 2) }}</td>
                                <td class="text-center text-primary fw-bold small">{{ number_format($score->frequency_norm, 2) }}</td>
                                <td class="text-center text-primary fw-bold small">{{ number_format($score->monetary_norm, 2) }}</td>
                                
                                <td class="text-center fw-extrabold text-dark">{{ $score->rfm_score }}</td>
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
    const COLORS = ['#435ebe', '#5ddab4', '#ff7976', '#57caeb', '#ffc107', '#6c757d'];
    const scatterData = @json($scatterData);
    const centroidData = @json($latestBatch->final_centroids ?? []);
    const clusterLabels = @json($latestBatch->cluster_labels ?? []);

    // 1. Render Radar Chart (Patterns)
    if (centroidData.length > 0) {
        new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: ['Recency', 'Frequency', 'Monetary'],
                datasets: centroidData.map((center, i) => ({
                    label: clusterLabels[i] || `Cluster ${i}`,
                    data: center,
                    borderColor: COLORS[i % COLORS.length],
                    backgroundColor: COLORS[i % COLORS.length] + '22',
                    borderWidth: 2
                }))
            },
            options: { 
                responsive: true, maintainAspectRatio: false,
                scales: { r: { beginAtZero: true, max: 1, ticks: { display: false } } },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
            }
        });
    }

    // 2. Render Scatter Plot
    let scatterChart;
    function renderScatter(xKey, yKey) {
        const ctx = document.getElementById('scatterChart').getContext('2d');
        if (scatterChart) scatterChart.destroy();

        const grouped = {};
        scatterData.forEach(d => {
            if (!grouped[d.segment_name]) grouped[d.segment_name] = { label: d.segment_name, data: [], cid: d.cluster_id };
            grouped[d.segment_name].data.push({ x: d[xKey], y: d[yKey] });
        });

        scatterChart = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: Object.values(grouped).map(g => ({
                    label: g.label,
                    data: g.data,
                    backgroundColor: COLORS[g.cid % COLORS.length] + 'BB'
                }))
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    x: { title: { display: true, text: 'Nilai Normalisasi X' }, min: 0, max: 1 },
                    y: { title: { display: true, text: 'Nilai Normalisasi Y' }, min: 0, max: 1 }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderScatter('recency_norm', 'frequency_norm');
        document.querySelectorAll('#axisToggle button').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('#axisToggle button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                renderScatter(this.dataset.x, this.dataset.y);
            });
        });
    });
</script>
@endpush