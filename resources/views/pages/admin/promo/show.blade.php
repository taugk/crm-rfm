@php
    $layout = match(auth()->user()->role) {
        'manager' => 'layouts.manager',
        'admin' => 'layouts.admin',
        default => 'layouts.admin',
    };
    $routePrefix = match(auth()->user()->role) {
        'manager' => 'manager',
        'admin' => 'admin',
        default => 'admin'
    };
@endphp

@extends($layout)

@section('title', 'Detail Promosi')

@section('content')
<div class="page-heading">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="fw-bold"><i class="bi bi-info-circle me-2 text-info"></i>Detail Promosi</h3>
        <a href="{{ route($routePrefix . '.promo') }}" class="btn btn-light-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="page-content">
    <section class="section">
        <div class="row">
            {{-- KOLOM KIRI: Ringkasan & Kuota --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center py-4">
                    <div class="card-body">
                        <div class="avatar avatar-xl bg-light-primary mb-3">
                            <span class="avatar-content"><i class="bi bi-megaphone-fill fs-1 text-primary"></i></span>
                        </div>
                        <h4 class="fw-bold mb-1">{{ $promo->promo_name }}</h4>
                        <div class="mb-3">
                            {!! $promo->status_label !!}
                        </div>
                        
                        <hr class="opacity-50">
                        
                        <p class="text-muted mb-0 small uppercase fw-bold">Besar Diskon</p>
                        <h2 class="text-primary fw-bold mb-4">
                            {{ $promo->discount_type == 'percentage' ? number_format($promo->discount_value, 0).'%' : 'Rp '.number_format($promo->discount_value, 0, ',', '.') }}
                        </h2>

                        {{-- Progress Bar Pemakaian Kuota --}}
                        <div class="px-3">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="fw-bold">Penggunaan Kuota</span>
                                <span>{{ $promo->used_count }} / {{ $promo->usage_limit ?? '∞' }}</span>
                            </div>
                            @php 
                                $percent = $promo->usage_limit ? ($promo->used_count / $promo->usage_limit) * 100 : 0;
                                $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $percent }}%" 
                                     aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                {{ $promo->usage_limit ? 'Tersisa '.($promo->usage_limit - $promo->used_count).' kuota' : 'Kuota tidak terbatas' }}
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-grid gap-2">
                    <a href="{{ route($routePrefix . '.promo.edit', $promo->id) }}" class="btn btn-warning fw-bold shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i> Edit Promosi
                    </a>
                </div>
            </div>

            {{-- KOLOM KANAN: Informasi Detail --}}
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Lengkap</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover align-middle">
                            <tr>
                                <th width="35%" class="text-muted small text-uppercase">Kode Promo</th>
                                <td>: <code class="fs-5 fw-bold">{{ $promo->promo_code ?? 'Tanpa Kode (Otomatis)' }}</code></td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Target Segmen</th>
                                <td>: <span class="badge bg-light-info text-info px-3">{{ $promo->target_segment ?? 'Semua Pelanggan' }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Minimal Belanja</th>
                                <td>: <span class="fw-bold text-dark">Rp {{ number_format($promo->min_spend, 0, ',', '.') }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Waktu Mulai</th>
                                <td>: <span class="text-dark">{{ $promo->start_date->format('d F Y - H:i') }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Waktu Berakhir</th>
                                <td>: <span class="text-dark">{{ $promo->end_date->format('d F Y - H:i') }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Deskripsi</th>
                                <td>: <p class="mb-0 text-secondary">{{ $promo->description ?? 'Tidak ada deskripsi tambahan.' }}</p></td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Terakhir Diperbarui</th>
                                <td>: <small class="text-muted">{{ $promo->updated_at->diffForHumans() }}</small></td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- Tips Card --}}
                <div class="alert alert-light-primary border-0 shadow-sm mt-3">
                    <h6 class="fw-bold"><i class="bi bi-lightbulb me-2"></i>Informasi Sistem</h6>
                    <p class="small mb-0">Status promosi akan berubah menjadi <strong>"Selesai"</strong> secara otomatis jika waktu saat ini melewati waktu berakhir, atau menjadi <strong>"Limit Habis"</strong> jika pemakaian mencapai batas maksimal.</p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection