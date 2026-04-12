@extends('layouts.admin')

@section('title', 'Edit Produk')

@section('content')

<div class="page-heading">
    <h3>Edit Produk: {{ $product->name }}</h3>
</div>

<div class="page-content">
    {{-- Pastikan action diarahkan ke route update dengan ID produk --}}
    <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- BAGIAN KIRI: Informasi Produk --}}
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5><i class="bi bi-pencil-square me-2"></i>Informasi Utama</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">SKU</label>
                                <input type="text" name="sku" class="form-control" value="{{ $product->sku }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Kategori</label>
                                <select name="category_id" class="form-select" required>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" {{ $product->category_id == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold">Nama Produk</label>
                            <input type="text" name="name" class="form-control" value="{{ $product->name }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Harga Jual (Price)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="price" class="form-control" step="0.01" value="{{ $product->price }}" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" {{ $product->status == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ $product->status == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="4">{{ $product->description }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold">Foto Produk</label>
                            @if($product->image)
                                <div class="mb-2">
                                    <small class="text-muted d-block mb-1">Foto saat ini:</small>
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="Preview" class="img-thumbnail" style="height: 100px;">
                                </div>
                            @endif
                            <input type="file" name="image" class="form-control">
                            <small class="text-muted italic">*Kosongkan jika tidak ingin mengubah foto</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BAGIAN KANAN: Detail & Stok (Relasi product_details) --}}
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white border-0 pb-0">
                        <h5><i class="bi bi-box-seam me-2"></i>Inventaris & Batch</h5>
                    </div>
                    <div class="card-body">
                        {{-- Asumsi data detail diakses melalui $product->detail --}}
                        <div class="mb-3 mt-2">
                            <label class="form-label font-bold">Varian</label>
                            <input type="text" name="variant" class="form-control" value="{{ $product->details->variant ?? '' }}">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Stok</label>
                                <input type="number" name="stock" class="form-control" value="{{ $product->details->stock ?? 0 }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">Harga Modal (Cost)</label>
                                <input type="number" name="cost_price" class="form-control" step="0.01" value="{{ $product->details->cost_price ?? '' }}">
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label font-bold text-success">Tanggal Masuk</label>
                            <input type="date" name="date_in" class="form-control" value="{{ $product->details->date_in ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold text-danger">Tanggal Kadaluarsa (Expired)</label>
                            <input type="date" name="expired_date" class="form-control" value="{{ $product->details->expired_date ?? '' }}">
                        </div>

                        <div class="mt-4 d-grid">
                            <button type="submit" class="btn btn-warning btn-lg shadow edit-confirm">
                                <i class="bi bi-arrow-repeat me-2"></i>Perbarui Produk
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