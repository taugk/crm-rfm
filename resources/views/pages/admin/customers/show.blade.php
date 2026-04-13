@extends('layouts.admin')
@section('title', 'Detail Pelanggan - ' . $customer->name)

@section('content')
<div class="page-heading">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.customers') }}">Pelanggan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profil Pelanggan</li>
                </ol>
            </nav>
            <h3 class="fw-bold">Ringkasan Pelanggan</h3>
        </div>
        <div class="btn-group shadow-sm">
            <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-warning">
                <i class="bi bi-pencil-square me-2"></i>Edit
            </a>
            <a href="{{ route('admin.customers') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>
</div>

<div class="page-content">
    {{-- Header Stats Row --}}
    <div class="row">
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon purple mb-2">
                                <i class="iconly-boldStar"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold small uppercase">Total Poin</h6>
                            <h5 class="font-extrabold mb-0">{{ number_format($customer->total_points) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon blue mb-2">
                                <i class="iconly-boldBuy"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold small uppercase">Total Transaksi</h6>
                            <h5 class="font-extrabold mb-0">{{ $customer->transactions->count() }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon green mb-2">
                                <i class="iconly-boldCalendar"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold small uppercase">Visit Terakhir</h6>
                            <h5 class="font-extrabold mb-0 small">{{ $customer->last_purchase_at ? $customer->last_purchase_at->format('d/m/y') : '-' }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon red mb-2">
                                <i class="iconly-boldWallet"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold small uppercase">Total Spending</h6>
                            <h5 class="font-extrabold mb-0 small">Rp{{ number_format($customer->transactions->sum('subtotal')) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Sisi Kiri: Profil Singkat --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="avatar avatar-2xl mb-3">
                            <img src="{{ $customer->profile_photo ? asset($customer->profile_photo) : 'https://www.w3schools.com/howto/img_avatar.png' }}" 
                                 alt="Avatar" class="rounded-circle border" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <div class="mt-2">
                            <h4 class="fw-bold">{{ $customer->name }}</h4>
                            <p class="text-muted mb-1">{{ $customer->email ?? '-' }}</p>
                            <span class="badge {{ $customer->status == 'active' ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }} rounded-pill px-3">
                                {{ ucfirst($customer->status) }}
                            </span>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="info-list">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">No. Telepon</span>
                            <span class="fw-bold">{{ $customer->phone }}</span>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">ID Pelanggan</span>
                            <span class="fw-bold text-primary">#PLG-{{ str_pad($customer->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sisi Kanan: Tab History & Detail --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <ul class="nav nav-tabs card-header-tabs" id="customerTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active fw-bold" id="info-tab" data-bs-toggle="tab" href="#info" role="tab">
                                <i class="bi bi-info-circle me-2"></i>Info Dasar
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link fw-bold" id="history-tab" data-bs-toggle="tab" href="#history" role="tab">
                                <i class="bi bi-clock-history me-2"></i>History Transaksi
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="myTabContent">
                        {{-- Tab Info Dasar --}}
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold text-uppercase d-block mb-1">Jenis Kelamin</label>
                                    <p class="fw-bold text-dark fs-5">{{ $customer->gender == 'male' ? 'Laki-laki' : ($customer->gender == 'female' ? 'Perempuan' : 'Lainnya') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold text-uppercase d-block mb-1">Tanggal Lahir</label>
                                    <p class="fw-bold text-dark fs-5">{{ $customer->date_of_birth ? $customer->date_of_birth->format('d F Y') : '-' }}</p>
                                </div>
                                <div class="col-12">
                                    <label class="text-muted small fw-bold text-uppercase d-block mb-1">Alamat Lengkap</label>
                                    <div class="p-3 bg-light rounded border-start border-primary border-4">
                                        <p class="mb-0 fw-bold">{{ $customer->full_address ?? 'Alamat tidak diisi.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab History Transaksi --}}
                        <div class="tab-pane fade" id="history" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Tanggal</th>
                                            <th>Metode</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customer->transactions()->latest()->take(10)->get() as $trx)
                                        <tr>
                                            <td class="fw-bold">{{ $trx->invoice_number }}</td>
                                            <td>{{ $trx->created_at->format('d/m/y H:i') }}</td>
                                            <td><span class="badge bg-light-secondary text-dark">{{ strtoupper($trx->payment_method) }}</span></td>
                                            <td class="text-end fw-bold">Rp{{ number_format($trx->subtotal) }}</td>
                                            <td class="text-center">
                                                <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detail</a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Belum ada history transaksi.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection