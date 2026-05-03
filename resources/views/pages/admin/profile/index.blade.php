@php
    $role = auth()->user()->role;
    $layout = match($role) {
        'admin' => 'layouts.admin',
        'manager' => 'layouts.manager',
        'kasir' => 'layouts.kasir',
        default => 'layouts.app',
    };
@endphp

@extends($layout)

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

            {{-- Bagian Kanan: Form Update Profil --}}
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="card-title">Informasi Profil</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route($role . '.profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Email</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted italic">Email dapat diubah, pastikan unik.</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold small">Nomor Telepon</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold small">Foto Profil</label>
                                    <input type="file" name="profile_photo" class="form-control @error('profile_photo') is-invalid @enderror" accept="image/*">
                                    @error('profile_photo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah foto. Format: jpg, jpeg, png. Maks 2MB.</small>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary fw-bold px-4">Update Profil</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Form Ganti Password --}}
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="card-title">Ganti Password</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route($role . '.profile.password') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold small">Password Saat Ini</label>
                                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Password Baru</label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Konfirmasi Password Baru</label>
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary fw-bold px-4">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection