{{-- resources/views/pages/admin/rfm/batch-detail.blade.php --}}
@extends('layouts.admin')

@section('title', 'Detail Batch #' . $batch->id)

@push('styles')
<style>
    .step-card { 
        position: relative;
        border-left: 2px solid #e9ecef; 
        padding-left: 25px; 
        padding-bottom: 30px; 
    }
    .step-card:last-child { border-left-color: transparent; }
    .step-card::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 0;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #435ebe;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #435ebe;
    }
    .formula-box { 
        background: #f1f3f9; 
        border-radius: 10px;
        padding: 15px; 
        font-family: 'SFMono-Regular', Consolas, monospace; 
        font-size: 0.8rem; 
    }
    .formula-box dt { color: #25396f; font-weight: 700; }
    .centroid-coord { font-family: monospace; font-size: 0.75rem; color: #435ebe; }
    .bg-light-info { background-color: #e0f7fa; }
    .bg-light-secondary { background-color: #f8f9fa; }
</style>
@endpush

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Batch Detail #{{ $batch->id }}</h3>
                <p class="text-subtitle text-muted">Log teknis, normalisasi, dan hasil clustering.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('rfm.index') }}">RFM Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Batch Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        {{-- Header Status --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-light-primary me-3">
                        <i class="bi bi-cpu-fill text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Kalkulasi Batch #{{ $batch->id }}</h5>
                        <small class="text-muted">Eksekusi: {{ $batch->created_at->format('d M Y, H:i:s') }}</small>
                    </div>
                </div>
                <span class="badge {{ $batch->status === 'completed' ? 'bg-success' : 'bg-danger' }}">
                    {{ strtoupper($batch->status) }}
                </span>
            </div>
        </div>

        <div class="row">
            {{-- Panel Kiri: Log Pipeline --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header"><h4>Execution Pipeline</h4></div>
                    <div class="card-body mt-3">
                        @foreach($steps as $step)
                        <div class="step-card">
                            <h6 class="text-primary mb-1">Langkah {{ $step['step'] }}: {{ $step['title'] }}</h6>
                            <p class="text-muted small mb-2">{{ $step['description'] }}</p>

                            @if(!empty($step['formula']))
                            <div class="formula-box mb-3">
                                <dl class="row mb-0">
                                    @foreach($step['formula'] as $label => $val)
                                        <dt class="col-sm-4 small">{{ $label }}</dt>
                                        <dd class="col-sm-8 small mb-1">{{ $val }}</dd>
                                    @endforeach
                                </dl>
                            </div>
                            @endif

                            @if($step['step'] == 4 && !empty($step['iter_log']))
                                <div style="height: 150px"><canvas id="sseChart"></canvas></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Panel Kanan: Centroid & Labeling --}}
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white"><h5>Mapping Segmen</h5></div>
                    <div class="card-body pt-3">
                        <form method="POST" action="{{ route('rfm.batch.labels', $batch->id) }}">
                            @csrf @method('PATCH')
                            @foreach($clusterLabels as $ci => $label)
                            <div class="mb-2">
                                <label class="small fw-bold">Cluster {{ $ci }}</label>
                                <input type="text" name="labels[{{ $ci }}]" value="{{ $label }}" class="form-control form-control-sm">
                            </div>
                            @endforeach
                            <button class="btn btn-primary btn-sm w-100 mt-2">Update Label</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header border-bottom"><h5>Centroid (Pola)</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr class="small text-center">
                                    <th>Segmen</th><th>R</th><th>F</th><th>M</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($centroids as $ci => $c)
                                <tr class="text-center">
                                    <td class="small fw-bold text-start ps-2">{{ $clusterLabels[$ci] ?? $ci }}</td>
                                    <td class="centroid-coord">{{ round($c[0], 2) }}</td>
                                    <td class="centroid-coord">{{ round($c[1], 2) }}</td>
                                    <td class="centroid-coord">{{ round($c[2], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Audit: Mentah vs Normalisasi --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header"><h4>Audit Perhitungan: Mentah vs Normalisasi</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr class="text-center align-middle bg-light small">
                                        <th rowspan="2">Customer</th>
                                        <th colspan="3" class="bg-light-secondary">Data Mentah (Actual)</th>
                                        <th colspan="3" class="bg-light-info">Normalisasi (Input K-Means)</th>
                                        <th rowspan="2">Cluster</th>
                                    </tr>
                                    <tr class="text-center small">
                                        <th>Recency</th><th>Freq</th><th>Monetary</th>
                                        <th>R_Norm</th><th>F_Norm</th><th>M_Norm</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($scores as $score)
                                    <tr class="text-center align-middle small">
                                        <td class="text-start"><strong>{{ $score->customer->name }}</strong></td>
                                        {{-- Raw --}}
                                        <td>{{ $score->recency_days }}d</td>
                                        <td>{{ $score->frequency }}x</td>
                                        <td>Rp{{ number_format($score->monetary/1000, 0) }}k</td>
                                        {{-- Norm --}}
                                        <td class="text-info fw-bold">{{ number_format($score->recency_norm, 3) }}</td>
                                        <td class="text-info fw-bold">{{ number_format($score->frequency_norm, 3) }}</td>
                                        <td class="text-info fw-bold">{{ number_format($score->monetary_norm, 3) }}</td>
                                        
                                        <td><span class="badge bg-secondary">C-{{ $score->cluster_id }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
    document.addEventListener('DOMContentLoaded', function() {
        @php $sseLog = collect($steps)->firstWhere('step', 4)['iter_log'] ?? []; @endphp
        @if(count($sseLog) > 1)
        new Chart(document.getElementById('sseChart'), {
            type: 'line',
            data: {
                labels: @json(array_column($sseLog, 'iteration')),
                datasets: [{
                    label: 'SSE',
                    data: @json(array_column($sseLog, 'sse')),
                    borderColor: '#435ebe',
                    tension: 0.3,
                    fill: true,
                    backgroundColor: 'rgba(67, 94, 190, 0.1)'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
        @endif
    });
</script>
@endpush