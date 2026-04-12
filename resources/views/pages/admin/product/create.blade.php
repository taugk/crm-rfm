@extends('layouts.admin')

@section('title', 'Tambah Produk')

@section('content')

<div class="page-heading">
    <h3>Tambah Produk Baru</h3>
</div>

<div class="page-content">
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            {{-- BAGIAN KIRI: Informasi Produk (Table: products) --}}
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5><i class="bi bi-info-circle me-2"></i>Informasi Utama</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">SKU</label>
                                <input type="text" name="sku" class="form-control" placeholder="Contoh: PROD-001" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Kategori</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="" disabled selected>Pilih Kategori</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold">Nama Produk</label>
                            <input type="text" name="name" class="form-control" placeholder="Nama lengkap produk" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Harga Jual (Price)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="price" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold">Foto Produk</label>
                            <input type="file" name="image" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            {{-- BAGIAN KANAN: Detail & Stok (Table: product_details) --}}
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white border-0 pb-0">
                        <h5><i class="bi bi-box-seam me-2"></i>Inventaris & Batch</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 mt-2">
                            <label class="form-label font-bold">Varian</label>
                            <input type="text" name="variant" class="form-control" placeholder="Contoh: Merah, XL, atau 256GB">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Stok Awal</label>
                                <input type="number" name="stock" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Harga Modal (Cost)</label>
                                <input type="number" name="cost_price" class="form-control" step="0.01">
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label font-bold text-success">Tanggal Masuk</label>
                            <input type="date" name="date_in" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold text-danger">Tanggal Kadaluarsa (Expired)</label>
                            <input type="date" name="expired_date" class="form-control">
                        </div>

                        <div class="mt-4 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg shadow">
                                <i class="bi bi-save me-2"></i>Simpan Produk
                            </button>
                            <a href="{{ route('admin.products') }}" class="btn btn-light mt-2">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection