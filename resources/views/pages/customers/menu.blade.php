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
        width: 100%;
        height: 100%;
        object-fit: cover;
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
    .cart-sidebar {
        position: fixed;
        right: -400px;
        top: 0;
        width: 400px;
        height: 100vh;
        background: white;
        box-shadow: -5px 0 20px rgba(0,0,0,0.1);
        transition: right 0.3s ease;
        z-index: 1050;
        display: flex;
        flex-direction: column;
    }
    .cart-sidebar.open {
        right: 0;
    }
    .cart-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
        display: none;
    }
    .cart-overlay.show {
        display: block;
    }
    .cart-items {
        flex: 1;
        overflow-y: auto;
        max-height: calc(100vh - 180px);
    }
    .cart-item-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 10px;
    }
    .quantity-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }
</style>

<div class="container pb-5">
    {{-- Header Section --}}
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h3 class="fw-800 text-dark mb-1">Daftar Menu ☕</h3>
            <p class="text-muted">Pilih menu favoritmu dan kumpulkan poin loyalitasnya.</p>
        </div>
        <div class="col-md-4">
            <div class="position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="searchInput" class="form-control rounded-pill ps-5 py-2 border-0 shadow-sm" placeholder="Cari kopi favoritmu...">
            </div>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary rounded-pill w-100" onclick="toggleCart()">
                <i class="bi bi-cart3 me-2"></i> Keranjang
                <span id="cartCount" class="badge bg-white text-primary rounded-pill ms-2">{{ count($cart ?? []) }}</span>
            </button>
        </div>
    </div>

    {{-- Category Tabs --}}
    <div class="d-flex gap-2 mb-4 overflow-x-auto pb-2" style="white-space: nowrap;">
        <a href="{{ route('customers.menu', ['category' => 'all']) }}" 
           class="btn menu-category-link rounded-pill px-4 {{ !request('category') || request('category') == 'all' ? 'active' : '' }}">
            Semua
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('customers.menu', ['category' => $cat->name]) }}" 
               class="btn menu-category-link rounded-pill px-4 {{ request('category') == $cat->name ? 'active' : '' }}">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>

    {{-- Menu Grid --}}
    <div class="row g-4" id="productGrid">
       @forelse($featuredProducts as $product)
    @php
        // Ambil stok dengan aman (jika tidak ada details, stok = 0)
        $productDetail = $product->details;
        $stock = $productDetail ? (int)$productDetail->stock : 0;
        $hasStock = $stock > 0;
        
        // Cek apakah produk memiliki detail
        $hasDetail = !is_null($productDetail);
    @endphp
    
    <div class="col-6 col-md-4 col-lg-3 product-item" 
         data-name="{{ strtolower($product->name) }}" 
         data-stock="{{ $stock }}"
         data-product-id="{{ $product->id }}">
        
        <div class="card product-card border-0 shadow-sm h-100 {{ !$hasStock ? 'opacity-75' : '' }}">
            <div class="product-img-container">
                <img src="{{ $product->image ? asset('storage/'.$product->image) : 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=400&auto=format' }}" 
                     class="w-100 h-100 object-fit-cover"
                     onerror="this.src='https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=400&auto=format'">
                
                {{-- Badge Stok --}}
                @if(!$hasDetail)
                    <span class="badge bg-secondary position-absolute bottom-0 start-0 m-3 rounded-pill">
                        <i class="bi bi-question-circle me-1"></i> No Data
                    </span>
                @elseif($hasStock && $stock <= 5)
                    <span class="badge bg-warning text-dark position-absolute bottom-0 start-0 m-3 rounded-pill">
                        <i class="bi bi-exclamation-triangle me-1"></i> Stok: {{ $stock }}
                    </span>
                @elseif($hasStock)
                    <span class="badge bg-success position-absolute bottom-0 start-0 m-3 rounded-pill">
                        <i class="bi bi-check-circle me-1"></i> Tersedia
                    </span>
                @else
                    <span class="badge bg-danger position-absolute bottom-0 start-0 m-3 rounded-pill">
                        <i class="bi bi-x-circle me-1"></i> Habis
                    </span>
                @endif
            </div>
            
            <div class="card-body p-3">
                <h6 class="fw-700 text-dark mb-1 text-truncate">{{ $product->name }}</h6>
                <p class="text-muted small mb-3" style="height: 32px; font-size: 11px;">
                    {{ Str::limit($product->description ?? 'Deskripsi menu belum tersedia.', 50) }}
                </p>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-800 text-primary fs-5">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                    </div>
                    
                    <button class="btn btn-primary btn-add-cart shadow-primary" 
                            onclick="addToCart({{ $product->id }}, this)"
                            {{ !$hasStock ? 'disabled' : '' }}
                            data-stock="{{ $stock }}">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12 text-center py-5">
        <i class="bi bi-emoji-frown fs-1 text-muted mb-3 d-block"></i>
        <h5 class="fw-bold">Menu tidak ditemukan</h5>
        <p class="text-muted">Coba cari dengan kata kunci lain atau pilih kategori berbeda.</p>
    </div>
@endforelse
    </div>
</div>

<!-- Cart Sidebar -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-cart3 me-2"></i>Keranjang Saya</h5>
        <button class="btn btn-sm btn-outline-secondary rounded-circle" onclick="toggleCart()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <div class="cart-items p-3" id="cartItems">
        <div class="text-center text-muted py-5">
            <i class="bi bi-cart-x fs-1 d-block mb-3"></i>
            <p>Keranjang belanja kosong</p>
        </div>
    </div>
    
    <div class="p-3 border-top bg-light">
        <div class="d-flex justify-content-between mb-2">
            <span>Subtotal</span>
            <span id="cartSubtotal">Rp 0</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span>Pajak (11%)</span>
            <span id="cartTax">Rp 0</span>
        </div>
        <hr>
        <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
            <span>Total</span>
            <span id="cartTotal" class="text-primary">Rp 0</span>
        </div>
        
        <div class="mb-3">
            <label class="small fw-bold">Metode Pembayaran</label>
            <select id="paymentMethod" class="form-select mt-1">
                <option value="cash">Tunai</option>
                <option value="qris">QRIS / Debit</option>
            </select>
        </div>
        
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="usePoints">
            <label class="form-check-label small">
                <i class="bi bi-gem me-1"></i>
                Gunakan poin saya ({{ number_format(auth()->guard('customers')->user()->total_points ?? 0) }} poin tersedia)
            </label>
        </div>
        
        <button class="btn btn-primary w-100 py-2 rounded-pill fw-bold" onclick="checkout()">
            <i class="bi bi-credit-card me-2"></i> Bayar Sekarang
        </button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let cart = @json($cart ?? []);
    
    $(document).ready(function() {
        renderCart();
        updateCartCount();
    });
    
    function updateCartCount() {
        const count = Object.keys(cart).length;
        $('#cartCount').text(count);
    }
    
    function toggleCart() {
        $('#cartSidebar').toggleClass('open');
        $('#cartOverlay').toggleClass('show');
        if ($('#cartSidebar').hasClass('open')) {
            renderCart();
        }
    }
    
    function addToCart(productId, btn) {
        $(btn).prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');
        
        $.ajax({
            url: "{{ route('customers.cart.add') }}",
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { product_id: productId, quantity: 1 },
            success: function(res) {
                if (res.success) {
                    cart = res.cart;
                    updateCartCount();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Ditambahkan!',
                        text: 'Produk ditambahkan ke keranjang',
                        timer: 1000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                    
                    if ($('#cartSidebar').hasClass('open')) {
                        renderCart();
                    }
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            },
            complete: function() {
                $(btn).prop('disabled', false).html('<i class="bi bi-plus-lg"></i>');
            }
        });
    }
    
    function updateCart(productId, quantity) {
        if (quantity < 1) {
            removeFromCart(productId);
            return;
        }
        
        $.ajax({
            url: "{{ route('customers.cart.update') }}",
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { product_id: productId, quantity: quantity },
            success: function(res) {
                if (res.success) {
                    cart = res.cart;
                    updateCartCount();
                    renderCart();
                }
            }
        });
    }
    
    function removeFromCart(productId) {
        Swal.fire({
            title: 'Hapus item?',
            text: 'Item akan dihapus dari keranjang',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('customers.cart.remove') }}",
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { product_id: productId },
                    success: function(res) {
                        if (res.success) {
                            cart = res.cart;
                            updateCartCount();
                            renderCart();
                            Swal.fire('Terhapus!', '', 'success');
                        }
                    }
                });
            }
        });
    }
    
    function renderCart() {
        const cartItems = Object.values(cart);
        let subtotal = 0;
        
        if (cartItems.length === 0) {
            $('#cartItems').html(`
                <div class="text-center text-muted py-5">
                    <i class="bi bi-cart-x fs-1 d-block mb-3"></i>
                    <p>Keranjang belanja kosong</p>
                </div>
            `);
            $('#cartSubtotal').text('Rp 0');
            $('#cartTax').text('Rp 0');
            $('#cartTotal').text('Rp 0');
            return;
        }
        
        let html = '';
        cartItems.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            html += `
                <div class="d-flex align-items-center gap-3 mb-3 pb-2 border-bottom">
                    <img src="${item.image ? '/storage/' + item.image : 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=50&auto=format'}" 
                         class="cart-item-img" onerror="this.src='https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=50&auto=format'">
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold">${escapeHtml(item.name)}</h6>
                        <small class="text-muted">Rp${Number(item.price).toLocaleString()}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-outline-secondary quantity-btn" onclick="updateCart(${item.id}, ${item.quantity - 1})">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span class="fw-bold" style="min-width: 25px; text-align: center;">${item.quantity}</span>
                        <button class="btn btn-outline-secondary quantity-btn" onclick="updateCart(${item.id}, ${item.quantity + 1})">
                            <i class="bi bi-plus"></i>
                        </button>
                        <button class="btn btn-outline-danger quantity-btn" onclick="removeFromCart(${item.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        const tax = subtotal * 0.11;
        const total = subtotal + tax;
        
        $('#cartItems').html(html);
        $('#cartSubtotal').text('Rp ' + subtotal.toLocaleString());
        $('#cartTax').text('Rp ' + tax.toLocaleString());
        $('#cartTotal').text('Rp ' + total.toLocaleString());
    }
    
    function checkout() {
        if (Object.keys(cart).length === 0) {
            Swal.fire('Keranjang Kosong', 'Silakan tambahkan produk terlebih dahulu', 'warning');
            return;
        }
        
        const paymentMethod = $('#paymentMethod').val();
        const usePoints = $('#usePoints').is(':checked') ? 1 : 0;
        
        Swal.fire({
            title: 'Konfirmasi Pembayaran',
            text: 'Apakah Anda yakin ingin melanjutkan pembayaran?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            confirmButtonText: 'Ya, Bayar',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Harap tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: "{{ route('customers.checkout') }}",
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: {
                        payment_method: paymentMethod,
                        use_points: usePoints
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                confirmButtonColor: '#6366f1'
                            }).then(() => {
                                window.location.href = res.redirect;
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Search produk
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const search = $(this).val().toLowerCase();
            let visibleCount = 0;
            
            $('.product-item').each(function() {
                const name = $(this).data('name');
                if (name && name.includes(search)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });
            
            // Tampilkan pesan jika tidak ada hasil
            if (visibleCount === 0 && search !== '') {
                if ($('#noResultMsg').length === 0) {
                    $('#productGrid').append(`
                        <div id="noResultMsg" class="col-12 text-center py-5">
                            <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                            <h5 class="fw-bold">Tidak ada menu</h5>
                            <p class="text-muted">Tidak ada menu dengan kata kunci "${search}"</p>
                        </div>
                    `);
                }
            } else {
                $('#noResultMsg').remove();
            }
        }, 300);
    });
</script>
@endsection