@extends('layouts.admin')

@section('title', 'Detail Staf')

@section('content')
<div class="page-heading">
    <div class="d-flex justify-content-between align-items-center">
        <h3>Profil Pengguna Internal</h3>
        <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Daftar Staf
        </a>
    </div>
</div>

<div class="page-content">
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-4">
                    <div class="avatar avatar-xl mb-3">
                        <img src="{{ $user->profile_photo ? asset('storage/'.$user->profile_photo) : asset('assets/images/faces/1.jpg') }}" 
                             class="rounded-circle border p-1" 
                             style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-3">{{ $user->email }}</p>
                    
                    @php
                        $roleColor = [
                            'admin' => 'bg-danger',
                            'manager' => 'bg-primary',
                            'kasir' => 'bg-success'
                        ][$user->role] ?? 'bg-secondary';
                    @endphp
                    <span class="badge {{ $roleColor }} px-4 py-2 text-uppercase" style="letter-spacing: 1px;">
                        <i class="bi bi-shield-lock me-1"></i> {{ $user->role }}
                    </span>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="p-3 bg-light rounded-3 text-start">
                                <small class="text-muted d-block">Login Terakhir</small>
                                <span class="fw-bold text-dark">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Belum Login' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="card-title mb-0">Informasi Personalia</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="ps-4 py-3" width="35%">Username / Email</th>
                                <td class="py-3">{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 py-3">Telepon</th>
                                <td class="py-3">{{ $user->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4 py-3">Status Akun</th>
                                <td class="py-3">
                                    @if($user->status == 'active')
                                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Aktif</span>
                                    @else
                                        <span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> Non-Aktif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 py-3">Hak Akses Sistem</th>
                                <td class="py-3 text-muted small">
                                    <ul class="mb-0 ps-3">
                                        @if($user->role == 'admin')
                                            <li>Manajemen User & Database</li>
                                            <li>Konfigurasi Sistem RFM</li>
                                        @elseif($user->role == 'manager')
                                            <li>Laporan Analisis K-Means</li>
                                            <li>Manajemen Promosi</li>
                                        @else
                                            <li>Input Transaksi Penjualan</li>
                                            <li>Registrasi Pelanggan Baru</li>
                                        @endif
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-4 py-3 border-top">Dibuat Pada</th>
                                <td class="py-3 border-top">{{ $user->created_at->format('d M Y, H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent d-flex justify-content-end gap-2 py-3">
                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#resetPassword">
                        <i class="bi bi-key me-1"></i> Reset Password
                    </button>
                    <a href="/user/{{ $user->id }}/edit" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-square me-1"></i> Ubah Data Staf
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">Log Aktivitas Terbaru</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small text-center my-3 italic">Fitur log aktivitas akan segera tersedia di pembaruan sistem berikutnya.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection