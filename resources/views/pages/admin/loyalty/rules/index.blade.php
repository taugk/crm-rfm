@php
    $layout = match(auth()->user()->role) {
        'manager' => 'layouts.manager',
        'admin' => 'layouts.admin',
        default => 'layouts.admin',
    };
    
    $routePrefix = match(auth()->user()->role) {
        'manager' => 'manager',
        'admin' => 'admin',
        default => 'admin'
    };
@endphp

@extends($layout)

@section('title', 'Aturan Perolehan Poin')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-md-6">
                <h3 class="fw-bold">Aturan Perolehan Poin</h3>
                <p class="text-muted">Tentukan kebijakan konversi nilai belanja menjadi poin pelanggan.</p>
            </div>
            <div class="col-md-6 text-end">
                <button id="btnAdd" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#ruleModal">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Aturan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h5 class="mb-0">Daftar Kebijakan Poin</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Aturan</th>
                            <th>Setiap Belanja</th>
                            <th>Poin</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $rule)
                        <tr>
                            <td class="fw-bold">{{ $rule->rule_name }}</td>
                            <td>Rp {{ number_format($rule->min_purchase, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-light-primary text-primary">
                                    {{ $rule->points_earned }} Poin
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $rule->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $rule->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group gap-1">
                                    <button 
                                        class="btn btn-sm btn-outline-warning rounded-pill px-3 btn-edit"
                                        data-id="{{ $rule->id }}"
                                        data-name="{{ $rule->rule_name }}"
                                        data-min="{{ $rule->min_purchase }}"
                                        data-point="{{ $rule->points_earned }}"
                                        data-status="{{ $rule->is_active }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#ruleModal">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    
                                    @if($rule->is_active)
                                    <button 
                                        class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-deactivate"
                                        data-id="{{ $rule->id }}"
                                        data-name="{{ $rule->rule_name }}">
                                        <i class="bi bi-pause-circle"></i> Nonaktifkan
                                    </button>
                                    @else
                                    <button 
                                        class="btn btn-sm btn-outline-success rounded-pill px-3 btn-activate"
                                        data-id="{{ $rule->id }}"
                                        data-name="{{ $rule->rule_name }}">
                                        <i class="bi bi-play-circle"></i> Aktifkan
                                    </button>
                                    @endif
                                    
                                    <button 
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-delete"
                                        data-id="{{ $rule->id }}"
                                        data-name="{{ $rule->rule_name }}">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL UNTUK TAMBAH/EDIT --}}
<div class="modal fade" id="ruleModal">
    <div class="modal-dialog modal-dialog-centered">
        <form id="ruleForm" method="POST" class="modal-content">
            @csrf
            <input type="hidden" id="formMethod" name="_method" value="POST">

            <div class="modal-header">
                <h5 id="modalTitle">Tambah Aturan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">Nama Aturan <span class="text-danger">*</span></label>
                    <input type="text" id="ruleName" name="rule_name" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Min. Belanja (Rp) <span class="text-danger">*</span></label>
                        <input type="number" id="ruleMin" name="min_purchase" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Poin yang Didapat <span class="text-danger">*</span></label>
                        <input type="number" id="rulePoint" name="points_earned" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3 d-none" id="statusField">
                    <label class="form-label">Status</label>
                    <select id="ruleStatus" name="is_active" class="form-select">
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>
                    <small class="text-muted">Hanya satu aturan yang bisa aktif dalam satu waktu</small>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>

        </form>
    </div>
</div>

{{-- MODAL KONFIRMASI HAPUS --}}
<div class="modal fade" id="deleteModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus aturan <strong id="deleteRuleName"></strong>?</p>
                <p class="text-danger small">Aturan yang aktif tidak dapat dihapus. Nonaktifkan terlebih dahulu.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI AKTIF/NONAKTIF --}}
<div class="modal fade" id="statusModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="statusModalHeader">
                <h5 class="modal-title" id="statusModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="statusModalMessage"></p>
            </div>
            <div class="modal-footer">
                <form id="statusForm" method="POST">
                    @csrf
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn" id="statusModalButton"></button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('ruleForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodInput = document.getElementById('formMethod');

    const nameInput = document.getElementById('ruleName');
    const minInput = document.getElementById('ruleMin');
    const pointInput = document.getElementById('rulePoint');
    const statusInput = document.getElementById('ruleStatus');
    const statusField = document.getElementById('statusField');

    // =========================
    // TAMBAH ATURAN
    // =========================
    document.getElementById('btnAdd').addEventListener('click', function () {
        form.action = "{{ route($routePrefix . '.loyalty.rule.store') }}";
        methodInput.value = "POST";
        modalTitle.innerText = "Tambah Aturan";

        nameInput.value = "";
        minInput.value = "";
        pointInput.value = "";
        statusField.classList.add('d-none');
    });

    // =========================
    // EDIT ATURAN
    // =========================
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            form.action = "{{ url($routePrefix . '/loyalty/rule') }}/" + id;
            methodInput.value = "PUT";
            modalTitle.innerText = "Edit Aturan";

            nameInput.value = this.dataset.name;
            minInput.value = this.dataset.min;
            pointInput.value = this.dataset.point;
            statusInput.value = this.dataset.status;

            statusField.classList.remove('d-none');
        });
    });

    // =========================
    // AKTIFKAN ATURAN
    // =========================
    document.querySelectorAll('.btn-activate').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            const modal = new bootstrap.Modal(document.getElementById('statusModal'));
            const statusForm = document.getElementById('statusForm');
            
            document.getElementById('statusModalHeader').className = 'modal-header bg-success text-white';
            document.getElementById('statusModalTitle').innerText = 'Konfirmasi Aktivasi';
            document.getElementById('statusModalMessage').innerHTML = `Apakah Anda yakin ingin mengaktifkan aturan <strong>${name}</strong>?<br><small class="text-warning">Aturan yang aktif sebelumnya akan otomatis dinonaktifkan.</small>`;
            document.getElementById('statusModalButton').className = 'btn btn-success';
            document.getElementById('statusModalButton').innerText = 'Ya, Aktifkan';
            
            statusForm.action = "{{ url($routePrefix . '/loyalty/rule') }}/" + id + "/activate";
            statusForm.method = "POST";
            
            // Tambahkan CSRF token jika belum ada
            if (!statusForm.querySelector('input[name="_token"]')) {
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                statusForm.appendChild(csrfToken);
            }
            
            modal.show();
        });
    });

    // =========================
    // NONAKTIFKAN ATURAN
    // =========================
    document.querySelectorAll('.btn-deactivate').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            const modal = new bootstrap.Modal(document.getElementById('statusModal'));
            const statusForm = document.getElementById('statusForm');
            
            document.getElementById('statusModalHeader').className = 'modal-header bg-warning text-dark';
            document.getElementById('statusModalTitle').innerText = 'Konfirmasi Nonaktifasi';
            document.getElementById('statusModalMessage').innerHTML = `Apakah Anda yakin ingin menonaktifkan aturan <strong>${name}</strong>?`;
            document.getElementById('statusModalButton').className = 'btn btn-warning';
            document.getElementById('statusModalButton').innerText = 'Ya, Nonaktifkan';
            
            statusForm.action = "{{ url($routePrefix . '/loyalty/rule') }}/" + id + "/deactivate";
            statusForm.method = "POST";
            
            if (!statusForm.querySelector('input[name="_token"]')) {
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                statusForm.appendChild(csrfToken);
            }
            
            modal.show();
        });
    });

    // =========================
    // HAPUS ATURAN
    // =========================
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const deleteForm = document.getElementById('deleteForm');
            
            document.getElementById('deleteRuleName').innerText = name;
            deleteForm.action = "{{ url($routePrefix . '/loyalty/rule') }}/" + id;
            deleteForm.method = "POST";
            
            // Tambahkan CSRF token dan method DELETE
            if (!deleteForm.querySelector('input[name="_token"]')) {
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                deleteForm.appendChild(csrfToken);
            }
            
            if (!deleteForm.querySelector('input[name="_method"]')) {
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                deleteForm.appendChild(methodField);
            }
            
            modal.show();
        });
    });

    // =========================
    // VALIDASI FORM
    // =========================
    form.addEventListener('submit', function(e){
        if (parseInt(minInput.value) <= 0 || parseInt(pointInput.value) <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Input Tidak Valid',
                text: 'Nominal dan poin harus lebih dari 0'
            });
        }
    });

    // =========================
    // ALERT NOTIFICATION
    // =========================
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: "{{ session('success') }}",
        timer: 2000,
        showConfirmButton: false
    });
    @endif

    @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: "{{ session('error') }}",
        timer: 3000,
        showConfirmButton: true
    });
    @endif

});
</script>
@endpush