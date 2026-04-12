@extends('layouts.admin')
@section('title', 'Tambah Pelanggan')


@section('content')
<div class="page-heading">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.customers') }}">Pelanggan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah Pelanggan</li>
        </ol>
    </nav>
    <h3 class="fw-bold">Tambah Pelanggan Baru</h3>
</div>

<div class="page-content">
    <form action="{{ route('admin.customers.store') }}" method="POST" enctype="multipart/form-data" id="customerForm">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="mb-4 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Informasi Dasar</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Masukkan nama..." required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="08xxxx">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Email (Opsional)</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="email@domain.com">
                            </div>

                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Password (Opsional)</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control form-control-sm" placeholder="Kosongkan jika ingin pakai OTP">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <small class="text-muted" style="font-size: 10px;">*Jika kosong, pelanggan bisa login menggunakan OTP via WA/Email.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Jenis Kelamin</label>
                                <select name="gender" class="form-select">
                                    <option value="" disabled selected>Pilih...</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Perempuan</option>
                                    <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Tanggal Lahir</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                    <input type="text" name="birthdate" id="birthdate" class="form-control flatpickr" value="{{ old('birthdate') }}" placeholder="Pilih tanggal lahir">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                                    <option value="block" {{ old('status') == 'block' ? 'selected' : '' }}>Blokir</option>
                                </select>
                            </div>

                            <div class="col-12 mt-4">
                                <h5 class="mb-3 text-primary"><i class="bi bi-geo-alt me-2"></i>Informasi Alamat</h5>
                                <label class="fw-bold mb-1 small">Alamat Lengkap</label>
                                <textarea name="full_address" class="form-control @error('full_address') is-invalid @enderror" rows="4" placeholder="Jl. Merdeka No. 1...">{{ old('full_address') }}</textarea>
                                @error('full_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 text-center p-4">
                    <label class="fw-bold mb-3 small">Foto Profil</label>
                    <div class="mb-3">
                        <img src="https://www.w3schools.com/howto/img_avatar.png" id="preview" class="rounded-circle img-thumbnail shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <input type="file" name="profile_photo" id="photo" class="form-control @error('profile_photo') is-invalid @enderror" accept="image/*">
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm mt-4">
                        <i class="bi bi-check-circle me-1"></i> Simpan Pelanggan
                    </button>
                    <a href="{{ route('admin.customers') }}" class="btn btn-light-secondary w-100 mt-2">Batal</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Flatpickr Kalender (Format Indonesia)
        flatpickr("#birthdate", {
            locale: "id",
            dateFormat: "Y-m-d",
            maxDate: "today",
            minDate: new Date().getFullYear() - 100 + "-01-01"
        });

        // 2. Preview Foto Profil
        const photoInput = document.getElementById('photo');
        const previewImg = document.getElementById('preview');
        photoInput?.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => previewImg.src = e.target.result;
                reader.readAsDataURL(file);
            }
        });
    });

    // 3. Function Toggle Password (Diletakkan di luar DOMContentLoaded agar bisa dipanggil onclick)
    function togglePassword() {
        const passwordField = document.getElementById("password");
        const icon = document.getElementById("toggleIcon");
        
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.replace("bi-eye", "bi-eye-slash");
        } else {
            passwordField.type = "password";
            icon.classList.replace("bi-eye-slash", "bi-eye");
        }
    }
</script>
@endpush