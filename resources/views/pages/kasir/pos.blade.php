@extends('layouts.kasir')

@section('title', 'Point of Sale')

@section('content')
<style>
    /* Layout Utama */
    .pos-container {
        display: flex;
        gap: 20px;
        height: calc(100vh - 120px);
        align-items: stretch;
    }
    
    /* Panel Produk */
    .pos-products {
        flex: 1;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #EAECF0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
        padding: 20px;
        overflow-y: auto;
    }
    
    .product-card {
        border: 1px solid #F2F4F7;
        border-radius: 12px;
        padding: 15px;
        cursor: pointer;
        transition: 0.2s;
        background: #fff;
        position: relative;
    }
    
    .product-card:hover {
        border-color: #F97316;
        box-shadow: 0 4px 12px rgba(249,115,22,0.1);
    }
    
    .product-card.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #f8f9fa;
    }

    /* Panel Keranjang */
    .pos-cart {
        width: 420px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #EAECF0;
        display: flex;
        flex-direction: column;
    }

    .cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        max-height: calc(100vh - 500px);
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        border-bottom: 1px solid #F2F4F7;
        transition: 0.2s;
    }
    
    .cart-item:hover {
        background: #FAFAFA;
    }
    
    .cart-item .item-name {
        font-weight: 600;
        font-size: 14px;
        color: #1F2937;
    }
    
    .cart-item .item-price {
        font-size: 12px;
        color: #6B7280;
        margin-top: 2px;
    }

    /* Promo Badge */
    .promo-badge {
        font-size: 11px;
        padding: 6px 12px;
        border-radius: 20px;
        background: #FFF7ED;
        color: #C2410C;
        border: 1px solid #FFEDD5;
        cursor: pointer;
        display: inline-block;
        margin: 4px;
        transition: all 0.2s;
        position: relative;
    }
    
    .promo-badge:hover:not(.disabled) {
        background: #FFEDD5;
        transform: translateY(-1px);
    }
    
    .promo-badge.active {
        background: #F97316;
        color: white;
        border-color: #F97316;
        box-shadow: 0 2px 4px rgba(249,115,22,0.3);
    }
    
    .promo-badge.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #E5E7EB;
        color: #6B7280;
        border-color: #D1D5DB;
    }
    
    .promo-badge.disabled:hover {
        transform: none;
    }
    
    .promo-requirement {
        font-size: 9px;
        display: block;
        margin-top: 2px;
        color: inherit;
    }
    
    .promo-badge.active .promo-requirement {
        color: rgba(255,255,255,0.8);
    }

    .payment-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 10px;
    }
    
    .pay-opt {
        padding: 10px;
        text-align: center;
        border: 1px solid #EAECF0;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .pay-opt:hover {
        border-color: #F97316;
        background: #FFF7ED;
    }
    
    .pay-opt.active {
        background: #101828;
        color: white;
        border-color: #101828;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .empty-cart {
        text-align: center;
        color: #9CA3AF;
        padding: 60px 20px;
    }
    
    /* Search input styling */
    #searchProduct {
        border-radius: 10px;
        border: 1px solid #EAECF0;
        padding: 10px 15px;
        font-size: 14px;
    }
    
    #searchProduct:focus {
        border-color: #F97316;
        box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        outline: none;
    }
    
    /* Scrollbar styling */
    .products-grid::-webkit-scrollbar,
    .cart-items::-webkit-scrollbar {
        width: 6px;
    }
    
    .products-grid::-webkit-scrollbar-track,
    .cart-items::-webkit-scrollbar-track {
        background: #F1F1F1;
        border-radius: 10px;
    }
    
    .products-grid::-webkit-scrollbar-thumb,
    .cart-items::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 10px;
    }
    
    /* Animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .product-card {
        animation: fadeIn 0.3s ease-out;
    }
    
    /* Progress bar untuk minimal pembelian */
    .promo-progress {
        margin-top: 8px;
        margin-bottom: 5px;
    }
    
    .progress {
        height: 4px;
        border-radius: 2px;
    }
    
    .progress-bar {
        background-color: #F97316;
        border-radius: 2px;
    }
</style>

<div class="container-fluid py-3">
    <div class="pos-container">
        <!-- Panel Produk -->
        <div class="pos-products">
            <div class="p-3 border-bottom bg-light">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchProduct" class="form-control border-start-0 ps-0" 
                           placeholder="Cari Nama Produk atau SKU...">
                </div>
            </div>
            <div class="products-grid" id="productsGrid">
                @forelse($products as $product)
                    @php 
                        $stock = $product->details ? $product->details->stock : 0;
                        $hasStock = $stock > 0;
                    @endphp
                    <div class="product-card {{ !$hasStock ? 'disabled' : '' }}" 
                         data-name="{{ strtolower($product->name) }}"
                         data-sku="{{ strtolower($product->sku ?? '') }}"
                         data-stock="{{ $stock }}"
                         data-price="{{ $product->price }}"
                         data-id="{{ $product->id }}"
                         onclick="{{ $hasStock ? "addToCart({$product->id}, '" . addslashes($product->name) . "', {$product->price}, {$stock})" : "showStockAlert('" . addslashes($product->name) . "')" }}">
                        
                        <div class="fw-bold text-dark product-name mb-1">{{ $product->name }}</div>
                        <div class="product-sku text-muted small" style="font-size: 10px;">
                            <i class="bi bi-upc-scan"></i> {{ $product->sku ?? 'No SKU' }}
                        </div>
                        
                        @if($product->details && $product->details->variant)
                            <div class="text-muted small mt-1" style="font-size: 10px;">
                                <i class="bi bi-tag"></i> {{ $product->details->variant }}
                            </div>
                        @endif
                        
                        <div class="mt-3">
                            <div class="text-orange fw-bold fs-5">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </div>
                            <small class="{{ !$hasStock ? 'text-danger fw-bold' : 'text-muted' }}">
                                <i class="bi bi-box-seam"></i> Stok: {{ number_format($stock) }}
                            </small>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
                        Tidak ada produk tersedia
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Panel Keranjang -->
        <div class="pos-cart">
            <div class="p-3 border-bottom bg-light">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-cart3 me-2"></i>Pesanan Baru
                </h6>
            </div>

            <div class="cart-items" id="cartItems">
                <div class="empty-cart">
                    <i class="bi bi-cart-x fs-1 d-block mb-3"></i>
                    Keranjang Kosong
                </div>
            </div>

            <!-- Bagian Member & Promo -->
            <div class="p-3 border-top">
                <label class="small fw-bold text-muted mb-1 d-block">
                    <i class="bi bi-person"></i> Pelanggan
                </label>
                <select id="customerId" class="form-select form-select-sm mb-3" onchange="updateCustomerContext()">
                    <option value="" data-type="walk_in" data-points="0" data-name="Guest">Guest (Umum)</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" 
                                data-type="{{ $customer->type }}" 
                                data-points="{{ $customer->total_points ?? 0 }}"
                                data-name="{{ $customer->name }}">
                            {{ $customer->name }} ({{ ucfirst($customer->type) }})
                            @if($customer->total_points > 0) - {{ number_format($customer->total_points) }} poin @endif
                        </option>
                    @endforeach
                </select>

                <div id="promoSection" class="mb-3">
                    <label class="small fw-bold text-muted d-block mb-2">
                        <i class="bi bi-tags"></i> Promo Tersedia:
                    </label>
                    <div id="promoContainer">
                        <span class="text-muted small" id="noPromoMessage">
                            <i class="bi bi-info-circle"></i> Tambahkan produk ke keranjang untuk melihat promo
                        </span>
                    </div>
                </div>

                <!-- Section Penggunaan Poin -->
                <div id="pointSection" class="bg-light p-3 rounded mb-3" style="display: none;">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="usePointsSwitch" onchange="calculateGrandTotal()">
                        <label class="form-check-label small fw-bold">
                            <i class="bi bi-gem"></i> Gunakan Poin 
                            (<span id="displayPoints">0</span> poin = Rp <span id="pointsValue">0</span>)
                        </label>
                    </div>
                </div>

                <label class="small fw-bold text-muted mb-2 d-block">
                    <i class="bi bi-credit-card"></i> Metode Pembayaran
                </label>
                <div class="payment-options">
                    <div class="pay-opt active" data-method="cash" onclick="setPayment('cash', this)">
                        <i class="bi bi-cash-stack"></i> Tunai
                    </div>
                    <div class="pay-opt" data-method="qris" onclick="setPayment('qris', this)">
                        <i class="bi bi-qr-code"></i> QRIS/Debit
                    </div>
                </div>
            </div>

            <!-- Footer Total dan Tombol Bayar -->
            <div class="p-3 border-top bg-light">
                <div class="summary-row">
                    <span>Subtotal</span> 
                    <span id="txtSubtotal" class="fw-bold">Rp 0</span>
                </div>
                <div class="summary-row">
                    <span>Pajak (11%)</span> 
                    <span id="txtTax">Rp 0</span>
                </div>
                <div class="summary-row text-danger">
                    <span>Potongan</span> 
                    <span id="txtDiscount">Rp 0</span>
                </div>
                <hr class="my-2">
                <div class="summary-row h5 fw-bold">
                    <span>Total</span> 
                    <span id="txtTotal" class="text-orange">Rp 0</span>
                </div>
                
                <button class="btn btn-warning w-100 fw-bold text-white py-2 mt-3" id="btnPay" onclick="processCheckout()" disabled>
                    <i class="bi bi-credit-card"></i> PROSES BAYAR
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let cart = [];
    let allPromos = [];
    let selectedPromo = null;
    let paymentMethod = 'cash';
    let currentCustomerPoints = 0;
    let currentCustomerId = '';
    let currentCustomerType = 'walk_in';

    // Fungsi untuk alert stok habis
    function showStockAlert(productName) {
        Swal.fire({
            icon: 'error',
            title: 'Stok Habis',
            text: `Produk "${productName}" sedang habis!`,
            timer: 1500,
            showConfirmButton: false
        });
    }

    // Fungsi untuk menambah ke keranjang
    function addToCart(id, name, price, stock) {
        if (stock <= 0) {
            showStockAlert(name);
            return;
        }
        
        let item = cart.find(i => i.id === id);
        if (item) {
            if (item.qty >= stock) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Melebihi Stok',
                    text: `Stok tersedia hanya ${stock.toLocaleString()} item!`,
                    timer: 1500,
                    showConfirmButton: false
                });
                return;
            }
            item.qty++;
        } else {
            cart.push({ id, name, price, qty: 1, stock });
        }
        renderCart();
        updateAvailablePromos(); // Update promo setelah keranjang berubah
    }

    // Render keranjang
    function renderCart() {
        const container = document.getElementById('cartItems');
        if (cart.length === 0) {
            container.innerHTML = `
                <div class="empty-cart">
                    <i class="bi bi-cart-x fs-1 d-block mb-3"></i>
                    Keranjang Kosong
                </div>
            `;
            document.getElementById('btnPay').disabled = true;
        } else {
            container.innerHTML = cart.map((item, index) => `
                <div class="cart-item">
                    <div class="flex-grow-1">
                        <div class="item-name">${escapeHtml(item.name)}</div>
                        <div class="item-price">Rp ${item.price.toLocaleString()}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle" 
                                onclick="updateQty(${index}, -1)" 
                                style="width: 32px; height: 32px; padding: 0;">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span class="small fw-bold mx-1" style="min-width: 30px; text-align: center;">${item.qty}</span>
                        <button class="btn btn-sm btn-outline-secondary rounded-circle" 
                                onclick="updateQty(${index}, 1)" 
                                style="width: 32px; height: 32px; padding: 0;">
                            <i class="bi bi-plus"></i>
                        </button>
                        <button class="btn btn-sm btn-danger rounded-circle" 
                                onclick="removeItem(${index})" 
                                style="width: 32px; height: 32px; padding: 0;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            document.getElementById('btnPay').disabled = false;
        }
        calculateGrandTotal();
    }
    
    // Escape HTML untuk keamanan
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Update quantity
    function updateQty(index, delta) {
        let newQty = cart[index].qty + delta;
        if (newQty <= 0) {
            cart.splice(index, 1);
        } else if (newQty <= cart[index].stock) {
            cart[index].qty = newQty;
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Melebihi Stok',
                text: `Stok tersedia hanya ${cart[index].stock.toLocaleString()} item!`,
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }
        renderCart();
        updateAvailablePromos(); // Update promo setelah keranjang berubah
    }

    // Remove item dari keranjang
    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
        updateAvailablePromos(); // Update promo setelah keranjang berubah
    }

    // Hitung subtotal
    function getSubtotal() {
        return cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    }

    // Update promo yang tersedia berdasarkan subtotal
    function updateAvailablePromos() {
        const subtotal = getSubtotal();
        const promoContainer = document.getElementById('promoContainer');
        
        // Filter promo yang memenuhi syarat
        const availablePromos = allPromos.filter(promo => {
            // Cek minimal pembelian
            const minSpend = parseFloat(promo.min_spend) || 0;
            if (subtotal < minSpend) return false;
            
            // Cek target segment
            const targetSegments = promo.target_segment ? promo.target_segment.split(',') : ['all'];
            if (!targetSegments.includes(currentCustomerType) && !targetSegments.includes('all')) return false;
            
            // Cek tanggal berlaku
            const now = new Date();
            const startDate = promo.start_date ? new Date(promo.start_date) : null;
            const endDate = promo.end_date ? new Date(promo.end_date) : null;
            
            if (startDate && now < startDate) return false;
            if (endDate && now > endDate) return false;
            
            return true;
        });
        
        // Render promo yang tersedia
        if (availablePromos.length === 0) {
            if (cart.length === 0) {
                promoContainer.innerHTML = `
                    <span class="text-muted small">
                        <i class="bi bi-info-circle"></i> Tambahkan produk ke keranjang untuk melihat promo
                    </span>
                `;
            } else {
                // Cari promo terdekat untuk ditampilkan progressnya
                const nextPromo = allPromos.filter(promo => {
                    const targetSegments = promo.target_segment ? promo.target_segment.split(',') : ['all'];
                    return (targetSegments.includes(currentCustomerType) || targetSegments.includes('all'));
                }).sort((a, b) => (a.min_spend || 0) - (b.min_spend || 0))
                  .find(promo => (promo.min_spend || 0) > subtotal);
                
                if (nextPromo) {
                    const remaining = (nextPromo.min_spend || 0) - subtotal;
                    promoContainer.innerHTML = `
                        <div class="text-muted small">
                            <i class="bi bi-tag"></i> Tambah Rp ${remaining.toLocaleString()} lagi untuk mendapatkan promo ${nextPromo.promo_name}
                            <div class="promo-progress">
                                <div class="progress">
                                    <div class="progress-bar" style="width: ${(subtotal / (nextPromo.min_spend || 1)) * 100}%"></div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    promoContainer.innerHTML = `
                        <span class="text-muted small">
                            <i class="bi bi-info-circle"></i> Belum ada promo yang memenuhi syarat
                        </span>
                    `;
                }
            }
        } else {
            promoContainer.innerHTML = availablePromos.map(promo => {
                const isSelected = selectedPromo && selectedPromo.promo_code === promo.promo_code;
                const minSpend = parseFloat(promo.min_spend) || 0;
                const isEligible = subtotal >= minSpend;
                
                return `
                    <div class="promo-badge ${!isEligible ? 'disabled' : ''} ${isSelected ? 'active' : ''}" 
                          id="promo-${promo.promo_code}"
                          data-code="${promo.promo_code}"
                          data-discount-type="${promo.discount_type}"
                          data-discount-value="${promo.discount_value}"
                          data-min-spend="${minSpend}"
                          onclick="${isEligible ? `applyPromo('${promo.promo_code}')` : ''}">
                        <strong>${promo.promo_name}</strong>
                        <span class="promo-requirement">
                            ${promo.discount_type === 'percentage' ? `${promo.discount_value}% OFF` : `Rp ${promo.discount_value.toLocaleString()} OFF`}
                            ${minSpend > 0 ? ` • Min. Rp ${minSpend.toLocaleString()}` : ''}
                        </span>
                        ${!isEligible ? `<span class="promo-requirement">Kurang Rp ${(minSpend - subtotal).toLocaleString()}</span>` : ''}
                    </div>
                `;
            }).join('');
        }
        
        // Reset selected promo jika tidak memenuhi syarat
        if (selectedPromo) {
            const isStillValid = availablePromos.some(p => p.promo_code === selectedPromo.promo_code);
            if (!isStillValid) {
                selectedPromo = null;
                calculateGrandTotal();
            }
        }
    }

    // Update customer context
    function updateCustomerContext() {
        const select = document.getElementById('customerId');
        const selectedOption = select.options[select.selectedIndex];
        currentCustomerType = selectedOption.getAttribute('data-type') || 'walk_in';
        currentCustomerPoints = parseInt(selectedOption.getAttribute('data-points')) || 0;
        currentCustomerId = select.value;

        // Tampilkan section poin jika member dan memiliki poin
        const pointSection = document.getElementById('pointSection');
        if (currentCustomerType === 'member' && currentCustomerPoints > 0) {
            pointSection.style.display = 'block';
            const pointsValue = currentCustomerPoints * 10;
            document.getElementById('displayPoints').innerText = currentCustomerPoints.toLocaleString();
            document.getElementById('pointsValue').innerText = pointsValue.toLocaleString();
        } else {
            pointSection.style.display = 'none';
            document.getElementById('usePointsSwitch').checked = false;
        }

        // Update promo berdasarkan tipe customer
        updateAvailablePromos();
        calculateGrandTotal();
    }

    // Apply promo
    function applyPromo(code) {
        const promo = allPromos.find(p => p.promo_code === code);
        const subtotal = getSubtotal();
        const minSpend = parseFloat(promo.min_spend) || 0;
        
        if (subtotal < minSpend) {
            Swal.fire({
                icon: 'warning',
                title: 'Syarat Promo Belum Terpenuhi',
                text: `Minimal belanja Rp ${minSpend.toLocaleString()} untuk menggunakan promo ini. Kurang Rp ${(minSpend - subtotal).toLocaleString()} lagi.`,
                confirmButtonColor: '#F97316'
            });
            return;
        }
        
        if (selectedPromo && selectedPromo.promo_code === code) {
            selectedPromo = null;
            Swal.fire({
                icon: 'info',
                title: 'Promo Dibatalkan',
                text: `Promo ${promo.promo_name} telah dibatalkan`,
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            selectedPromo = promo;
            Swal.fire({
                icon: 'success',
                title: 'Promo Diterapkan',
                text: `Promo ${promo.promo_name} berhasil diterapkan`,
                timer: 1500,
                showConfirmButton: false
            });
        }
        
        updateAvailablePromos();
        calculateGrandTotal();
    }

    // Hitung total keseluruhan
    function calculateGrandTotal() {
        let subtotal = getSubtotal();
        let tax = subtotal * 0.11;
        let discount = 0;

        // Hitung promo
        if (selectedPromo) {
            if (selectedPromo.discount_type === 'percentage') {
                discount = (subtotal * selectedPromo.discount_value) / 100;
            } else {
                discount = parseFloat(selectedPromo.discount_value);
            }
            discount = Math.min(discount, subtotal);
        }

        // Hitung poin
        const usePoints = document.getElementById('usePointsSwitch')?.checked || false;
        let pointsDiscount = 0;
        
        if (usePoints && currentCustomerPoints > 0) {
            pointsDiscount = currentCustomerPoints * 10;
            const maxPointsDiscount = Math.max(0, (subtotal + tax) - discount);
            pointsDiscount = Math.min(pointsDiscount, maxPointsDiscount);
        }

        const totalDiscount = discount + pointsDiscount;
        let total = (subtotal + tax) - totalDiscount;
        if (total < 0) total = 0;

        // Update display
        document.getElementById('txtSubtotal').innerHTML = "Rp " + subtotal.toLocaleString();
        document.getElementById('txtTax').innerHTML = "Rp " + tax.toLocaleString();
        document.getElementById('txtDiscount').innerHTML = "- Rp " + totalDiscount.toLocaleString();
        document.getElementById('txtTotal').innerHTML = "Rp " + total.toLocaleString();
    }

    // Set payment method
    function setPayment(method, el) {
        paymentMethod = method;
        document.querySelectorAll('.pay-opt').forEach(opt => opt.classList.remove('active'));
        el.classList.add('active');
    }

    // Process checkout
    async function processCheckout() {
        if (cart.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Keranjang Kosong',
                text: 'Silakan tambahkan produk terlebih dahulu!',
                confirmButtonColor: '#F97316'
            });
            return;
        }

        const btn = document.getElementById('btnPay');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';

        const customerId = document.getElementById('customerId').value;
        const usePoints = document.getElementById('usePointsSwitch')?.checked || false;

        const data = {
            _token: "{{ csrf_token() }}",
            customer_id: customerId || null,
            items: cart.map(i => ({ id: i.id, qty: i.qty })),
            payment_method: paymentMethod,
            promo_code: selectedPromo ? selectedPromo.promo_code : null,
            use_points: usePoints
        };

        try {
            const response = await fetch("{{ route('kasir.pos.store') }}", {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(data)
            });
            
            const res = await response.json();

            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Transaksi Berhasil!',
                    html: `Invoice: <strong>${res.invoice}</strong><br>Total: Rp ${res.total?.toLocaleString() || 0}`,
                    confirmButtonColor: '#F97316',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Transaksi Gagal',
                    text: res.message || 'Terjadi kesalahan pada server',
                    confirmButtonColor: '#F97316'
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (e) {
            console.error('Error:', e);
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Terjadi kesalahan, silakan coba lagi!',
                confirmButtonColor: '#F97316'
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    // Search produk
    let searchTimeout;
    document.getElementById('searchProduct').addEventListener('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = this.value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            let visibleCount = 0;
            
            products.forEach(product => {
                const name = product.getAttribute('data-name');
                const sku = product.getAttribute('data-sku');
                
                if (name.includes(searchTerm) || sku.includes(searchTerm)) {
                    product.style.display = 'block';
                    visibleCount++;
                } else {
                    product.style.display = 'none';
                }
            });
            
            const productsGrid = document.getElementById('productsGrid');
            const noResultMsg = document.getElementById('noSearchResult');
            
            if (visibleCount === 0 && searchTerm !== '') {
                if (!noResultMsg) {
                    const msg = document.createElement('div');
                    msg.id = 'noSearchResult';
                    msg.className = 'text-center text-muted py-5';
                    msg.innerHTML = `
                        <i class="bi bi-search fs-1 d-block mb-3"></i>
                        Tidak ada produk yang cocok dengan "${searchTerm}"
                    `;
                    productsGrid.appendChild(msg);
                }
            } else if (noResultMsg) {
                noResultMsg.remove();
            }
        }, 300);
    });

    // Inisialisasi saat halaman load
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil data promo dari server
        @if(isset($promos) && $promos->count() > 0)
            allPromos = @json($promos);
            console.log('Promos loaded:', allPromos.length, 'promos');
        @else
            console.log('No promos available');
        @endif
        
        updateCustomerContext();
        updateAvailablePromos();
    });
</script>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection