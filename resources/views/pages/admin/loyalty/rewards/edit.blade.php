@php
    $layout = match(auth()->user()->role) {
        'manager' => 'layouts.manager',
        'admin' => 'layouts.admin',
        default => 'layouts.admin',
    };
    
    $routePrefix = match(auth()->user()->role) {
        'manager' => 'manager',
        'admin' => 'admin',
        default => 'admin'
    };
@endphp

@extends($layout)

@section('title', 'Edit Hadiah Poin')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Edit Hadiah: {{ $reward->name }}</h3>
                <p class="text-subtitle text-muted">Ubah detail informasi hadiah katalog poin.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first text-end">
                <a href="{{ route($routePrefix . '.loyalty.rewards') }}" class="btn btn-light-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route($routePrefix . '.loyalty.rewards.update', $reward->id) }}" method="POST" enctype="multipart/form-data">
                @csrf 
                @method('PUT')
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Nama Hadiah <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $reward->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipe Hadiah <span class="text-danger">*</span></label>
                        <select name="reward_type" id="reward_type" class="form-select @error('reward_type') is-invalid @enderror" required>
                            <option value="product" {{ old('reward_type', $reward->reward_type) == 'product' ? 'selected' : '' }}>Produk Fisik (Barang)</option>
                            <option value="voucher" {{ old('reward_type', $reward->reward_type) == 'voucher' ? 'selected' : '' }}>Voucher (Diskon/Nominal)</option>
                            <option value="other" {{ old('reward_type', $reward->reward_type) == 'other' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        @error('reward_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Poin yang Dibutuhkan <span class="text-danger">*</span></label>
                        <input type="number" name="points_required" class="form-control @error('points_required') is-invalid @enderror" value="{{ old('points_required', $reward->points_required) }}" required>
                        @error('points_required') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Dinamis: Muncul jika tipe Produk --}}
                    <div class="col-md-6 mb-3" id="stock_field" style="{{ old('reward_type', $reward->reward_type) != 'product' ? 'display:none' : '' }}">
                        <label class="form-label fw-bold">Stok <span class="text-danger">*</span></label>
                        <input type="number" name="stock" class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock', $reward->stock) }}">
                        @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Dinamis: Muncul jika tipe Voucher --}}
                    <div class="col-md-6 mb-3" id="amount_field" style="{{ old('reward_type', $reward->reward_type) != 'voucher' ? 'display:none' : '' }}">
                        <label class="form-label fw-bold">Nilai Voucher (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="value_amount" class="form-control @error('value_amount') is-invalid @enderror" value="{{ old('value_amount', $reward->value_amount) }}">
                        @error('value_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Gambar Hadiah</label>
                        @if($reward->image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/'.$reward->image) }}" width="100" height="100" class="rounded" style="object-fit: cover;">
                                <small class="d-block text-muted">Gambar saat ini</small>
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">Format: JPG, JPEG, PNG (Max 2MB). Kosongkan jika tidak ingin mengubah gambar.</small>
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" {{ old('is_active', $reward->is_active) ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ old('is_active', $reward->is_active) ? '' : 'selected' }}>Non-Aktif</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Deskripsi Hadiah</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan detail hadiah...">{{ old('description', $reward->description) }}</textarea>
                    </div>
                </div>
                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning px-4">
                        <i class="bi bi-save me-1"></i> Update Hadiah
                    </button>
                    <a href="{{ route($routePrefix . '.loyalty.rewards') }}" class="btn btn-light-secondary px-4">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('reward_type').addEventListener('change', function() {
        const stockField = document.getElementById('stock_field');
        const amountField = document.getElementById('amount_field');
        
        if (this.value === 'product') {
            stockField.style.display = 'block';
            amountField.style.display = 'none';
            document.querySelector('input[name="stock"]').required = true;
            document.querySelector('input[name="value_amount"]').required = false;
        } else if (this.value === 'voucher') {
            stockField.style.display = 'none';
            amountField.style.display = 'block';
            document.querySelector('input[name="stock"]').required = false;
            document.querySelector('input[name="value_amount"]').required = true;
        } else {
            stockField.style.display = 'none';
            amountField.style.display = 'none';
            document.querySelector('input[name="stock"]').required = false;
            document.querySelector('input[name="value_amount"]').required = false;
        }
    });
</script>
@endpush