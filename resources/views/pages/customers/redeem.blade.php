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
</style>

<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h3 class="fw-800 text-dark mb-1">Tukar Poin Reward 🎁</h3>
            <p class="text-muted">Pilih menu favoritmu dan tukarkan poin yang sudah kamu kumpulkan.</p>
        </div>
    </div>

    <div class="user-point-summary shadow-lg">
        <div>
            <p class="mb-0 opacity-75 fw-600">Total Saldo Poin Anda</p>
            <h2 class="fw-800 mb-0 display-6">{{ number_format(auth()->guard('customers')->user()->total_points) }} <span class="fs-4 fw-normal">PTS</span></h2>
        </div>
        <div class="d-none d-md-block">
            <i class="bi bi-gift-fill display-4 opacity-50"></i>
        </div>
    </div>

    <div class="d-flex gap-2 mb-4 overflow-x-auto pb-2">
        <button class="btn btn-primary rounded-pill px-4 fw-600">Semua</button>
        <button class="btn btn-white border rounded-pill px-4 fw-600 text-muted">Coffee</button>
        <button class="btn btn-white border rounded-pill px-4 fw-600 text-muted">Non-Coffee</button>
        <button class="btn btn-white border rounded-pill px-4 fw-600 text-muted">Snack</button>
    </div>

    <div class="row g-4">
        @php
            // Mock data untuk contoh, nantinya ambil dari database
            $rewards = [
                ['name' => 'Caramel Macchiato', 'points' => 1500, 'img' => 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?q=80&w=500'],
                ['name' => 'Croissant Almond', 'points' => 800, 'img' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?q=80&w=500'],
                ['name' => 'Iced Caffe Latte', 'points' => 1200, 'img' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?q=80&w=500'],
                ['name' => 'Red Velvet Cake', 'points' => 2000, 'img' => 'https://images.unsplash.com/photo-1586788680434-30d324671ff6?q=80&w=500'],
            ];
        @endphp

        @foreach($rewards as $reward)
        <div class="col-sm-6 col-lg-3">
            <div class="card reward-card shadow-sm border-0">
                <div class="reward-img-wrapper">
                    <div class="point-badge">
                        {{ number_format($reward['points']) }} PTS
                    </div>
                    <img src="{{ $reward['img'] }}" class="reward-img" alt="{{ $reward['name'] }}">
                </div>
                <div class="card-body p-4">
                    <h6 class="fw-800 text-dark mb-1">{{ $reward['name'] }}</h6>
                    <p class="small text-muted mb-3">Tukarkan poinmu dengan 1 item ini.</p>
                    
                    @if(auth()->guard('customers')->user()->total_points >= $reward['points'])
                        <button class="btn btn-primary w-100 btn-redeem" data-bs-toggle="modal" data-bs-target="#redeemModal{{ $loop->index }}">
                            Tukarkan Sekarang
                        </button>
                    @else
                        <button class="btn btn-light w-100 btn-redeem text-muted disabled" style="cursor: not-allowed;">
                            Poin Tidak Cukup
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="modal fade" id="redeemModal{{ $loop->index }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-3">
                    <div class="modal-body text-center">
                        <div class="mb-4">
                            <i class="bi bi-question-circle text-primary display-1"></i>
                        </div>
                        <h4 class="fw-800">Konfirmasi Penukaran</h4>
                        <p class="text-muted">Kamu akan menukarkan <strong>{{ number_format($reward['points']) }} poin</strong> untuk satu <strong>{{ $reward['name'] }}</strong>. Lanjutkan?</p>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-light w-100 py-3 rounded-4 fw-bold" data-bs-dismiss="modal">Batal</button>
                            <form action="#" method="POST" class="w-100">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-primary">Ya, Tukarkan!</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection