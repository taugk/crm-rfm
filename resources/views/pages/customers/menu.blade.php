@extends('layouts.customers')

@section('title', 'Menu CoffeeHub')

@section('content')
<style>
    .menu-category-link {
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-weight: 600;
    }
    .menu-category-link.active {
        background: var(--primary-gradient);
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .product-card {
        border-radius: 24px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1) !important;
    }
    .product-img-container {
        height: 180px;
        overflow: hidden;
        border-radius: 20px 20px 0 0;
        position: relative;
    }
    .product-img-container img {
        transition: transform 0.5s ease;
    }
    .product-card:hover .product-img-container img {
        transform: scale(1.1);
    }
    .btn-add-cart {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
</style>

<div class="container pb-5">
    {{-- Header Section --}}
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h3 class="fw-800 text-dark mb-1">Daftar Menu ☕</h3>
            <p class="text-muted">Pilih menu favoritmu dan kumpulkan poin loyalitasnya.</p>
        </div>
        <div class="col-md-5">
            <div class="position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" class="form-control rounded-pill ps-5 py-2 border-0 shadow-sm" placeholder="Cari kopi favoritmu...">
            </div>
        </div>
    </div>

    {{-- Category Tabs --}}
    <div class="d-flex gap-2 mb-4 overflow-x-auto pb-2" style="white-space: nowrap;">
        <a href="?category=all" class="btn menu-category-link rounded-pill px-4 {{ !request('category') || request('category') == 'all' ? 'active' : '' }}">Semua</a>
        @foreach($categories as $cat)
            <a href="?category={{ $cat->name }}" class="btn menu-category-link rounded-pill px-4 {{ request('category') == $cat->name ? 'active' : '' }}">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>

    {{-- Menu Grid --}}
    <div class="row g-4">
        @forelse($featuredProducts as $product)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card product-card border-0 shadow-sm h-100">
                    <div class="product-img-container">
                        <img src="{{ $product->image ? asset('storage/'.$product->image) : 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400&auto=format&fit=crop' }}" 
                             class="w-100 h-100 object-fit-cover">
                        <span class="badge bg-white text-primary position-absolute bottom-0 start-0 m-3 rounded-pill fw-bold shadow-sm" style="font-size: 10px;">
                            <i class="bi bi-star-fill text-warning me-1"></i> Terlaris
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="fw-700 text-dark mb-1 text-truncate">{{ $product->name }}</h6>
                        <p class="text-muted small mb-3 line-clamp-2" style="height: 32px; font-size: 11px;">
                            {{ $product->description ?? 'Deskripsi menu belum tersedia.' }}
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block" style="font-size: 10px;">Harga mulai</small>
                                <span class="fw-800 text-primary">Rp{{ number_format($product->first()->price ?? 0) }}</span>
                            </div>
                            <button class="btn btn-primary btn-add-cart shadow-primary">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <img src="https://illustrations.popsy.co/blue/searching.svg" style="width: 200px;" class="mb-4">
                <h5 class="fw-bold">Menu tidak ditemukan</h5>
                <p class="text-muted">Coba cari dengan kata kunci lain.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection