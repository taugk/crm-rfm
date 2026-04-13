@extends('layouts.customers')

@section('title', 'Promo Eksklusif')

@section('content')
<style>
    .promo-card {
        border: none;
        border-radius: 24px;
        transition: all 0.3s ease;
        background: #fff;
        position: relative;
        overflow: hidden;
    }
    .promo-card:hover {
        transform: translateY(-5px);
    }
    .promo-line {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 6px;
        background: var(--primary-gradient);
    }
    .coupon-cut {
        position: absolute;
        width: 30px;
        height: 30px;
        background: #f8fafc; /* Sama dengan background body */
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
    }
    .cut-left { left: -15px; }
    .cut-right { right: -15px; }
    
    .promo-code-box {
        background: #f1f5f9;
        border: 2px dashed #cbd5e1;
        padding: 10px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .promo-code-box:hover {
        background: #e2e8f0;
        border-color: var(--bs-primary);
    }
    .segment-badge {
        font-size: 11px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-weight: 700;
    }
</style>

<div class="container">
    {{-- Header & Info Segment --}}
    <div class="row mb-5 align-items-center">
        <div class="col-md-7">
            <h3 class="fw-800 text-dark">Promo Spesial Untukmu 🎫</h3>
            <p class="text-muted">Halo <strong>{{ auth()->guard('customers')->user()->name }}</strong>, berdasarkan statusmu sebagai <span class="badge bg-primary-subtle text-primary">{{ auth()->guard('customers')->user()->rfmScore->segment_name ?? 'New Member' }}</span>, berikut penawaran terbaik hari ini.</p>
        </div>
        <div class="col-md-5 text-md-end">
            <div class="bg-white d-inline-block p-3 rounded-4 shadow-sm border">
                <p class="small text-muted mb-0">Min. Belanja Kamu</p>
                <h5 class="fw-800 mb-0 text-primary">Rp{{ number_format(auth()->guard('customers')->user()->rfmScore->monetary ?? 0) }}</h5>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse($promos as $promo)
            <div class="col-md-6 col-lg-4">
                <div class="card promo-card shadow-sm h-100">
                    <div class="promo-line"></div>
                    
                    {{-- Badge Segment Target --}}
                    <div class="p-4 pb-0 d-flex justify-content-between">
                        <span class="badge bg-{{ $promo->target_segment == 'all' || !$promo->target_segment ? 'secondary' : 'warning' }} segment-badge">
                            {{ $promo->target_segment ?: 'Semua Member' }}
                        </span>
                        <small class="text-danger fw-bold"><i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($promo->end_date)->diffForHumans() }}</small>
                    </div>

                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <h2 class="fw-900 text-dark mb-1">
                                @if($promo->discount_type == 'percentage')
                                    {{ number_format($promo->discount_value) }}% <small class="fs-6">OFF</small>
                                @else
                                    <small class="fs-6">Potongan</small> Rp{{ number_format($promo->discount_value / 1000) }}k
                                @endif
                            </h2>
                            <h5 class="fw-700">{{ $promo->promo_name }}</h5>
                            <p class="small text-muted mb-0">{{ $promo->description }}</p>
                        </div>

                        {{-- Coupon Decoration --}}
                        <div class="position-relative my-4">
                            <hr class="text-muted opacity-25">
                            <div class="coupon-cut cut-left"></div>
                            <div class="coupon-cut cut-right"></div>
                        </div>

                        <div class="mb-3">
                            <p class="small fw-bold text-muted mb-2 uppercase">Salin Kode Promo</p>
                            <div class="promo-code-box d-flex justify-content-between align-items-center" onclick="copyToClipboard('{{ $promo->promo_code }}')">
                                <span class="fw-800 text-primary fs-5">{{ $promo->promo_code }}</span>
                                <i class="bi bi-clipboard-plus fs-5 text-muted"></i>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-start">
                                <p class="small text-muted mb-0">Min. Spend</p>
                                <p class="fw-bold mb-0">Rp{{ number_format($promo->min_spend) }}</p>
                            </div>
                            <div class="text-end">
                                <p class="small text-muted mb-0">Kuota</p>
                                <p class="fw-bold mb-0">{{ $promo->usage_limit - $promo->used_count }} <small>Sisa</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <img src="https://illustrations.popsy.co/blue/ticket.svg" style="width: 180px;" class="mb-4">
                <h4 class="fw-800">Belum ada promo hari ini</h4>
                <p class="text-muted">Tenang saja! Kami akan segera mengirimkan promo khusus untuk segmentasi <span class="text-primary fw-bold">{{ auth()->guard('customers')->user()->rfmScore->segment_name ?? 'Member' }}</span> milikmu.</p>
            </div>
        @endforelse
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Kode promo ' + text + ' berhasil disalin!');
        });
    }
</script>
@endsection