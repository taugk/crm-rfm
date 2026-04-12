@extends('layouts.admin')

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
                {{-- Navigasi Kembali --}}
                <a href="{{ route('admin.loyalty.rewards') }}" class="btn btn-light-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('admin.loyalty.rewards.update', $reward->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Nama Hadiah</label>
                        <input type="text" name="name" class="form-control" value="{{ $reward->name }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipe Hadiah</label>
                        <select name="reward_type" id="reward_type" class="form-select" required>
                            <option value="product" {{ $reward->reward_type == 'product' ? 'selected' : '' }}>Produk</option>
                            <option value="voucher" {{ $reward->reward_type == 'voucher' ? 'selected' : '' }}>Voucher</option>
                            <option value="other" {{ $reward->reward_type == 'other' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Poin Dibutuhkan</label>
                        <input type="number" name="points_required" class="form-control" value="{{ $reward->points_required }}" required>
                    </div>

                    <div class="col-md-6 mb-3" id="stock_field" style="{{ $reward->reward_type != 'product' ? 'display:none' : '' }}">
                        <label class="form-label fw-bold">Stok</label>
                        <input type="number" name="stock" class="form-control" value="{{ $reward->stock }}">
                    </div>

                    <div class="col-md-6 mb-3" id="amount_field" style="{{ $reward->reward_type != 'voucher' ? 'display:none' : '' }}">
                        <label class="form-label fw-bold">Nilai Voucher (Rp)</label>
                        <input type="number" name="value_amount" class="form-control" value="{{ (int)$reward->value_amount }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" {{ $reward->is_active ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ !$reward->is_active ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-start gap-2">
                    <button type="submit" class="btn btn-warning px-4">Update Hadiah</button>
                    {{-- Navigasi Batal --}}
                    <a href="{{ route('admin.loyalty.rewards') }}" class="btn btn-light px-4">Batal</a>
                </div>
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