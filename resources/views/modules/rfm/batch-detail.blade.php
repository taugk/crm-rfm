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
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; 
        font-size: 0.8rem; 
    }
    .formula-box dt { color: #25396f; font-weight: 700; }
    .formula-box dd { margin-bottom: 0.5rem; color: #4b4b4b; }
    .centroid-coord { font-family: monospace; font-size: 0.75rem; color: #435ebe; }
</style>
@endpush

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Batch Detail #{{ $batch->id }}</h3>
                <p class="text-subtitle text-muted">Log teknis dan tahapan kalkulasi algoritma RFM.</p>
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
        {{-- Status Header --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-light-primary me-3">
                        <i class="bi bi-cpu-fill text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Batch Kalkulasi #{{ $batch->id }}</h5>
                        <small class="text-muted">
                            Triggered by <strong>{{ $batch->triggeredBy->name }}</strong> 
                            &bull; {{ $batch->created_at->format('d M Y, H:i:s') }}
                        </small>
                    </div>
                </div>
                <div>
                    <span class="badge {{ $batch->status === 'completed' ? 'bg-light-success' : 'bg-light-danger' }} p-2">
                        <i class="bi bi-circle-fill me-1" style="font-size: 8px"></i> {{ strtoupper($batch->status) }}
                    </span>
                </div>
            </div>
        </div>

        @if($batch->status === 'failed')
        <div class="alert alert-light-danger color-danger"><i class="bi bi-exclamation-circle"></i> 
            {{ $batch->error_message }}
        </div>
        @endif

        <div class="row">
            {{-- Left Panel: Steps timeline --}}
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h4>Pipeline Execution Log</h4>
                    </div>
                    <div class="card-body mt-3">
                        @foreach($steps as $step)
                        <div class="step-card">
                            <h6 class="text-primary mb-1">Langkah {{ $step['step'] }}: {{ $step['title'] }}</h6>
                            <p class="text-muted small mb-3">{{ $step['description'] }}</p>

                            {{-- Formula Box --}}
                            @if(!empty($step['formula']))
                            <div class="formula-box mb-3">
                                <dl class="row mb-0">
                                    @foreach($step['formula'] as $label => $val)
                                    <dt class="col-sm-3">{{ $label }}</dt>
                                    <dd class="col-sm-9">{{ $val }}</dd>
                                    @endforeach
                                </dl>
                            </div>
                            @endif

                            {{-- Step Specific Data --}}
                            @if($step['step'] == 1 && !empty($step['stats']))
                            <div class="row g-2">
                                @foreach(['Pelanggan' => 'count', 'Avg Recency' => 'avg_recency_days', 'Avg Freq' => 'avg_frequency'] as $label => $key)
                                <div class="col-md-4">
                                    <div class="p-2 border rounded text-center bg-light">
                                        <div class="small text-muted">{{ $label }}</div>
                                        <div class="fw-bold">{{ number_format($step['stats'][$key] ?? 0, ($key == 'count' ? 0 : 1)) }}</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- Iteration Log for KMeans --}}
                            @if($step['step'] == 4 && !empty($step['iter_log']))
                            <div class="mt-3">
                                <h6 class="small fw-bold">Grafik Konvergensi SSE</h6>
                                <div style="height: 150px">
                                    <canvas id="sseChart"></canvas>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right Panel --}}
            <div class="col-lg-4">
                {{-- Edit Label Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary py-3">
                        <h5 class="text-white mb-0">Mapping Segmen</h5>
                    </div>
                    <div class="card-body pt-4">
                        <form method="POST" action="{{ route('rfm.batch.labels', $batch->id) }}">
                            @csrf @method('PATCH')
                            @foreach($clusterLabels as $ci => $label)
                            <div class="form-group mb-3">
                                <label class="small fw-bold">Cluster {{ $ci }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-primary">#{{ $ci }}</span>
                                    <input type="text" name="labels[{{ $ci }}]" value="{{ $label }}" 
                                           class="form-control" required>
                                </div>
                            </div>
                            @endforeach
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Update Nama Segmen</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Centroid Card --}}
                <div class="card shadow-sm border-0">
                    <div class="card-header py-3 border-bottom">
                        <h5 class="mb-0">Centroid Akhir</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr class="small">
                                        <th class="ps-3">Segmen</th>
                                        <th class="text-center">R</th>
                                        <th class="text-center">F</th>
                                        <th class="text-center">M</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($centroids as $ci => $c)
                                    <tr>
                                        <td class="ps-3 small fw-bold">{{ $clusterLabels[(string)$ci] ?? 'Cluster '.$ci }}</td>
                                        <td class="text-center centroid-coord">{{ round($c[0], 3) }}</td>
                                        <td class="text-center centroid-coord">{{ round($c[1], 3) }}</td>
                                        <td class="text-center centroid-coord">{{ round($c[2], 3) }}</td>
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
        @php
            $sseLog = collect($steps)->firstWhere('step', 4)['iter_log'] ?? [];
        @endphp
        
        @if(count($sseLog) > 1)
        const sseCtx = document.getElementById('sseChart');
        new Chart(sseCtx, {
            type: 'line',
            data: {
                labels: @json(array_column($sseLog, 'iteration')),
                datasets: [{
                    label: 'Sum of Squared Errors',
                    data: @json(array_column($sseLog, 'sse')),
                    borderColor: '#435ebe',
                    backgroundColor: 'rgba(67, 94, 190, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#435ebe'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { ticks: { font: { size: 10 } } }
                }
            }
        });
        @endif
    });
</script>
@endpush