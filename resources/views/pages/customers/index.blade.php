@extends('layouts.customers')

@section('title', 'Member Dashboard')

@section('content')
<style>
    .point-card {
        background: var(--primary-gradient);
        color: white;
        position: relative;
        overflow: hidden;
    }
    .point-card::before {
        content: '';
        position: absolute;
        width: 200px; height: 200px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        top: -100px; right: -50px;
    }
    .icon-box {
        width: 54px; height: 54px;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
    }
    .status-badge {
        padding: 6px 14px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
    }
    .reward-item {
        transition: transform 0.3s;
        border: 1px dashed #dee2e6;
    }
    .reward-item:hover {
        transform: translateY(-5px);
        border-color: var(--bs-primary);
    }
</style>

<div class="row g-4">
    {{-- Welcome Message & RFM Segment --}}
    <div class="col-12 mb-2 d-flex justify-content-between align-items-end">
        <div>
            <h2 class="fw-800 text-dark">Halo, {{ explode(' ', auth()->guard('customers')->user()->name)[0] }}! 👋</h2>
            <p class="text-muted">Status Member: 
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                    {{ auth()->guard('customers')->user()->rfmScore->segment_name ?? 'New Member' }}
                </span>
            </p>
        </div>
        <div class="text-end d-none d-md-block">
            <p class="small text-muted mb-0">Member sejak</p>
            <p class="fw-bold">{{ auth()->guard('customers')->user()->created_at->format('M Y') }}</p>
        </div>
    </div>

    {{-- Main Points Card --}}
    <div class="col-lg-5">
        <div class="card custom-card point-card p-4 h-100 border-0 shadow-lg">
            <div class="d-flex justify-content-between">
                <div>
                    <span class="badge bg-white bg-opacity-20 text-dark rounded-pill px-3 py-2 mb-3">Loyalty Points</span>
                    <h1 class="display-3 fw-800 mb-0">{{ number_format(auth()->guard('customers')->user()->total_points) }}</h1>
                    @php
    $userPoints = auth()->guard('customers')->user()->total_points;

    // 1. Cari reward terakhir yang SUDAH dicapai (sebagai baseline 0% progress bar)
    $lastReward = \App\Models\PointReward::where('is_active', true)
                    ->where('points_required', '<=', $userPoints)
                    ->orderBy('points_required', 'desc')
                    ->first();

    // 2. Cari reward berikutnya (Target)
    // LOGIKA: Ambil yang points > user points DAN (tipe voucher/other ATAU tipe produk stok > 0)
    $nextReward = \App\Models\PointReward::where('is_active', true)
                    ->where('points_required', '>', $userPoints)
                    ->where(function($q) {
                        $q->whereIn('reward_type', ['voucher', 'other'])
                          ->orWhere(function($sq) {
                              $sq->where('reward_type', 'product')
                                 ->where('stock', '>', 0);
                          });
                    })
                    ->orderBy('points_required', 'asc')
                    ->first();

    // 3. Hitung Persentase Progress
    if ($nextReward) {
        $startPoint = $lastReward ? $lastReward->points_required : 0;
        $targetPoint = $nextReward->points_required;
        
        // Rumus: (Poin Sekarang - Poin Terakhir) / (Target Poin - Poin Terakhir)
        $currentProgress = $userPoints - $startPoint;
        $totalNeeded = $targetPoint - $startPoint;
        
        // Hindari pembagian dengan nol
        $percent = ($totalNeeded > 0) ? ($currentProgress / $totalNeeded) * 100 : 0;
    } else {
        $percent = 100; 
    }
    
    $percent = max(0, min(100, $percent));
@endphp
                    <p class="opacity-75 fw-500 mt-2">
                        @if($nextReward)
                            Kurang {{ number_format($nextReward->points_required - auth()->guard('customers')->user()->total_points) }} poin lagi untuk {{ $nextReward->name }}!
                        @else
                            Poinmu sudah maksimal! Yuk tukarkan hadiah.
                        @endif
                    </p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-4 align-self-start">
                    <i class="bi bi-stars fs-2" style="color: #FFD700;"></i>
                </div>
            </div>
            <div class="mt-auto pt-4">
                <div class="progress" style="height: 10px; background: rgba(255,255,255,0.2); border-radius: 20px;">
                    <div class="progress-bar bg-white" style="width: {{ $percent }}%; border-radius: 20px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="col-lg-7">
        <div class="row g-4">
            <div class="col-6">
                <div class="card custom-card p-4 h-100">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="bi bi-bag-check fs-4"></i>
                    </div>
                    <p class="text-muted small fw-bold mb-1">TOTAL KUNJUNGAN</p>
                    <h4 class="fw-800 mb-0 text-dark">{{ auth()->guard('customers')->user()->transactions()->where('status', 'completed')->count() }}x</h4>
                </div>
            </div>
            <div class="col-6">
                <div class="card custom-card p-4 h-100">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning mb-3">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <p class="text-muted small fw-bold mb-1">TERAKHIR NGOPI</p>
                    <h4 class="fw-800 mb-0 text-dark">
                        {{ auth()->guard('customers')->user()->last_purchase_at ? \Carbon\Carbon::parse(auth()->guard('customers')->user()->last_purchase_at)->diffForHumans() : '-' }}
                    </h4>
                </div>
            </div>
            {{-- Promo Bar --}}
            <div class="col-12">
                <div class="card custom-card p-4 d-flex flex-row align-items-center justify-content-between bg-light border-0">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-ticket-perforated-fill text-primary fs-4 me-3"></i>
                        <div>
                            <p class="mb-0 small fw-600">Gunakan promo hari ini!</p>
                            <small class="text-muted">Cek kode promo khusus member aktif.</small>
                        </div>
                    </div>
                    <a href="{{ route('customers.promos') }}" class="btn btn-primary btn-sm rounded-pill px-4">Cek Promo</a>
                </div>
            </div>
        </div>
    </div>

    {{-- NEW MENU: Point Rewards (Penukaran Hadiah) --}}
    <div class="col-12 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-800 mb-0 text-dark">Tukar Poin Hadiah</h5>
            <a href="{{ route('customers.points.redeem') }}" class="small fw-bold text-decoration-none">Semua Hadiah</a>
        </div>
        <div class="row g-3">
            @foreach(\App\Models\PointReward::where('is_active', true)
    ->where(function($q) {
        $q->whereIn('reward_type', ['voucher', 'other']) // Voucher & Other tidak cek stok
          ->orWhere(function($sq) {
              $sq->where('reward_type', 'product')
                 ->where('stock', '>', 0); // Produk fisik wajib ada stok
          });
    })
    ->orderBy('points_required', 'asc')
    ->take(3)
    ->get() as $reward)
    
    <div class="col-md-4">
        <div class="card h-100 reward-item p-3 shadow-sm rounded-4 border-0">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 bg-light rounded-3 p-2">
                    {{-- Icon dinamis berdasarkan tipe --}}
                    @if($reward->reward_type == 'voucher')
                        <i class="bi bi-ticket-perforated text-primary fs-4"></i>
                    @elseif($reward->reward_type == 'product')
                        <i class="bi bi-box-seam text-primary fs-4"></i>
                    @else
                        <i class="bi bi-gift text-primary fs-4"></i>
                    @endif
                </div>
                
                <div class="flex-grow-1 ms-3">
                    <h6 class="mb-0 fw-bold small text-truncate" style="max-width: 120px;">{{ $reward->name }}</h6>
                    <p class="small text-primary fw-bold mb-0" style="font-size: 11px;">
                        {{ number_format($reward->points_required) }} PTS
                    </p>
                    {{-- Tampilkan sisa stok hanya jika tipenya produk --}}
                    @if($reward->reward_type == 'product')
                        <small class="text-muted" style="font-size: 9px;">Stok: {{ $reward->stock }}</small>
                    @else
                        <small class="text-success" style="font-size: 9px;"><i class="bi bi-check-circle-fill"></i> Tersedia</small>
                    @endif
                </div>

                @php
                    $hasEnoughPoints = auth()->guard('customers')->user()->total_points >= $reward->points_required;
                @endphp

                <button class="btn btn-sm {{ $hasEnoughPoints ? 'btn-primary shadow-sm' : 'btn-light text-muted' }} rounded-pill px-3" 
                        style="font-size: 10px; font-weight: 700;"
                        {{ !$hasEnoughPoints ? 'disabled' : '' }}>
                    Tukar
                </button>
            </div>
        </div>
    </div>
@endforeach
        </div>
    </div>

    {{-- NEW SECTION: Featured Products / Menu --}}
    <div class="col-12 mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-800 mb-0 text-dark">Menu Favorit Hari Ini ☕</h5>
                <p class="text-muted small mb-0">Pesan langsung dan kumpulkan poinnya!</p>
            </div>
            <a href="{{ route('customers.menu') }}" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm">Lihat Menu Lengkap</a>
        </div>
        
        <div class="row g-3">
    @foreach($featuredProducts as $product)
    <div class="col-6 col-md-3">
        <div class="card custom-card border-0 product-card shadow-sm h-100">
            {{-- Image Logic --}}
            <img src="{{ $product->image ? asset('storage/'.$product->image) : 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400&auto=format&fit=crop' }}" 
                                             class="card-img-top" style="height: 140px; object-fit: cover; border-radius: 20px 20px 0 0;">
            
            <div class="card-body p-3">
                <h6 class="fw-700 text-dark mb-1 text-truncate">{{ $product->name }}</h6>
                <div class="d-flex justify-content-between align-items-center">
                    {{-- Mengambil harga dari detail pertama --}}
                    <span class="fw-800 text-primary small">
                        Rp{{ number_format($product->first()->price ?? 0) }}
                    </span>
                    <button class="btn btn-primary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
    </div>

    {{-- Transaction History --}}
    <div class="col-12 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-800 mb-0 text-dark">Riwayat Transaksi</h5>
            <a href="{{ route('customers.transactions') }}" class="btn btn-sm btn-light rounded-pill px-3 fw-bold border">Lihat Semua <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        
        <div class="card custom-card shadow-sm border-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 border-0 text-muted small fw-bold">INVOICE</th>
                            <th class="py-3 border-0 text-muted small fw-bold">TANGGAL</th>
                            <th class="py-3 border-0 text-muted small fw-bold">TOTAL</th>
                            <th class="px-4 py-3 border-0 text-muted small fw-bold text-center">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(auth()->guard('customers')->user()->transactions()->latest()->take(5)->get() as $trx)
                        <tr>
                            <td class="px-4 py-4 fw-800 text-primary">#{{ $trx->invoice_number }}</td>
                            <td class="text-muted">
                                {{ $trx->transaction_date->format('d M Y') }}<br>
                                <small class="opacity-50">{{ $trx->transaction_date->format('H:i') }}</small>
                            </td>
                            <td class="fw-bold text-dark">Rp{{ number_format($trx->total_price) }}</td>
                            <td class="px-4 text-center">
                                @php
                                    $statusClass = [
                                        'completed' => 'bg-success-subtle text-success',
                                        'pending' => 'bg-warning-subtle text-warning',
                                        'cancelled' => 'bg-danger-subtle text-danger'
                                    ][$trx->status] ?? 'bg-secondary-subtle text-secondary';
                                @endphp
                                <span class="status-badge {{ $statusClass }}">{{ $trx->status }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <img src="https://illustrations.popsy.co/blue/coffee-break.svg" style="width: 150px;" class="mb-4 opacity-50">
                                <p class="text-muted">Belum ada transaksi. Yuk mampir ke cafe!</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection