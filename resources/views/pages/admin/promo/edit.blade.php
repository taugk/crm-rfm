@extends('layouts.admin')

@section('title', 'Edit Promosi')

@section('content')
<div class="page-heading">
    <h3 class="fw-bold">Edit Promosi</h3>
</div>

<div class="page-content">
    <section class="section">
        {{-- Gunakan rute update dengan ID --}}
        <form action="{{ route('admin.promo.update', $promo->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="promo_name" class="form-label fw-bold small text-uppercase">Nama Promosi</label>
                                <input type="text" name="promo_name" id="promo_name" class="form-control @error('promo_name') is-invalid @enderror" value="{{ old('promo_name', $promo->promo_name) }}" required>
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
                                        <label for="discount_value" class="form-label fw-bold small text-uppercase">Nilai Diskon</label>
                                        <input type="number" step="0.01" name="discount_value" id="discount_value" class="form-control" value="{{ old('discount_value', $promo->discount_value) }}" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="target_segment" class="form-label fw-bold small text-uppercase">Target Segmen</label>
                                <input type="text" name="target_segment" id="target_segment" class="form-control" value="{{ old('target_segment', $promo->target_segment) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="promo_code" class="form-label fw-bold small text-uppercase">Kode Promo</label>
                                <input type="text" name="promo_code" id="promo_code" class="form-control @error('promo_code') is-invalid @enderror" value="{{ old('promo_code', $promo->promo_code) }}">
                                @error('promo_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="min_spend" class="form-label fw-bold small text-uppercase">Min. Belanja (Rp)</label>
                                <input type="number" step="0.01" name="min_spend" id="min_spend" class="form-control" value="{{ old('min_spend', $promo->min_spend) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="usage_limit" class="form-label fw-bold small text-uppercase">Batas Pemakaian</label>
                                <input type="number" name="usage_limit" id="usage_limit" class="form-control" value="{{ old('usage_limit', $promo->usage_limit) }}">
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-3">
                                        <label for="start_date" class="form-label fw-bold small text-uppercase">Mulai</label>
                                        {{-- Format datetime-local memerlukan format Y-m-d\TH:i --}}
                                        <input type="datetime-local" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', $promo->start_date->format('Y-m-d\TH:i')) }}" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-3">
                                        <label for="end_date" class="form-label fw-bold small text-uppercase">Berakhir</label>
                                        <input type="datetime-local" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', $promo->end_date->format('Y-m-d\TH:i')) }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label fw-bold small">STATUS PROMO</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ $promo->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning w-100 fw-bold">UPDATE PROMOSI</button>
                            <a href="{{ route('admin.promo') }}" class="btn btn-light-secondary w-100 mt-2">BATAL</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
@endsection