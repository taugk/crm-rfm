@extends('layouts.admin')

@section('title', 'Tambah Hadiah Poin')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Tambah Hadiah Baru</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first text-end">
                <a href="{{ route('admin.loyalty.rewards') }}" class="btn btn-light-secondary shadow-sm">Kembali</a>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('admin.loyalty.rewards.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Nama Hadiah</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Mug Cantik" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipe Hadiah</label>
                        <select name="reward_type" id="reward_type" class="form-select" required>
                            <option value="product">Produk Fisik (Barang)</option>
                            <option value="voucher">Voucher (Diskon/Nominal)</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Poin yang Dibutuhkan</label>
                        <input type="number" name="points_required" class="form-control" placeholder="0" required>
                    </div>

                    {{-- Dinamis: Muncul jika tipe Produk --}}
                    <div class="col-md-6 mb-3" id="stock_field">
                        <label class="form-label fw-bold">Stok</label>
                        <input type="number" name="stock" class="form-control" value="0">
                    </div>

                    {{-- Dinamis: Muncul jika tipe Voucher --}}
                    <div class="col-md-6 mb-3" id="amount_field" style="display:none;">
                        <label class="form-label fw-bold">Nilai Voucher (Rp)</label>
                        <input type="number" name="value_amount" class="form-control" placeholder="0">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary px-4">Simpan Hadiah</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('reward_type').addEventListener('change', function() {
        document.getElementById('stock_field').style.display = (this.value === 'product') ? 'block' : 'none';
        document.getElementById('amount_field').style.display = (this.value === 'voucher') ? 'block' : 'none';
    });
</script>
@endpush