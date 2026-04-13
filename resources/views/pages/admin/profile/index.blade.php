@extends('layouts.admin')

@section('title', 'Profil Pengguna')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Profil Saya</h3>
                <p class="text-subtitle text-muted">Kelola informasi pribadi dan keamanan akun Anda.</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <section class="section">
        <div class="row">
            {{-- Bagian Kiri: Foto & Ringkasan --}}
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-center flex-column align-items-center">
                            <div class="avatar avatar-2xl mb-3">
                                <img src="{{ $user->profile_photo ? asset('storage/'.$user->profile_photo) : 'https://www.w3schools.com/howto/img_avatar.png' }}" alt="Avatar" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
                            </div>
                            <h4 class="fw-bold">{{ $user->name }}</h4>
                            <p class="text-muted">
                                @if($user->role == 'admin')
                                    <span class="badge bg-danger">Administrator</span>
                                @elseif($user->role == 'manager')
                                    <span class="badge bg-primary">Manager</span>
                                @else
                                    <span class="badge bg-success">Kasir</span>
                                @endif
                            </p>
                        </div>
                        <hr>
                        <div class="mt-3">
                            <h6 class="fw-bold">Tentang</h6>
                            <p class="small text-muted">Akun ini dibuat pada {{ $user->created_at->format('d M Y') }}. Anda memiliki akses penuh sesuai dengan role yang diberikan.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian Kanan: Form Edit --}}
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="card-title">Pengaturan Akun</h5>
                    </div>
                    <div class="card-body">
                        <form action="#" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control" value="{{ $user->name }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ $user->email }}" readonly>
                                    <small class="text-muted italic">*Email tidak dapat diubah</small>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <h6 class="fw-bold border-bottom pb-2">Ganti Password</h6>
                                    <p class="text-muted small">Kosongkan jika tidak ingin mengubah password.</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Password Baru</label>
                                    <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Konfirmasi Password</label>
                                    <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password">
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-light-secondary fw-bold">Reset</button>
                                <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection