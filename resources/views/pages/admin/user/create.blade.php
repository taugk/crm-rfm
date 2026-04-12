@extends('layouts.admin')

@section('title', 'Tambah User')

@section('content')
<div class="page-heading">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.users') }}">User</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah User</li>
        </ol>
    </nav>
    <h3 class="fw-bold">Tambah User Baru</h3>
</div>

<div class="page-content">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" id="userForm">
                @csrf
                <div class="row">
                    {{-- SISI KIRI: FOTO PROFIL --}}
                    <div class="col-md-4 text-center border-end">
                        <label class="fw-bold mb-3 d-block">Foto Profil</label>
                        <div class="mb-3">
                            {{-- Preview Image --}}
                            <img src="{{ asset('assets/images/faces/1.jpg') }}" 
                                 id="preview" 
                                 class="rounded-circle img-thumbnail shadow-sm" 
                                 style="width: 180px; height: 180px; object-fit: cover;">
                        </div>
                        <div class="px-3">
                            <input type="file" name="profile_photo" id="photo" class="form-control @error('profile_photo') is-invalid @enderror" accept="image/*">
                            @error('profile_photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-2">Format: JPG, PNG. Maks 2MB</small>
                        </div>
                    </div>

                    {{-- SISI KANAN: FORM DATA --}}
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-bold mb-1">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Masukkan nama" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="fw-bold mb-1">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="email@contoh.com" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="fw-bold mb-1">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="08xxxx">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="fw-bold mb-1">Role / Hak Akses</label>
                                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="" disabled selected>-- Pilih Role --</option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                                    <option value="kasir" {{ old('role') == 'kasir' ? 'selected' : '' }}>Kasir</option>
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- status --}}
                            <div class="col-md-6">
                                <label class="fw-bold mb-1">Status Akun</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="" disabled selected>-- Pilih Status --</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <hr class="my-3">

                            <div class="col-md-6">
                                <label class="fw-bold mb-1">Password</label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="fw-bold mb-1">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                                <div id="password-error" class="invalid-feedback">Password tidak cocok!</div>
                                <div id="password-success" class="valid-feedback">Password cocok.</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-5">
                            <a href="{{ route('admin.users') }}" class="btn btn-light px-4 me-2">Batal</a>
                            <button type="submit" id="submit-btn" class="btn btn-primary px-5 shadow-sm">
                                <i class="bi bi-save me-1"></i> Simpan User
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const photoInput = document.getElementById('photo');
        const previewImg = document.getElementById('preview');
        const password = document.getElementById('password');
        const confirm = document.getElementById('password_confirmation');
        const submitBtn = document.getElementById('submit-btn');
        const errorMsg = document.getElementById('password-error');
        const successMsg = document.getElementById('password-success');

        // 1. LOGIC: PREVIEW FOTO
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => { 
                    previewImg.src = e.target.result; 
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // 2. LOGIC: VALIDASI PASSWORD MATCH
        function validatePassword() {
            const passVal = password.value;
            const confVal = confirm.value;

            // Jika konfirmasi masih kosong, reset state
            if (confVal === "") {
                confirm.classList.remove('is-invalid', 'is-valid');
                submitBtn.disabled = false;
                return;
            }

            if (passVal !== confVal) {
                // Jika Tidak Cocok
                confirm.classList.add('is-invalid');
                confirm.classList.remove('is-valid');
                submitBtn.disabled = true; // Disable tombol submit
            } else {
                // Jika Cocok
                confirm.classList.remove('is-invalid');
                confirm.classList.add('is-valid');
                submitBtn.disabled = false; // Enable tombol submit
            }
        }

        // Jalankan fungsi setiap kali user mengetik
        password.addEventListener('keyup', validatePassword);
        confirm.addEventListener('keyup', validatePassword);
    });
</script>
@endpush