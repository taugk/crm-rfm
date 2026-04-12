@extends('layouts.admin')

@section('title', 'Detail Produk')

@section('content')

<div class="page-heading d-flex justify-content-between align-items-center">
    <h3>Detail Produk: {{ $product->name }}</h3>
    <a href="{{ route('admin.products') }}" class="btn btn-secondary shadow-sm">
        <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
</div>

<div class="page-content">
    <div class="row">
        {{-- BAGIAN KIRI: Foto & Status --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center">
                   <div class="mb-4 text-center">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" 
                                alt="{{ $product->name }}" 
                                class="img-fluid rounded shadow-sm border" 
                                style="max-height: 300px; width: 100%; object-fit: contain; background-color: #f8f9fa;">
                        @else
                            <div class="bg-light rounded d-flex flex-column align-items-center justify-content-center border" 
                                style="height: 250px;">
                                <i class="bi bi-image text-muted" style="font-size: 4rem;"></i>
                                <span class="text-muted mt-2 small">Tidak ada gambar produk</span>
                            </div>
                        @endif
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center px-3">
                        <span class="text-muted">Status Produk:</span>
                        @if($product->status == 'active')
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Tidak Aktif</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0"><i class="bi bi-tag me-2"></i>Informasi Harga</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted d-block small">Harga Jual</label>
                        <h3 class="text-primary font-bold">Rp {{ number_format($product->price, 0, ',', '.') }}</h3>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted d-block small">Harga Modal (Cost)</label>
                        {{-- MENGGUNAKAN ?? UNTUK CEK NULL --}}
                        <h5 class="text-muted">Rp {{ number_format($product->details->first()->cost_price ?? 0, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN KANAN: Informasi Detail --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between">
                    <h5><i class="bi bi-info-circle me-2"></i>Informasi Lengkap</h5>
                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-striped mt-3">
                        <tr>
                            <th width="30%">SKU</th>
                            <td><code>{{ $product->sku }}</code></td>
                        </tr>
                        <tr>
                            <th>Kategori</th>
                            <td><span class="badge bg-info text-dark">{{ $product->category->name ?? 'Tanpa Kategori' }}</span></td>
                        </tr>
                        <tr>
                            <th>Varian</th>
                            {{-- MENGGUNAKAN ?? UNTUK CEK NULL --}}
                            <td>{{ $product->details->variant ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Stok Tersedia</th>
                            <td>
                                {{-- CEK APAKAH DETAIL ADA DULU --}}
                                @if($product->details)
                                    @if($product->details->stock <= 5)
                                        <span class="text-danger font-bold">{{ $product->details->stock }} (Stok Menipis)</span>
                                    @else
                                        <span class="text-success font-bold">{{ $product->details->stock }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Tanggal Masuk</th>
                            <td>
                                {{-- PERBAIKAN: CEK DETAIL DAN PROPERTI --}}
                                {{ ($product->details && $product->details->date_in) ? \Carbon\Carbon::parse($product->details->date_in)->format('d M Y') : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Tanggal Kadaluarsa</th>
                            <td>
                                {{-- PERBAIKAN: CEK DETAIL DAN PROPERTI --}}
                                @if($product->details && $product->details->expired_date)
                                    <span class="{{ \Carbon\Carbon::parse($product->details->expired_date)->isPast() ? 'text-danger font-bold' : '' }}">
                                        {{ \Carbon\Carbon::parse($product->details->expired_date)->format('d M Y') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </table>

                    <div class="mt-4">
                        <h6>Deskripsi Produk:</h6>
                        <p class="text-muted" style="white-space: pre-line;">
                            {{ $product->description ?: 'Tidak ada deskripsi untuk produk ini.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-3 px-2">
                <small class="text-muted italic">
                    Data dibuat pada: {{ $product->created_at->format('d/m/Y H:i') }} | 
                    Terakhir diperbarui: {{ $product->updated_at->format('d/m/Y H:i') }}
                </small>
            </div>
        </div>
    </div>
</div>

@endsection