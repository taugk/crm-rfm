@extends('layouts.customers')

@section('title', 'Tukar Poin Reward')

@section('content')
<style>
    /* Profile Summary Mini Card */
    .user-point-summary {
        background: var(--primary-gradient);
        border-radius: 20px;
        color: white;
        padding: 25px;
        margin-bottom: 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }
    .user-point-summary::after {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 300px; height: 300px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }

    /* Reward Card Styling */
    .reward-card {
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        background: #fff;
    }
    .reward-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    }
    .reward-img-wrapper {
        position: relative;
        height: 200px;
        overflow: hidden;
        background: #f8fafc;
    }
    .reward-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .reward-card:hover .reward-img {
        transform: scale(1.1);
    }
    .point-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(5px);
        padding: 8px 15px;
        border-radius: 12px;
        font-weight: 800;
        color: var(--primary);
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        z-index: 2;
    }
    .stock-badge {
        position: absolute;
        bottom: 15px;
        left: 15px;
        background: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 12px;
        backdrop-filter: blur(4px);
        z-index: 2;
    }
    .unlimited-badge {
        background: rgba(99, 102, 241, 0.8) !important;
    }

    /* Modal Styling */
    .modal-content {
        border-radius: 28px;
        border: none;
    }
    .btn-redeem {
        border-radius: 14px;
        padding: 12px;
        font-weight: 700;
        transition: all 0.3s;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;  
        overflow: hidden;
    }
</style>

<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h3 class="fw-800 text-dark mb-1">Tukar Poin Reward 🎁</h3>
            <p class="text-muted">Tukarkan poin loyalty kamu dengan berbagai hadiah menarik kami.</p>
        </div>
    </div>

    {{-- User Point Summary --}}
    <div class="user-point-summary shadow-lg">
        <div style="z-index: 1;">
            <p class="mb-0 opacity-75 fw-600">Total Saldo Poin Anda</p>
            <h2 class="fw-800 mb-0 display-6">
                {{ number_format($customer->total_points) }} 
                <span class="fs-4 fw-normal">PTS</span>
            </h2>
        </div>
        <div class="d-none d-md-block" style="z-index: 1;">
            <i class="bi bi-gift-fill display-4 opacity-50"></i>
        </div>
    </div>

    {{-- Categories Filter --}}
    <div class="d-flex gap-2 mb-4 overflow-x-auto pb-2">
        <a href="{{ route('customers.points.redeem', ['type' => 'all']) }}" class="btn {{ request('type') == 'all' || !request('type') ? 'btn-primary' : 'btn-white border text-muted' }} rounded-pill px-4 fw-600">Semua</a>
        <a href="{{ route('customers.points.redeem', ['type' => 'product']) }}" class="btn {{ request('type') == 'product' ? 'btn-primary' : 'btn-white border text-muted' }} rounded-pill px-4 fw-600">Produk</a>
        <a href="{{ route('customers.points.redeem', ['type' => 'voucher']) }}" class="btn {{ request('type') == 'voucher' ? 'btn-primary' : 'btn-white border text-muted' }} rounded-pill px-4 fw-600">Voucher</a>
        <a href="{{ route('customers.points.redeem', ['type' => 'other']) }}" class="btn {{ request('type') == 'other' ? 'btn-primary' : 'btn-white border text-muted' }} rounded-pill px-4 fw-600">Lainnya</a>
    </div>

    <div class="row g-4">
        @forelse($rewards as $reward)
        <div class="col-sm-6 col-lg-3">
            <div class="card reward-card shadow-sm border-0">
                <div class="reward-img-wrapper">
                    <div class="point-badge">
                        {{ number_format($reward->points_required) }} PTS
                    </div>

                    {{-- Logic Badge Stok: Hanya tampil untuk tipe produk fisik --}}
                    @if($reward->reward_type === 'product')
                        @if(!is_null($reward->stock))
                            <div class="stock-badge">
                                Stok: {{ $reward->stock }}
                            </div>
                        @else
                            <div class="stock-badge unlimited-badge">
                                <i class="bi bi-infinity"></i> Tersedia
                            </div>
                        @endif
                    @endif

                    @if($reward->image)
                        <img src="{{ asset('storage/' . $reward->image) }}" class="reward-img" alt="{{ $reward->name }}">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($reward->name) }}&background=f1f5f9&color=64748b&size=500" class="reward-img" alt="no-image">
                    @endif
                </div>

                <div class="card-body p-4">
                    <h6 class="fw-800 text-dark mb-1 text-truncate">{{ $reward->name }}</h6>
                    <p class="small text-muted mb-3 line-clamp-2" style="min-height: 40px;">
                        {{ $reward->description ?? 'Tukarkan poinmu dengan hadiah menarik ini.' }}
                    </p>
                    
                    @php
                        $userPoints = $customer->total_points;
                        
                        // Tentukan apakah item non-fisik
                        $isNonPhysical = in_array($reward->reward_type, ['voucher', 'other']);
                        
                        // Tersedia jika: non-fisik (bypass stok) ATAU stok NULL (unlimited) ATAU stok > 0
                        $isAvailable = $isNonPhysical || (is_null($reward->stock) || $reward->stock > 0);
                        
                        $hasEnoughPoints = $userPoints >= $reward->points_required;
                    @endphp

                    @if($isAvailable && $hasEnoughPoints)
                        <button class="btn btn-primary w-100 btn-redeem shadow-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#redeemModal{{ $reward->id }}">
                            Tukarkan Sekarang
                        </button>
                    @elseif(!$isAvailable)
                        <button class="btn btn-secondary w-100 btn-redeem disabled" style="cursor: not-allowed;">
                            Stok Habis
                        </button>
                    @else
                        <button class="btn btn-light w-100 btn-redeem text-muted disabled" style="cursor: not-allowed;">
                            Poin Tidak Cukup
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi --}}
        <div class="modal fade" id="redeemModal{{ $reward->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-3 text-center">
                    <div class="modal-body">
                        <div class="mb-4">
                            <i class="bi bi-patch-question-fill text-primary display-1"></i>
                        </div>
                        <h4 class="fw-800">Konfirmasi Penukaran</h4>
                        <p class="text-muted">Kamu akan menukarkan <strong>{{ number_format($reward->points_required) }} poin</strong> untuk satu <strong>{{ $reward->name }}</strong>.</p>
                        
                        <div class="alert alert-info border-0 rounded-4 small mb-0">
                            Poin akan langsung dikurangi setelah konfirmasi berhasil.
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-light w-100 py-3 rounded-4 fw-bold" data-bs-dismiss="modal">Batal</button>
                            <form action="{{ route('customers.points.redeem.process') }}" method="POST" class="w-100">
                                @csrf
                                <input type="hidden" name="reward_id" value="{{ $reward->id }}">
                                <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-primary">Ya, Tukarkan!</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <img src="https://illustrations.popsy.co/blue/searching.svg" style="width: 200px;" class="mb-4">
            <h5 class="fw-bold">Belum ada reward tersedia</h5>
            <p class="text-muted">Cek kembali nanti untuk penawaran menarik lainnya.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection