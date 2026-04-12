@extends('layouts.kasir')

@section('title', 'Kasir')

@section('content')

<div class="page-heading">
    <h3>Halaman Kasir</h3>
</div>

<div class="page-content">
    <div class="row">

        <!-- LIST PRODUK -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Pilih Produk</h4>
                </div>
                <div class="card-body">

                    <div class="row">
                        @foreach($produk ?? [] as $item)
                        <div class="col-6 col-md-4 mb-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h6>{{ $item->nama }}</h6>
                                    <p>Rp {{ number_format($item->harga, 0, ',', '.') }}</p>
                                    <button 
                                        class="btn btn-primary btn-sm"
                                        onclick="tambahItem({{ $item->id }}, '{{ $item->nama }}', {{ $item->harga }})">
                                        Tambah
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>

        <!-- KERANJANG -->
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Keranjang</h4>
                </div>
                <div class="card-body">

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="keranjang"></tbody>
                    </table>

                    <hr>

                    <h5>Total: Rp <span id="total">0</span></h5>

                    <div class="mt-3">
                        <label>Bayar</label>
                        <input type="number" id="bayar" class="form-control">
                    </div>

                    <div class="mt-2">
                        <h6>Kembalian: Rp <span id="kembalian">0</span></h6>
                    </div>

                    <button class="btn btn-success w-100 mt-3" onclick="checkout()">
                        Proses Transaksi
                    </button>

                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@section('scripts')
<script>
    let keranjang = [];

    function tambahItem(id, nama, harga) {
        let item = keranjang.find(i => i.id === id);

        if (item) {
            item.qty += 1;
        } else {
            keranjang.push({ id, nama, harga, qty: 1 });
        }

        renderKeranjang();
    }

    function renderKeranjang() {
        let html = '';
        let total = 0;

        keranjang.forEach(item => {
            let subtotal = item.qty * item.harga;
            total += subtotal;

            html += `
                <tr>
                    <td>${item.nama}</td>
                    <td>${item.qty}</td>
                    <td>Rp ${subtotal.toLocaleString()}</td>
                </tr>
            `;
        });

        document.getElementById('keranjang').innerHTML = html;
        document.getElementById('total').innerText = total.toLocaleString();

        hitungKembalian();
    }

    document.getElementById('bayar').addEventListener('input', hitungKembalian);

    function hitungKembalian() {
        let total = keranjang.reduce((sum, i) => sum + (i.qty * i.harga), 0);
        let bayar = parseInt(document.getElementById('bayar').value) || 0;

        let kembalian = bayar - total;

        document.getElementById('kembalian').innerText = kembalian > 0 
            ? kembalian.toLocaleString() 
            : 0;
    }

    function checkout() {
        if (keranjang.length === 0) {
            alert('Keranjang kosong!');
            return;
        }

        alert('Transaksi berhasil! (simulasi)');
        
        keranjang = [];
        renderKeranjang();
        document.getElementById('bayar').value = '';
    }
</script>
@endsection