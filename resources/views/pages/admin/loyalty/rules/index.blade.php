@extends('layouts.admin')

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
                                <button 
                                    class="btn btn-sm btn-outline-warning rounded-pill px-3 btn-edit"
                                    data-id="{{ $rule->id }}"
                                    data-name="{{ $rule->rule_name }}"
                                    data-min="{{ $rule->min_purchase }}"
                                    data-point="{{ $rule->points_earned }}"
                                    data-status="{{ $rule->is_active }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#ruleModal">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL UNIVERSAL --}}
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
                    <label class="form-label">Nama Aturan</label>
                    <input type="text" id="ruleName" name="rule_name" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Min. Belanja</label>
                        <input type="number" id="ruleMin" name="min_purchase" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Poin</label>
                        <input type="number" id="rulePoint" name="points_earned" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3 d-none" id="statusField">
                    <label class="form-label">Status</label>
                    <select id="ruleStatus" name="is_active" class="form-select">
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
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
    // TAMBAH
    // =========================
    document.getElementById('btnAdd').addEventListener('click', function () {
        form.action = "{{ route('admin.loyalty.rule.store') }}";
        methodInput.value = "POST";
        modalTitle.innerText = "Tambah Aturan";

        nameInput.value = "";
        minInput.value = "";
        pointInput.value = "";
        statusField.classList.add('d-none');
    });

    // =========================
    // EDIT
    // =========================
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function () {

            const id = this.dataset.id;

            form.action = `/admin/loyalty/rule/${id}`;
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
    // VALIDASI
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
    // ALERT
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

});
</script>
@endpush