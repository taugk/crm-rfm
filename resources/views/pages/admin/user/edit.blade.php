@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="page-heading"><h3>Edit User: {{ $u->name }}</h3></div>
<div class="page-content">
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/user/{{ $u->id }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-4 text-center border-end">
                        <label class="fw-bold mb-3">Foto Profil</label>
                        <div class="mb-3">
                            <img src="{{ $u->profile_photo ? asset('storage/'.$u->profile_photo) : asset('assets/images/faces/1.jpg') }}" id="preview" class="rounded-circle img-thumbnail" style="width: 180px; height: 180px; object-fit: cover;">
                        </div>
                        <input type="file" name="profile_photo" id="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="fw-bold">Nama</label><input type="text" name="name" class="form-control" value="{{ $u->name }}"></div>
                            <div class="col-md-6"><label class="fw-bold">Email</label><input type="email" name="email" class="form-control" value="{{ $u->email }}"></div>
                            <div class="col-md-6"><label class="fw-bold">Telepon</label><input type="text" name="phone" class="form-control" value="{{ $u->phone }}"></div>
                            <div class="col-md-6"><label class="fw-bold">Role</label>
                                <select name="role" class="form-select">
                                    <option value="manager" {{ $u->role == 'manager' ? 'selected' : '' }}>Manager</option>
                                    <option value="admin" {{ $u->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="kasir" {{ $u->role == 'kasir' ? 'selected' : '' }}>Kasir</option>
                                </select>
                            </div>
                            {{-- status --}}
                            <div class="col-md-6"><label class="fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" {{ $u->status == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ $u->status == 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                                </select>
                            </div>
                            <div class="col-12"><label class="fw-bold text-danger">Ganti Password (Kosongkan jika tidak)</label><input type="password" name="password" class="form-control"></div>
                            <div class="col-12"><label class="fw-bold">Alamat Lengkap</label><textarea name="address" class="form-control" rows="3">{{ $u->address->full_address ?? '' }}</textarea></div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('admin.users') }}" class="btn btn-light me-2">Batal</a>
                            <button type="submit" class="btn btn-warning text-white px-4 edit-confirm">Update</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection