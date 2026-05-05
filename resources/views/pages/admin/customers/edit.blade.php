@extends('layouts.admin')
@section('title', 'Edit Pelanggan')

@section('content')
<div class="page-heading">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.customers') }}">Pelanggan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Pelanggan</li>
        </ol>
    </nav>
    <h3 class="fw-bold">Edit Pelanggan: {{ $customer->name }}</h3>
</div>

<div class="page-content">
    {{-- Route diarahkan ke update dengan parameter ID --}}
    <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data" id="customerForm">
        @csrf
        @method('PUT') {{-- Wajib untuk proses update di Laravel --}}
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="mb-4 text-primary"><i class="bi bi-pencil-square me-2"></i>Perbarui Informasi Dasar</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customer->name) }}" placeholder="Masukkan nama..." required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $customer->phone) }}" placeholder="08xxxx" required>
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Email (Opsional)</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}" placeholder="email@domain.com">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Jenis Kelamin</label>
                                <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                    <option value="" disabled>Pilih...</option>
                                    <option value="male" {{ old('gender', $customer->gender) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="female" {{ old('gender', $customer->gender) == 'female' ? 'selected' : '' }}>Perempuan</option>
                                    <option value="other" {{ old('gender', $customer->gender) == 'other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Tanggal Lahir</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                    <input type="text" name="birthdate" id="birthdate" class="form-control flatpickr" value="{{ old('birthdate', $customer->date_of_birth ? $customer->date_of_birth->format('Y-m-d') : '') }}" placeholder="Pilih tanggal lahir">
                                </div>
                                @error('birthdate') <div class="invalid-feedback text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 small">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" {{ old('status', $customer->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ old('status', $customer->status) == 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                                    <option value="block" {{ old('status', $customer->status) == 'block' ? 'selected' : '' }}>Blokir</option>
                                </select>
                            </div>

                            {{-- SECTION PASSWORD - TAMBAHAN BARU --}}
                            <div class="col-12 mt-4">
                                <h5 class="mb-3 text-primary"><i class="bi bi-lock me-2"></i>Perbarui Password</h5>
                                <div class="alert alert-info small py-2 mb-3">
                                    <i class="bi bi-info-circle me-1"></i> 
                                    Kosongkan kedua field jika tidak ingin mengubah password.
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-bold mb-1 small">Password Baru</label>
                                        <div class="input-group">
                                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Masukkan password baru">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Minimal 6 karakter</small>
                                        @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-bold mb-1 small">Konfirmasi Password Baru</label>
                                        <div class="input-group">
                                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Konfirmasi password baru">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmation">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Masukkan ulang password yang sama</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <h5 class="mb-3 text-primary"><i class="bi bi-geo-alt me-2"></i>Informasi Alamat</h5>
                                <label class="fw-bold mb-1 small">Alamat Lengkap</label>
                                <textarea name="full_address" class="form-control @error('full_address') is-invalid @enderror" rows="4" placeholder="Jl. Merdeka No. 1..." required>{{ old('full_address', $customer->full_address) }}</textarea>
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
                        <img src="{{ $customer->profile_photo ? asset($customer->profile_photo) : 'https://www.w3schools.com/howto/img_avatar.png' }}" 
                             id="preview" class="rounded-circle img-thumbnail shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <input type="file" name="profile_photo" id="photo" class="form-control @error('profile_photo') is-invalid @enderror" accept="image/*">
                    @error('profile_photo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    
                    <button type="submit" class="btn btn-warning w-100 py-2 fw-bold shadow-sm mt-4 text-dark edit-confirm">
                        <i class="bi bi-pencil-square me-1"></i> Perbarui Pelanggan
                    </button>
                    <a href="{{ route('admin.customers') }}" class="btn btn-light-secondary w-100 mt-2">Batal</a>
                </div>
                
                {{-- Info Poin --}}
                <div class="card mt-3 border-0 shadow-sm bg-light">
                    <div class="card-body">
                        <small class="text-muted d-block">Saldo Poin Saat Ini</small>
                        <h4 class="fw-bold text-primary mb-0">{{ number_format($customer->total_points) }} Pts</h4>
                    </div>
                </div>

                {{-- Info Terakhir Update --}}
                <div class="card mt-3 border-0 shadow-sm bg-light">
                    <div class="card-body">
                        <small class="text-muted d-block">Terakhir Diupdate</small>
                        <h6 class="fw-bold mb-0">{{ $customer->updated_at ? $customer->updated_at->format('d M Y H:i') : '-' }}</h6>
                        <small class="text-muted d-block mt-2">Terdaftar Sejak</small>
                        <h6 class="fw-bold mb-0">{{ $customer->created_at ? $customer->created_at->format('d M Y') : '-' }}</h6>
                    </div>
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
        // Flatpickr Kalender
        flatpickr("#birthdate", {
            locale: "id",
            dateFormat: "Y-m-d",
            maxDate: "today",
            minDate: "1920-01-01"
        });

        // Preview Foto
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

        // Toggle Password Visibility
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Validasi password confirmation di client-side (opsional)
        const password = document.getElementById('password');
        const passwordConfirmation = document.getElementById('password_confirmation');
        
        function validatePasswordMatch() {
            if (password.value !== '' && passwordConfirmation.value !== '' && password.value !== passwordConfirmation.value) {
                passwordConfirmation.setCustomValidity('Password tidak cocok!');
                passwordConfirmation.classList.add('is-invalid');
            } else {
                passwordConfirmation.setCustomValidity('');
                passwordConfirmation.classList.remove('is-invalid');
            }
        }
        
        password?.addEventListener('change', validatePasswordMatch);
        passwordConfirmation?.addEventListener('keyup', validatePasswordMatch);
        
        // SweetAlert konfirmasi sebelum submit
        const form = document.getElementById('customerForm');
        form?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Simpan Perubahan?',
                text: "Pastikan data yang diisi sudah benar!",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#F97316',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

<style>
    .toggle-password {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .toggle-password:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush