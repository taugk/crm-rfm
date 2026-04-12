{{-- resources/views/pages/admin/rfm/customer-history.blade.php --}}
@extends('layouts.admin')

@section('title', 'Histori Segmen - ' . $customer->name)

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Histori Segmen Pelanggan</h3>
                <p class="text-subtitle text-muted">Melacak perjalanan loyalitas pelanggan dari waktu ke waktu.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('rfm.index') }}">RFM Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Customer History</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        {{-- Profile Header Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body py-4">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-xl bg-primary me-4">
                        <span class="avatar-content">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <h4 class="mb-1 text-primary">{{ $customer->name }}</h4>
                        <p class="text-muted mb-0"><i class="bi bi-envelope me-2"></i>{{ $customer->email }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Timeline Perubahan Segmen</h4>
                <div class="badge bg-light-primary text-primary">
                    Total {{ $history->total() }} Kalkulasi
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-lg">
                        <thead>
                            <tr class="text-uppercase small tracking-wider">
                                <th>Tanggal</th>
                                <th>Dari Segmen</th>
                                <th class="text-center">Arah</th>
                                <th>Ke Segmen</th>
                                <th class="text-center">Score</th>
                                <th class="text-end">Monetary (IDR)</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $h)
                            <tr>
                                <td class="text-nowrap col-2">
                                    <span class="fw-bold">{{ $h->created_at->format('d M Y') }}</span><br>
                                    <small class="text-muted">{{ $h->created_at->format('H:i') }} WIB</small>
                                </td>
                                <td>
                                    @if($h->segment_from)
                                        <span class="badge bg-light-secondary text-secondary">{{ $h->segment_from }}</span>
                                    @else
                                        <span class="text-muted italic">First Entry</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($h->segment_from === null)
                                        <i class="bi bi-plus-circle-fill text-info" title="Pelanggan Baru"></i>
                                    @elseif($h->is_segment_changed)
                                        @php
                                            // Logic sederhana: jika score naik, panah hijau. Jika turun, panah merah.
                                            $scoreDiff = $h->rfm_score - ($history[$loop->index + 1]->rfm_score ?? $h->rfm_score);
                                        @endphp
                                        @if($scoreDiff >= 0)
                                            <i class="bi bi-arrow-up-circle-fill text-success" title="Meningkat"></i>
                                        @else
                                            <i class="bi bi-arrow-down-circle-fill text-danger" title="Menurun"></i>
                                        @endif
                                    @else
                                        <i class="bi bi-dash-circle text-muted" title="Tetap"></i>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light-primary text-primary">{{ $h->segment_to }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold">{{ $h->rfm_score }}</div>
                                    <small class="text-muted" style="font-size: 0.7rem;">R:{{ $h->recency_days }}d | F:{{ $h->frequency }}x</small>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    {{ number_format($h->monetary, 0, ',', '.') }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted">{{ $h->calculationBatch?->triggeredBy?->name ?? 'System' }}</small>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <img src="{{ asset('assets/images/samples/error-404.png') }}" alt="No Data" style="height: 100px; opacity: 0.5;">
                                    <p class="text-muted mt-3">Belum ada riwayat segmentasi untuk pelanggan ini.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $history->links() }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection