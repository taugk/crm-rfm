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
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
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
        width: 400px;
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
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #F2F4F7;
    }
    
    .cart-item .item-name {
        font-weight: 600;
        font-size: 14px;
    }
    
    .cart-item .item-price {
        font-size: 12px;
        color: #666;
    }

    /* Promo Badge */
    .promo-badge {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 20px;
        background: #FFF7ED;
        color: #C2410C;
        border: 1px solid #FFEDD5;
        cursor: pointer;
        display: inline-block;
        margin: 2px;
        transition: all 0.2s;
    }
    
    .promo-badge:hover {
        background: #FFEDD5;
    }
    
    .promo-badge.active {
        background: #F97316;
        color: white;
        border-color: #F97316;
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
    }
    
    .pay-opt.active {
        background: #101828;
        color: white;
        border-color: #101828;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
        font-size: 14px;
    }
    
    .empty-cart {
        text-align: center;
        color: #999;
        padding: 40px 20px;
    }
    
    /* Search input styling */
    #searchProduct {
        border-radius: 8px;
        border: 1px solid #EAECF0;
        padding: 8px 12px;
    }
    
    #searchProduct:focus {
        border-color: #F97316;
        box-shadow: 0 0 0 2px rgba(249,115,22,0.1);
    }
</style>

<div class="container-fluid py-3">
    <div class="pos-container">
        <!-- Panel Produk -->
        <div class="pos-products">
            <div class="p-3 border-bottom">
                <input type="text" id="searchProduct" class="form-control" placeholder="Cari Nama Produk atau SKU...">
            </div>
            <div class="products-grid" id="productsGrid">
                @forelse($products as $product)
                    @php 
                        $stock = $product->details?->sum('stock') ?? 0;
                        $hasStock = $stock > 0;
                    @endphp
                    <div class="product-card {{ !$hasStock ? 'disabled' : '' }}" 
                         data-name="{{ strtolower($product->name) }}"
                         data-sku="{{ strtolower($product->sku ?? '') }}"
                         data-stock="{{ $stock }}"
                         onclick="{{ $hasStock ? "addToCart({$product->id}, '" . addslashes($product->name) . "', {$product->price}, {$stock})" : "" }}">
                        
                        <div class="fw-bold text-dark product-name">{{ $product->name }}</div>
                        <div class="product-sku text-muted small" style="font-size: 10px;">{{ $product->sku ?? 'No SKU' }}</div>
                        
                        <div class="mt-2">
                            <div class="text-orange fw-bold">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                            <small class="text-muted {{ $stock <= 0 ? 'text-danger fw-bold' : '' }}">
                                <i class="bi bi-box-seam"></i> Stok: {{ $stock }}
                            </small>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">Tidak ada produk tersedia</div>
                @endforelse
            </div>
        </div>

        <!-- Panel Keranjang -->
        <div class="pos-cart">
            <div class="p-3 border-bottom bg-light">
                <h6 class="fw-bold mb-0">Pesanan Baru</h6>
            </div>

            <div class="cart-items" id="cartItems">
                <div class="empty-cart">Keranjang Kosong</div>
            </div>

            <!-- Bagian Member & Promo -->
            <div class="p-3 border-top">
                <label class="small fw-bold text-muted">Pelanggan</label>
                <select id="customerId" class="form-select form-select-sm mb-2" onchange="updateCustomerContext()">
                    <option value="" data-type="walk_in" data-points="0" data-name="Guest">Guest (Umum)</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" 
                                data-type="{{ $customer->type }}" 
                                data-points="{{ $customer->total_points ?? 0 }}"
                                data-name="{{ $customer->name }}">
                            {{ $customer->name }} ({{ ucfirst($customer->type) }})
                        </option>
                    @endforeach
                </select>

                <div id="promoSection" class="mb-2">
                    <label class="small fw-bold text-muted d-block">Promo Tersedia:</label>
                    <div id="promoContainer">
                        @forelse($promos ?? [] as $promo)
                            <span class="promo-badge" 
                                  id="promo-{{ $promo->promo_code }}"
                                  data-code="{{ $promo->promo_code }}"
                                  data-target="{{ $promo->target_segment ?? 'all' }}"
                                  data-discount-type="{{ $promo->discount_type }}"
                                  data-discount-value="{{ $promo->discount_value }}"
                                  onclick="applyPromo('{{ $promo->promo_code }}')">
                                {{ $promo->promo_name }} ({{ $promo->discount_type == 'percentage' ? $promo->discount_value.'%' : 'Rp '.number_format($promo->discount_value, 0, ',', '.') }})
                            </span>
                        @empty
                            <span class="text-muted small">Tidak ada promo aktif</span>
                        @endforelse
                    </div>
                </div>

                <div id="pointSection" class="bg-light p-2 rounded mb-2" style="display: none;">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="usePointsSwitch" onchange="calculateGrandTotal()">
                        <label class="form-check-label small fw-bold">
                            Gunakan Poin (<span id="displayPoints">0</span> poin = Rp <span id="pointsValue">0</span>)
                        </label>
                    </div>
                </div>

                <label class="small fw-bold text-muted">Metode Pembayaran</label>
                <div class="payment-options">
                    <div class="pay-opt active" data-method="cash" onclick="setPayment('cash', this)">Tunai</div>
                    <div class="pay-opt" data-method="qris" onclick="setPayment('qris', this)">QRIS/Debit</div>
                </div>
            </div>

            <div class="p-3 border-top bg-light">
                <div class="summary-row"><span>Subtotal</span> <span id="txtSubtotal">Rp 0</span></div>
                <div class="summary-row"><span>Pajak (11%)</span> <span id="txtTax">Rp 0</span></div>
                <div class="summary-row text-danger fw-bold"><span>Potongan</span> <span id="txtDiscount">Rp 0</span></div>
                <hr>
                <div class="summary-row h5 fw-bold"><span>Total</span> <span id="txtTotal">Rp 0</span></div>
                
                <button class="btn btn-warning w-100 fw-bold text-white py-2 mt-2" id="btnPay" onclick="processCheckout()" disabled>
                    <i class="bi bi-credit-card"></i> PROSES BAYAR
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let cart = [];
    let allPromos = @json($promos ?? []);
    let selectedPromo = null;
    let paymentMethod = 'cash';
    let currentCustomerPoints = 0;

    // Fungsi untuk menambah ke keranjang
    function addToCart(id, name, price, stock) {
        if (stock <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Stok Habis',
                text: 'Stok produk ini habis!',
                timer: 1500
            });
            return;
        }
        
        let item = cart.find(i => i.id === id);
        if (item) {
            if (item.qty >= stock) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Melebihi Stok',
                    text: `Stok tersedia hanya ${stock} item!`,
                    timer: 1500
                });
                return;
            }
            item.qty++;
        } else {
            cart.push({ id, name, price, qty: 1, stock });
        }
        renderCart();
    }

    // Render keranjang
    function renderCart() {
        const container = document.getElementById('cartItems');
        if (cart.length === 0) {
            container.innerHTML = '<div class="empty-cart">Keranjang Kosong</div>';
            document.getElementById('btnPay').disabled = true;
        } else {
            container.innerHTML = cart.map((item, index) => `
                <div class="cart-item">
                    <div>
                        <div class="item-name">${item.name}</div>
                        <div class="item-price">Rp ${item.price.toLocaleString()}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${index}, -1)">-</button>
                        <span class="small fw-bold">${item.qty}</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${index}, 1)">+</button>
                        <button class="btn btn-sm btn-danger" onclick="removeItem(${index})">×</button>
                    </div>
                </div>
            `).join('');
            document.getElementById('btnPay').disabled = false;
        }
        calculateGrandTotal();
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
                text: `Stok tersedia hanya ${cart[index].stock} item!`,
                timer: 1500
            });
            return;
        }
        renderCart();
    }

    // Remove item dari keranjang
    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    // Update customer context (promo & poin)
    function updateCustomerContext() {
        const select = document.getElementById('customerId');
        const selectedOption = select.options[select.selectedIndex];
        const type = selectedOption.getAttribute('data-type');
        const points = parseInt(selectedOption.getAttribute('data-points')) || 0;
        
        currentCustomerPoints = points;

        // Tampilkan promo berdasarkan tipe customer
        document.querySelectorAll('.promo-badge').forEach(badge => {
            const target = badge.getAttribute('data-target');
            const targetSegment = target ? target.split(',') : ['all'];
            
            if (targetSegment.includes(type) || targetSegment.includes('all')) {
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
                // Hapus promo yang tidak valid
                if (selectedPromo && selectedPromo.promo_code === badge.getAttribute('data-code')) {
                    selectedPromo = null;
                }
            }
        });

        // Tampilkan section poin jika member dan memiliki poin
        const pointSection = document.getElementById('pointSection');
        if (type === 'member' && points > 0) {
            pointSection.style.display = 'block';
            const pointsValue = points * 10; // 1 poin = Rp 10 (sesuaikan)
            document.getElementById('displayPoints').innerText = points.toLocaleString();
            document.getElementById('pointsValue').innerText = pointsValue.toLocaleString();
        } else {
            pointSection.style.display = 'none';
            document.getElementById('usePointsSwitch').checked = false;
        }

        // Hapus selected promo jika tidak sesuai
        if (selectedPromo) {
            const promoBadge = document.getElementById(`promo-${selectedPromo.promo_code}`);
            if (promoBadge && promoBadge.style.display === 'none') {
                selectedPromo = null;
            }
        }

        calculateGrandTotal();
    }

    // Apply promo
    function applyPromo(code) {
        const promo = allPromos.find(p => p.promo_code === code);
        
        if (selectedPromo && selectedPromo.promo_code === code) {
            selectedPromo = null;
        } else {
            selectedPromo = promo;
        }
        
        document.querySelectorAll('.promo-badge').forEach(b => b.classList.remove('active'));
        if (selectedPromo) {
            const activeBadge = document.getElementById(`promo-${selectedPromo.promo_code}`);
            if (activeBadge) activeBadge.classList.add('active');
        }
        
        calculateGrandTotal();
    }

    // Hitung total keseluruhan
    function calculateGrandTotal() {
        let subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
        let tax = subtotal * 0.11;
        let discount = 0;

        // Hitung promo
        if (selectedPromo) {
            if (selectedPromo.discount_type === 'percentage') {
                discount = (subtotal * selectedPromo.discount_value) / 100;
            } else {
                discount = parseFloat(selectedPromo.discount_value);
            }
            discount = Math.min(discount, subtotal); // Tidak melebihi subtotal
        }

        // Hitung poin
        const usePoints = document.getElementById('usePointsSwitch')?.checked || false;
        let pointsDiscount = 0;
        
        if (usePoints && currentCustomerPoints > 0) {
            pointsDiscount = currentCustomerPoints * 10; // 1 poin = Rp 10
            const maxPointsDiscount = Math.max(0, (subtotal + tax) - discount);
            pointsDiscount = Math.min(pointsDiscount, maxPointsDiscount);
        }

        const totalDiscount = discount + pointsDiscount;
        let total = (subtotal + tax) - totalDiscount;
        if (total < 0) total = 0;

        // Update display
        document.getElementById('txtSubtotal').innerText = "Rp " + subtotal.toLocaleString();
        document.getElementById('txtTax').innerText = "Rp " + tax.toLocaleString();
        document.getElementById('txtDiscount').innerText = "- Rp " + totalDiscount.toLocaleString();
        document.getElementById('txtTotal').innerText = "Rp " + total.toLocaleString();
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
                text: 'Silakan tambahkan produk terlebih dahulu!'
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
                    text: 'Invoice: ' + res.invoice,
                    confirmButtonColor: '#F97316'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Transaksi Gagal',
                    text: res.message || 'Terjadi kesalahan pada server'
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (e) {
            console.error('Error:', e);
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Terjadi kesalahan, silakan coba lagi!'
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    // Search produk
    document.getElementById('searchProduct').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const products = document.querySelectorAll('.product-card');
        
        products.forEach(product => {
            const name = product.getAttribute('data-name');
            const sku = product.getAttribute('data-sku');
            
            if (name.includes(searchTerm) || sku.includes(searchTerm)) {
                product.style.display = 'block';
            } else {
                product.style.display = 'none';
            }
        });
    });

    // Inisialisasi saat halaman load
    document.addEventListener('DOMContentLoaded', function() {
        updateCustomerContext();
    });
</script>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection