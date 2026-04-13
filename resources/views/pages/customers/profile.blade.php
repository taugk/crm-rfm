@extends('layouts.customers')

@section('title', 'Profil & Keamanan')

@section('content')
<style>
    .profile-header {
        background: var(--primary-gradient);
        border-radius: 30px;
        padding: 40px;
        color: white;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    .avatar-wrapper {
        width: 120px;
        height: 120px;
        border-radius: 35px;
        border: 5px solid rgba(255,255,255,0.2);
        background: #fff;
        position: relative;
    }
    .profile-card {
        border-radius: 24px;
        border: 1px solid #f1f5f9;
        background: #fff;
    }
    .form-control-profile {
        border-radius: 12px;
        padding: 12px 15px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        transition: all 0.3s;
    }
    .form-control-profile:focus {
        background: #fff;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        outline: none;
    }
    .nav-pills-profile .nav-link {
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 8px;
        border: 1px solid transparent;
    }
    .nav-pills-profile .nav-link.active {
        background: #fff;
        color: var(--primary);
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
</style>

<div class="container pb-5">
    {{-- Header Section --}}
    <div class="profile-header shadow-lg">
        <div class="row align-items-center">
            <div class="col-md-auto text-center mb-3 mb-md-0">
                <div class="avatar-wrapper mx-auto">
                    <img src="{{ $customer->profile_photo ? asset($customer->profile_photo) : 'https://ui-avatars.com/api/?name='.urlencode($customer->name).'&background=6366f1&color=fff&size=128' }}" 
                         class="w-100 h-100 object-fit-cover" style="border-radius: 30px;">
                </div>
            </div>
            <div class="col-md">
                <h2 class="fw-800 mb-1">{{ $customer->name }}</h2>
                <p class="mb-0 opacity-75 fw-500"><i class="bi bi-geo-alt-fill me-2"></i>{{ $customer->full_address ?? 'Alamat belum dilengkapi' }}</p>
                <div class="mt-3">
                    <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold shadow-sm">
                        <i class="bi bi-patch-check-fill me-1"></i> {{ strtoupper($customer->type) }}
                    </span>
                    <span class="badge bg-white bg-opacity-20 text-dark rounded-pill px-3 py-2 ms-2 fw-500">
                        {{ number_format($customer->total_points) }} PTS
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Sidebar Menu --}}
        <div class="col-lg-4">
            <div class="card profile-card p-3 shadow-sm border-0 mb-4">
                <div class="nav flex-column nav-pills nav-pills-profile" id="v-pills-tab" role="tablist">
                    <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#tab-info">
                        <i class="bi bi-person-vcard me-2"></i> Informasi Pribadi
                    </button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-password">
                        <i class="bi bi-shield-lock me-2"></i> Ganti Password
                    </button>
                    <hr class="my-2 opacity-25">
                    <button class="nav-link text-start text-danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right me-2"></i> Keluar Akun
                    </button>
                </div>
            </div>
        </div>

        {{-- Content Area --}}
        <div class="col-lg-8">
            <div class="tab-content">
                
                {{-- Tab 1: Update Profil (Sesuai Struktur Tabel) --}}
                <div class="tab-pane fade show active" id="tab-info">
                    <div class="card profile-card p-4 p-md-5 shadow-sm border-0">
                        <h5 class="fw-800 text-dark mb-4">Detail Akun Member</h5>
                        <form action="{{ route('customers.profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">FOTO PROFIL</label>
                                    <input type="file" name="profile_photo" class="form-control form-control-profile">
                                    <small class="text-muted">Format: JPG, PNG. Maksimal 2MB.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">NAMA LENGKAP</label>
                                    <input type="text" name="name" class="form-control form-control-profile" value="{{ $customer->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">JENIS KELAMIN</label>
                                    <select name="gender" class="form-select form-control-profile">
                                        <option value="" disabled {{ is_null($customer->gender) ? 'selected' : '' }}>Pilih Jenis Kelamin</option>
                                        <option value="male" {{ $customer->gender == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="female" {{ $customer->gender == 'female' ? 'selected' : '' }}>Perempuan</option>
                                        <option value="other" {{ $customer->gender == 'other' ? 'selected' : '' }}>Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">TANGGAL LAHIR</label>
                                    <input type="date" name="date_of_birth" class="form-control form-control-profile" value="{{ $customer->date_of_birth }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">NOMOR TELEPON</label>
                                    <input type="text" name="phone" class="form-control form-control-profile" value="{{ $customer->phone }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">EMAIL (PERMANEN)</label>
                                    <input type="email" class="form-control form-control-profile bg-light opacity-75" value="{{ $customer->email }}" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">ALAMAT LENGKAP</label>
                                    <textarea name="full_address" class="form-control form-control-profile" rows="3">{{ $customer->full_address }}</textarea>
                                </div>
                                <div class="col-12 mt-4 text-end">
                                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-primary">Simpan Perubahan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Tab 2: Password --}}
                <div class="tab-pane fade" id="tab-password">
                    <div class="card profile-card p-4 p-md-5 shadow-sm border-0">
                        <h5 class="fw-800 text-dark mb-4">Ubah Keamanan Akun</h5>
                        <form action="{{ route('customers.password.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">PASSWORD SAAT INI</label>
                                    <input type="password" name="current_password" class="form-control form-control-profile" placeholder="********">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">PASSWORD BARU</label>
                                    <input type="password" name="password" class="form-control form-control-profile" placeholder="Minimal 8 karakter">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">KONFIRMASI PASSWORD BARU</label>
                                    <input type="password" name="password_confirmation" class="form-control form-control-profile" placeholder="********">
                                </div>
                                <div class="col-12 mt-4 text-end">
                                    <button type="submit" class="btn btn-dark rounded-pill px-5 py-2 fw-bold">Perbarui Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<form id="logout-form" action="{{ route('customers.logout') }}" method="POST" class="d-none">@csrf</form>
@endsection