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

@section('title', 'Edit Promosi')

@section('content')
<div class="page-heading">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Promosi</h3>
        <a href="{{ route($routePrefix . '.promo') }}" class="btn btn-light-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="page-content">
    <section class="section">
        <form action="{{ route($routePrefix . '.promo.update', $promo->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="mb-0 fw-bold">Informasi Promosi</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="promo_name" class="form-label fw-bold small text-uppercase">Nama Promosi <span class="text-danger">*</span></label>
                                <input type="text" name="promo_name" id="promo_name" class="form-control @error('promo_name') is-invalid @enderror" value="{{ old('promo_name', $promo->promo_name) }}" required>
                                @error('promo_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="description" class="form-label fw-bold small text-uppercase">Deskripsi</label>
                                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $promo->description) }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="discount_type" class="form-label fw-bold small text-uppercase">Tipe Diskon</label>
                                        <select name="discount_type" id="discount_type" class="form-select">
                                            <option value="percentage" {{ $promo->discount_type == 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                                            <option value="fixed_amount" {{ $promo->discount_type == 'fixed_amount' ? 'selected' : '' }}>Potongan Harga</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="discount_value" class="form-label fw-bold small text-uppercase">Nilai Diskon <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="discount_value" id="discount_value" class="form-control" value="{{ old('discount_value', $promo->discount_value) }}" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="target_segment" class="form-label fw-bold small text-uppercase">Target Segmen</label>
                                <select name="target_segment" id="target_segment" class="form-select">
                                    <option value="all" {{ $promo->target_segment == 'all' ? 'selected' : '' }}>Semua Pelanggan</option>
                                    <option value="Needs Attention" {{ $promo->target_segment == 'Needs Attention' ? 'selected' : '' }}>Needs Attention</option>
                                    <option value="Champions" {{ $promo->target_segment == 'Champions' ? 'selected' : '' }}>Champions</option>
                                    <option value="Potential Loyalists" {{ $promo->target_segment == 'Potential Loyalists' ? 'selected' : '' }}>Potential Loyalists</option>
                                    <option value="At Risk" {{ $promo->target_segment == 'At Risk' ? 'selected' : '' }}>At Risk</option>
                                    <option value="Loyal Customers" {{ $promo->target_segment == 'Loyal Customers' ? 'selected' : '' }}>Loyal Customers</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-warning text-dark py-3">
                            <h5 class="mb-0 fw-bold small text-uppercase"><i class="bi bi-gear-fill me-2"></i>Aturan & Jadwal</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="promo_code" class="form-label fw-bold small text-uppercase">Kode Promo</label>
                                <input type="text" name="promo_code" id="promo_code" class="form-control text-uppercase @error('promo_code') is-invalid @enderror" value="{{ old('promo_code', $promo->promo_code) }}">
                                <small class="text-muted small">Kosongkan jika promo otomatis.</small>
                                @error('promo_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="min_spend" class="form-label fw-bold small text-uppercase">Min. Belanja (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" step="0.01" name="min_spend" id="min_spend" class="form-control" value="{{ old('min_spend', $promo->min_spend) }}">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="usage_limit" class="form-label fw-bold small text-uppercase">Batas Pemakaian</label>
                                <input type="number" name="usage_limit" id="usage_limit" class="form-control" value="{{ old('usage_limit', $promo->usage_limit) }}">
                                <small class="text-muted small">Kosongkan jika tidak terbatas.</small>
                            </div>

                            <hr>

                            <div class="form-group mb-3">
                                <label for="start_date" class="form-label fw-bold small text-success">Waktu Mulai</label>
                                <input type="datetime-local" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', $promo->start_date->format('Y-m-d\TH:i')) }}" required>
                            </div>

                            <div class="form-group mb-4">
                                <label for="end_date" class="form-label fw-bold small text-danger">Waktu Berakhir</label>
                                <input type="datetime-local" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', $promo->end_date->format('Y-m-d\TH:i')) }}" required>
                            </div>

                            <div class="bg-light p-3 rounded-3 mb-4 border">
                                <label class="form-label fw-bold small mb-2 d-block">STATUS PROMO</label>
                                <div class="form-check form-switch p-0 m-0 d-flex justify-content-between align-items-center">
                                    <label class="form-check-label fw-bold small" for="is_active">Aktifkan Promosi</label>
                                    <input class="form-check-input ms-0" type="checkbox" name="is_active" id="is_active" style="width: 40px; height: 20px;" value="1" {{ $promo->is_active ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-warning fw-bold">
                                    <i class="bi bi-save2-fill me-2"></i> UPDATE PROMOSI
                                </button>
                                <a href="{{ route($routePrefix . '.promo') }}" class="btn btn-light-secondary">BATAL</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
@endsection