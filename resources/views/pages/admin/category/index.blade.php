@extends('layouts.admin')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Kategori Produk</h3>
                <p class="text-subtitle text-muted">Manajemen kategori sesuai dengan skema kode dan deskripsi.</p>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Data Tabel</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
                    <i class="bi bi-plus"></i> Tambah Kategori
                </button>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible show fade">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <table class="table table-striped" id="table1">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $cat)
                        <tr>
                            <td><span class="badge bg-light-primary text-primary">{{ $cat->kd_category }}</span></td>
                            <td>{{ $cat->name }}</td>
                            <td>{{ $cat->description ?? '-' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning btn-edit" 
                                    data-id="{{ $cat->id }}"
                                    data-kd="{{ $cat->kd_category }}"
                                    data-name="{{ $cat->name }}"
                                    data-desc="{{ $cat->description }}"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalEdit">
                                    Edit
                                </button>

                                <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger delete-confirm">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $categories->links('pagination::bootstrap-5')  }}
                </div>
            </div>
        </div>
    </section>
</div>

{{-- MODAL CREATE --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.categories.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Kategori</label>
                        <input type="text" name="kd_category" class="form-control" placeholder="Contoh: KTG-01" required>
                    </div>
                    <div class="form-group mt-2">
                        <label>Nama Kategori</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group mt-2">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formEdit" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Kategori</label>
                        <input type="text" name="kd_category" id="edit_kd" class="form-control" required>
                    </div>
                    <div class="form-group mt-2">
                        <label>Nama Kategori</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group mt-2">
                        <label>Deskripsi</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning edit-confirm">Update Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('.btn-edit').on('click', function() {
            const id = $(this).data('id');
            const kd = $(this).data('kd');
            const name = $(this).data('name');
            const desc = $(this).data('desc');

            // Sesuaikan action form dengan Route: admin.categories.update ({id})
            $('#formEdit').attr('action', '/admin/categories/' + id);

            // Isi field modal
            $('#edit_kd').val(kd);
            $('#edit_name').val(name);
            $('#edit_desc').val(desc);
        });
    });
</script>
@endpush