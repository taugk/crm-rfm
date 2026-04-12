@extends('layouts.admin')

@section('title', 'Data User')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Manajemen User</h3>
                <p class="text-subtitle text-muted">Daftar seluruh pengguna sistem.</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    {{-- Client-Side Filtering --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Pencarian Cepat</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="jsSearchInput" class="form-control" placeholder="Cari nama, email, atau telepon...">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Role</label>
                    <select id="jsRoleFilter" class="form-select">
                        <option value="">Semua Role</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="kasir">Kasir</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="jsResetBtn" class="btn btn-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Daftar Pengguna</h4>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg"></i> + Tambah User</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="userTable">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Profil</th>
                            <th>Kontak</th>
                            <th>Role</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        @forelse($data ?? [] as $u)
                        <tr class="user-row">
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                <div class="avatar avatar-md me-3">
                                    {{-- Langsung panggil asset($u->profile_photo) --}}
                                    <img src="{{ $u->profile_photo ? asset($u->profile_photo) : asset('assets/images/faces/1.jpg') }}" 
                                        alt="Avatar" 
                                        class="rounded-circle shadow-sm"
                                        style="width: 40px; height: 40px; object-fit: cover;"
                                        onerror="this.src='{{ asset('assets/images/faces/1.jpg') }}'">
                                </div>
                                <span class="fw-bold text-dark user-name">{{ $u->name }}</span>
                            </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <small class="user-email"><i class="bi bi-envelope me-1"></i> {{ $u->email }}</small>
                                    <small class="user-phone text-muted"><i class="bi bi-telephone me-1"></i> {{ $u->phone ?? '-' }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $u->role == 'admin' ? 'bg-light-danger' : 'bg-light-primary' }} user-role">
                                    {{ ucfirst($u->role) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group gap-1">
                                    <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-info rounded-2 border-0">Detail</a>
                                    <a href="{{ route('admin.users.edit', $u->id) }}" class="btn btn-sm btn-warning rounded-2 border-0">Edit</a>
                                    <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm delete-confirm" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5">Data Kosong</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('jsSearchInput');
        const roleFilter = document.getElementById('jsRoleFilter');
        const tableRows = document.querySelectorAll('.user-row');

        function filter() {
            const search = searchInput.value.toLowerCase();
            const role = roleFilter.value.toLowerCase();
            tableRows.forEach(row => {
                const name = row.querySelector('.user-name').innerText.toLowerCase();
                const email = row.querySelector('.user-email').innerText.toLowerCase();
                const phone = row.querySelector('.user-phone').innerText.toLowerCase();
                const uRole = row.querySelector('.user-role').innerText.toLowerCase().trim();
                
                const matchSearch = name.includes(search) || email.includes(search) || phone.includes(search);
                const matchRole = role === "" || uRole === role;
                row.style.display = (matchSearch && matchRole) ? "" : "none";
            });
        }
        searchInput.addEventListener('input', filter);
        roleFilter.addEventListener('change', filter);
        document.getElementById('jsResetBtn').addEventListener('click', () => {
            searchInput.value = ""; roleFilter.value = ""; filter();
        });
    });
</script>
@endpush