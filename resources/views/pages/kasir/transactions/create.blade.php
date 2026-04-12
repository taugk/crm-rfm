@extends('layouts.admin')

@section('title', 'Kasir - Transaksi Cafe')

@push('styles')
<style>
    :root {
        --primary-color: #435ebe;
        --soft-bg: #f2f7ff;
        --border-color: #ebeef3;
    }
    body { background-color: var(--soft-bg); overflow-x: hidden; }
    
    /* Menu Area */
    .menu-container { height: calc(100vh - 180px); overflow-y: auto; padding-right: 10px; }
    .product-card { border: none; border-radius: 15px; transition: all 0.3s ease; background: #fff; overflow: hidden; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .product-image-placeholder { width: 100%; height: 130px; display: flex; align-items: center; justify-content: center; background-color: #fafafa; }
    .product-image-placeholder img { width: 100%; height: 100%; object-fit: cover; }
    
    /* Side Panel */
    .pos-side-panel { background: #fff; border-radius: 20px; height: calc(100vh - 40px); display: flex; flex-direction: column; box-shadow: 0 0 25px rgba(0,0,0,0.03); position: sticky; top: 20px; }
    
    /* Cart Area */
    .cart-items-area { flex: 1; overflow-y: auto; padding: 0 20px; }
    .cart-item-row { padding: 12px 0; border-bottom: 1px dashed var(--border-color); }
    .qty-control { background: var(--soft-bg); border-radius: 8px; display: inline-flex; align-items: center; padding: 2px; }
    .qty-btn { width: 28px; height: 28px; border-radius: 6px; border: none; background: #fff; color: var(--primary-color); font-weight: bold; transition: 0.2s; }
    .qty-btn:hover { background: var(--primary-color); color: #fff; }
    
    /* Promo Section */
    .promo-box { background: #f8faff; border: 2px dashed #d1dcf0; border-radius: 12px; padding: 15px; margin: 0 20px 15px 20px; }
    
    /* Member Search */
    #memberSearchResults { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1050; max-height: 250px; overflow-y: auto; display: none; border: 1px solid var(--border-color); }
    .member-item { padding: 12px 15px; border-bottom: 1px solid #f8f9fa; cursor: pointer; }
    .member-item:hover { background: var(--soft-bg); }
    
    /* Custom Toggle */
    .type-toggle { background: var(--soft-bg); border-radius: 12px; padding: 5px; display: flex; margin-bottom: 15px; }
    .type-toggle button { flex: 1; border: none; padding: 10px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; background: transparent; color: #777; transition: 0.3s; }
    .type-toggle button.active { background: #fff; color: var(--primary-color); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    
    .checkout-box { background: #fff; padding: 25px; border-top: 1px solid var(--border-color); border-radius: 0 0 20px 20px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="row g-4">
        {{-- BAGIAN KIRI: DAFTAR MENU --}}
        <div class="col-lg-8">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-0">POS Cafe</h3>
                    <p class="text-muted small mb-0">Waktu Server (WIB): {{ date('H:i') }}</p>
                </div>
                <div class="position-relative col-md-5">
                    <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchProduct" class="form-control border-0 shadow-sm ps-5 rounded-pill" placeholder="Cari menu..." style="height: 48px;">
                </div>
            </div>

            <div class="row g-3 menu-container" id="productGrid">
                @foreach($products->groupBy('product_id') as $productId => $details)
                    @php $baseProduct = $details->first()->product; @endphp
                    <div class="col-md-4 col-6 product-item" data-name="{{ strtolower($baseProduct->name) }}">
                        <div class="card product-card shadow-sm h-100">
                            <div class="product-image-placeholder">
                                @if($baseProduct->image)
                                    <img src="{{ asset('storage/' . $baseProduct->image) }}" alt="{{ $baseProduct->name }}">
                                @else
                                    <i class="bi bi-cup-straw fs-1 text-primary opacity-25"></i>
                                @endif
                            </div>
                            <div class="card-body p-3">
                                <h6 class="fw-bold text-dark text-truncate mb-2">{{ $baseProduct->name }}</h6>
                                <div class="variant-group">
                                    <select class="form-select form-select-sm border-0 bg-light mb-2" id="variant-select-{{ $productId }}">
                                        @foreach($details as $detail)
                                            <option value="{{ $detail->id }}" 
                                                    data-name="{{ $baseProduct->name }}" 
                                                    data-price="{{ $detail->product->price }}" 
                                                    data-variant="{{ $detail->variant ?? 'Regular' }}">
                                                {{ $detail->variant ?? 'Regular' }} - Rp {{ number_format($detail->product->price, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-primary btn-sm w-100 rounded-pill fw-bold" onclick="handleAddWithVariant({{ $productId }})">
                                        <i class="bi bi-plus-lg me-1"></i> Tambah
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- BAGIAN KANAN: SIDE PANEL KASIR --}}
        <div class="col-lg-4">
            <div class="pos-side-panel">
                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Pesanan Aktif</h5>
                        <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill" id="invoiceLabel">
                            {{ 'INV-' . date('ymd') . rand(100,999) }}
                        </span>
                    </div>

                    <div class="type-toggle">
                        <button type="button" id="btnMember" class="active" onclick="setCustomerType('member')">Member</button>
                        <button type="button" id="btnWalkin" onclick="setCustomerType('walkin')">Walk-in</button>
                    </div>

                    <div id="inputMember" class="position-relative">
                        <input type="text" id="memberSearch" class="form-control border-0 bg-light py-2 px-3" placeholder="Nama / No. HP Member..." style="border-radius: 12px;" autocomplete="off">
                        <input type="hidden" id="selectedCustomerId" value="1">
                        <div id="memberSearchResults">
                            @foreach($customers as $c)
                                @if($c->id != 1)
                                <div class="member-item" onclick="selectMember({{ $c->id }}, '{{ $c->name }}', '{{ $c->phone }}')" data-search="{{ strtolower($c->name) }} {{ $c->phone }}">
                                    <div class="fw-bold small text-dark">{{ $c->name }}</div>
                                    <small class="text-muted">{{ $c->phone ?? 'Tidak ada nomor' }}</small>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div id="inputWalkin" style="display: none;">
                        <input type="text" id="walkinName" class="form-control border-0 bg-light py-2 px-3" placeholder="Nama Pelanggan (Opsional)..." style="border-radius: 12px;">
                    </div>
                </div>

                {{-- AREA ITEM KERANJANG --}}
                <div class="cart-items-area mt-3" id="cartList">
                    <div class="text-center py-5">
                        <i class="bi bi-cart-dash text-muted opacity-25" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-2">Keranjang kosong</p>
                    </div>
                </div>

                

                {{-- AREA PROMO --}}
                <div class="promo-box shadow-sm">
                    <label class="fw-bold small mb-2 d-block"><i class="bi bi-ticket-perk-fill me-1 text-primary"></i> Gunakan Promo</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="promoCodeInput" class="form-control border-0" placeholder="Kode Promo..." style="background: #fff;">
                        <button class="btn btn-primary fw-bold" type="button" id="btnApplyPromo">Cek</button>
                    </div>
                    <div id="promoInfoArea" class="mt-2 d-none">
                        <div class="alert alert-success p-2 mb-0 d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                            <span id="txtPromoName" class="fw-bold"></span>
                            <button type="button" class="btn-close" id="btnCancelPromo" style="padding: 0.2rem;"></button>
                        </div>
                    </div>
                </div>

                {{-- RINCIAN PEMBAYARAN --}}
                <div class="checkout-box">
                    <div class="d-flex justify-content-between mb-2 text-muted small">
                        <span>Subtotal</span>
                        <span id="txtSubtotal">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-danger small d-none" id="rowDiscount">
                        <span>Diskon Promo</span>
                        <span id="txtDiscount">- Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 text-muted small">
                        <span>Pajak (11%)</span>
                        <span id="txtTax">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4 border-top pt-3">
                        <h5 class="fw-bold mb-0">Total Akhir</h5>
                        <h4 class="fw-bold text-primary mb-0" id="txtTotal">Rp 0</h4>
                    </div>
                

                    <div class="row g-2">
                        <div class="col-5">
                            <select id="paymentMethod" class="form-select border-0 bg-light py-2 h-100" style="border-radius: 12px;">
                                <option value="cash">Tunai</option>
                                <option value="qris">QRIS</option>
                                <option value="bank">Transfer</option>
                            </select>
                        </div>
                        <div class="col-7">
                            <button class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm" id="btnProses">
                                PROSES BAYAR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- FORM TERSEMBUNYI UNTUK SUBMIT --}}
<form id="finalForm" action="{{ route('kasir.store.transaction') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="invoice_number" id="f-invoice">
    <input type="hidden" name="customer_id" id="f-customer">
    <input type="hidden" name="walkin_name" id="f-walkin">
    <input type="hidden" name="payment_method" id="f-method">
    <input type="hidden" name="subtotal" id="f-subtotal">
    <input type="hidden" name="promotion_id" id="f-promo-id">
    <input type="hidden" name="discount_amount" id="f-discount">
    <input type="hidden" name="tax_total" id="f-tax">
    <input type="hidden" name="total_price" id="f-total">
    <div id="hiddenItemsArea"></div>
</form>
@endsection

@push('scripts')
<script>
    let cart = [];
    let appliedPromo = null;
    const formatRupiah = (v) => "Rp " + Math.round(v).toLocaleString('id-ID');

    // 1. TAMBAH KE KERANJANG
    function handleAddWithVariant(productId) {
        const select = document.getElementById(`variant-select-${productId}`);
        const opt = select.options[select.selectedIndex];
        
        const data = {
            id: parseInt(opt.value),
            name: opt.dataset.name,
            price: parseFloat(opt.dataset.price),
            variant: opt.dataset.variant,
            qty: 1
        };

        const index = cart.findIndex(item => item.id === data.id);
        if (index > -1) {
            cart[index].qty++;
        } else {
            cart.push(data);
        }
        renderCart();
    }

    // 2. LOGIKA PROMO
    document.getElementById('btnApplyPromo').addEventListener('click', async function() {
        const code = document.getElementById('promoCodeInput').value;
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

        if (cart.length === 0) return alert('Keranjang masih kosong!');
        if (!code) return;

        try {
            // Gantilah endpoint ini dengan route pengecekan promo Anda
            const response = await fetch(`/kasir/promotions/check?code=${code}`);
            const result = await response.json();

            if (result.status === 'error') {
                alert(result.message);
                return;
            }

            const promo = result.data;

            // Syarat Minimal Belanja
            if (subtotal < promo.min_spend) {
                alert(`Promo ini butuh belanja min. ${formatRupiah(promo.min_spend)}`);
                return;
            }

            appliedPromo = promo;
            document.getElementById('promoCodeInput').value = '';
            renderPromoInfo();
            updateSummary();
        } catch (error) {
            alert('Gagal menghubungkan ke server.');
        }
    });

    function renderPromoInfo() {
        const area = document.getElementById('promoInfoArea');
        if (appliedPromo) {
            area.classList.remove('d-none');
            document.getElementById('txtPromoName').innerText = appliedPromo.promo_name;
        } else {
            area.classList.add('d-none');
        }
    }

    document.getElementById('btnCancelPromo').addEventListener('click', () => {
        appliedPromo = null;
        renderPromoInfo();
        updateSummary();
    });

    // 3. RENDER KERANJANG & SUMMARY
    function renderCart() {
        const container = document.getElementById('cartList');
        if (cart.length === 0) {
            container.innerHTML = `<div class="text-center py-5"><i class="bi bi-cart-dash text-muted opacity-25" style="font-size: 4rem;"></i><p class="text-muted mt-2">Keranjang kosong</p></div>`;
            appliedPromo = null;
            renderPromoInfo();
            updateSummary();
            return;
        }

        container.innerHTML = cart.map((item, i) => `
            <div class="cart-item-row d-flex justify-content-between align-items-center">
                <div style="width: 50%">
                    <div class="fw-bold text-dark text-truncate small">${item.name}</div>
                    <small class="text-primary" style="font-size: 0.7rem;">${item.variant} • ${formatRupiah(item.price)}</small>
                </div>
                <div class="qty-control">
                    <button class="qty-btn" onclick="updateQty(${i}, -1)">-</button>
                    <span class="px-2 fw-bold small">${item.qty}</span>
                    <button class="qty-btn" onclick="updateQty(${i}, 1)">+</button>
                </div>
                <div class="text-end fw-bold text-dark small" style="width: 25%">
                    ${formatRupiah(item.price * item.qty)}
                </div>
            </div>
        `).join('');
        
        updateSummary();
    }

    function updateQty(index, change) {
        cart[index].qty += change;
        if (cart[index].qty <= 0) cart.splice(index, 1);
        renderCart();
    }

    function updateSummary() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        let discount = 0;

        // Hitung Diskon Promo
        if (appliedPromo) {
            if (subtotal < appliedPromo.min_spend) {
                appliedPromo = null;
                renderPromoInfo();
                alert('Promo dicopot karena belanja kurang dari syarat minimal.');
            } else {
                if (appliedPromo.discount_type === 'percentage') {
                    discount = subtotal * (appliedPromo.discount_value / 100);
                } else {
                    discount = appliedPromo.discount_value;
                }
            }
        }

        const subtotalAfterDiscount = subtotal - discount;
        const tax = subtotalAfterDiscount * 0.11;
        const total = subtotalAfterDiscount + tax;

        // UI Update
        document.getElementById('txtSubtotal').innerText = formatRupiah(subtotal);
        document.getElementById('txtTax').innerText = formatRupiah(tax);
        document.getElementById('txtTotal').innerText = formatRupiah(total);

        const rowDiscount = document.getElementById('rowDiscount');
        if (discount > 0) {
            rowDiscount.classList.remove('d-none');
            document.getElementById('txtDiscount').innerText = "- " + formatRupiah(discount);
        } else {
            rowDiscount.classList.add('d-none');
        }

        // Hidden Inputs Sync
        document.getElementById('f-subtotal').value = Math.round(subtotal);
        document.getElementById('f-discount').value = Math.round(discount);
        document.getElementById('f-tax').value = Math.round(tax);
        document.getElementById('f-total').value = Math.round(total);
        document.getElementById('f-promo-id').value = appliedPromo ? appliedPromo.id : '';
    }

    // 4. MEMBER & PELANGGAN
    function setCustomerType(type) {
        document.getElementById('btnMember').classList.toggle('active', type === 'member');
        document.getElementById('btnWalkin').classList.toggle('active', type === 'walkin');
        document.getElementById('inputMember').style.display = type === 'member' ? 'block' : 'none';
        document.getElementById('inputWalkin').style.display = type === 'walkin' ? 'block' : 'none';
        if (type === 'walkin') document.getElementById('selectedCustomerId').value = 1;
    }

    const memberInput = document.getElementById('memberSearch');
    const memberBox = document.getElementById('memberSearchResults');
    
    memberInput.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        memberBox.style.display = q ? 'block' : 'none';
        document.querySelectorAll('.member-item').forEach(el => {
            el.style.display = el.dataset.search.includes(q) ? 'block' : 'none';
        });
    });

    function selectMember(id, name, phone) {
        document.getElementById('selectedCustomerId').value = id;
        memberInput.value = `${name} (${phone || 'No HP'})`;
        memberBox.style.display = 'none';
    }

    // 5. SEARCH PRODUCT UI
    document.getElementById('searchProduct').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach(el => {
            el.style.display = el.dataset.name.includes(q) ? 'block' : 'none';
        });
    });

    // 6. PROSES SUBMIT
    document.getElementById('btnProses').addEventListener('click', function() {
        if (cart.length === 0) return alert('Pilih menu dulu bos!');

        document.getElementById('f-invoice').value = document.getElementById('invoiceLabel').innerText.trim();
        document.getElementById('f-customer').value = document.getElementById('selectedCustomerId').value;
        document.getElementById('f-walkin').value = document.getElementById('walkinName').value;
        document.getElementById('f-method').value = document.getElementById('paymentMethod').value;

        const area = document.getElementById('hiddenItemsArea');
        area.innerHTML = '';
        cart.forEach((item, i) => {
            area.innerHTML += `
                <input type="hidden" name="items[${i}][product_detail_id]" value="${item.id}">
                <input type="hidden" name="items[${i}][qty]" value="${item.qty}">
                <input type="hidden" name="items[${i}][price]" value="${item.price}">
            `;
        });

        if(confirm('Konfirmasi pembayaran sekarang?')) {
            document.getElementById('finalForm').submit();
        }
    });
</script>
@endpush