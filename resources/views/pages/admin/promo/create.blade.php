@extends('layouts.admin')

@section('title', 'Tambah Promosi')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold"><i class="bi bi-megaphone-fill me-2 text-primary"></i>Buat Promosi Baru</h3>
                <p class="text-subtitle text-muted">Luncurkan penawaran menarik untuk meningkatkan penjualan cafe Anda.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.promo') }}">Promosi</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tambah Baru</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <section class="section">
        <form action="{{ route('admin.promo.store') }}" method="POST">
            @csrf
            <div class="row">
                {{-- KOLOM KIRI: Konten Utama --}}
                <div class="col-md-8">
                    {{-- Card 1: Informasi Dasar --}}
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="mb-0 fw-bold">1. Informasi Dasar</h5>
                        </div>
                        <div class="card-body mt-3">
                            <div class="form-group mb-3">
                                <label for="promo_name" class="form-label fw-bold small">NAMA PROMOSI <span class="text-danger">*</span></label>
                                <input type="text" name="promo_name" id="promo_name" class="form-control @error('promo_name') is-invalid @enderror" value="{{ old('promo_name') }}" placeholder="Contoh: Promo Ramadhan Berkah" required>
                                @error('promo_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label for="description" class="form-label fw-bold small">DESKRIPSI PROMOSI</label>
                                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Jelaskan detail syarat dan ketentuan promo agar pelanggan paham...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Konfigurasi Nilai --}}
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="mb-0 fw-bold">2. Nilai & Target</h5>
                        </div>
                        <div class="card-body mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="discount_type" class="form-label fw-bold small">TIPE DISKON</label>
                                        <select name="discount_type" id="discount_type" class="form-select border-primary-subtle">
                                            <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                                            <option value="fixed_amount" {{ old('discount_type') == 'fixed_amount' ? 'selected' : '' }}>Potongan Harga (Nominal Rp)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="discount_value" class="form-label fw-bold small">NILAI DISKON <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="discount_value" id="discount_value" class="form-control @error('discount_value') is-invalid @enderror" value="{{ old('discount_value') }}" placeholder="0" required>
                                            <span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
                                        </div>
                                        @error('discount_value') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label for="target_segment" class="form-label fw-bold small">TARGET SEGMEN PELANGGAN</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-people"></i></span>
                                    <input type="text" name="target_segment" id="target_segment" class="form-control" value="{{ old('target_segment') }}" placeholder="Contoh: Member Baru, Pelanggan VIP, atau Semua">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN: Aturan & Jadwal --}}
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 sticky-top" style="top: 20px; z-index: 10;">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0 fw-bold text-white small text-uppercase"><i class="bi bi-gear-fill me-2"></i>Aturan & Jadwal</h5>
                        </div>
                        <div class="card-body mt-3">
                            <div class="form-group mb-3">
                                <label for="promo_code" class="form-label fw-bold small">KODE PROMO</label>
                                <input type="text" name="promo_code" id="promo_code" class="form-control border-primary-subtle text-uppercase fw-bold @error('promo_code') is-invalid @enderror" value="{{ old('promo_code') }}" placeholder="MISAL: HEMAT20">
                                <small class="text-muted italic small">Kosongkan jika promo otomatis.</small>
                                @error('promo_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="min_spend" class="form-label fw-bold small">MIN. BELANJA (RP)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" step="1" name="min_spend" id="min_spend" class="form-control" value="{{ old('min_spend', 0) }}">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="usage_limit" class="form-label fw-bold small">BATAS TOTAL KUOTA</label>
                                <input type="number" name="usage_limit" id="usage_limit" class="form-control form-control-sm" value="{{ old('usage_limit') }}" placeholder="∞ (Tak Terbatas)">
                            </div>

                            <hr class="my-3">

                            <div class="form-group mb-3">
                                <label for="start_date" class="form-label fw-bold small text-success">WAKTU MULAI (WIB)</label>
                                <input type="datetime-local" name="start_date" id="start_date" class="form-control form-control-sm border-success-subtle" value="{{ old('start_date', date('Y-m-d\TH:i')) }}" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="end_date" class="form-label fw-bold small text-danger">WAKTU BERAKHIR (WIB)</label>
                                <input type="datetime-local" name="end_date" id="end_date" class="form-control form-control-sm border-danger-subtle" value="{{ old('end_date') }}" required>
                            </div>

                            <div class="bg-light p-3 rounded-3 mb-4 border">
                                <label class="form-label fw-bold small mb-2 d-block text-primary">STATUS AKTIVASI</label>
                                <div class="form-check form-switch p-0 m-0 d-flex justify-content-between align-items-center">
                                    <label class="form-check-label fw-bold small" for="is_active">Aktifkan Promosi</label>
                                    <input class="form-check-input ms-0" type="checkbox" name="is_active" id="is_active" style="width: 40px; height: 20px;" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary fw-bold shadow">
                                    <i class="bi bi-save2-fill me-2"></i>SIMPAN PROMO
                                </button>
                                <a href="{{ route('admin.promo') }}" class="btn btn-light-secondary btn-sm">KEMBALI</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
@endsection