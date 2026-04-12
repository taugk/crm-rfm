@extends('layouts.admin')

@section('title', 'Data Pelanggan')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Data Pelanggan</h3>
                <p class="text-subtitle text-muted">Kelola profil, kontak, dan loyalitas member cafe Anda.</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    {{-- ================= FILTER AREA ================= --}}
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold small">Cari Pelanggan</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" id="jsSearchInput" class="form-control" placeholder="Cari nama, email, atau no. telp...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Status Akun</label>
                        <select id="jsStatusFilter" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Non-Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button id="jsResetBtn" class="btn btn-light-secondary w-100 shadow-sm fw-bold">
                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TABLE AREA ================= --}}
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 py-3">
                <h5 class="mb-0">Daftar Pelanggan</h5>
                <div class="d-flex gap-2">
                    {{-- Tombol Export --}}
                    <a href="{{ route('admin.customers.export') }}" id="btnExport" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export
                    </a>

                    {{-- Tombol Import (Trigger Hidden Input) --}}
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="document.getElementById('importExcelInput').click()">
                        <i class="bi bi-upload me-1"></i> Import
                    </button>
                    
                    {{-- Form Import Tersembunyi --}}
                    <form action="{{ route('admin.customers.import') }}" method="POST" enctype="multipart/form-data" id="finalImportForm" class="d-none">
                        @csrf
                        <input type="file" name="file" id="importExcelInput" accept=".xlsx, .xls">
                    </form>

                    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm shadow-sm">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="pelangganTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Pelanggan</th>
                                <th>Kontak</th>
                                <th>Alamat</th>
                                <th class="text-center">Poin</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="pelangganTableBody">
                            @forelse($data as $p)
                            <tr class="pelanggan-row">
                                <td class="text-center small">{{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md me-3">
                                            <img src="{{ $p->profile_photo ? asset($p->profile_photo) : 'https://www.w3schools.com/howto/img_avatar.png' }}" 
                                                 class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        </div>
                                        <div>
                                            <span class="fw-bold pelanggan-name text-dark d-block">{{ $p->name }}</span>
                                            <small class="text-muted">#PLG-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="pelanggan-email mb-1 text-truncate" style="max-width: 150px;"><i class="bi bi-envelope me-1"></i> {{ $p->email ?? '-' }}</div>
                                        <div class="pelanggan-phone text-muted"><i class="bi bi-telephone me-1"></i> {{ $p->phone }}</div>
                                    </div>
                                </td>
                                <td>
                                    <p class="small text-truncate mb-0" style="max-width: 150px;" title="{{ $p->full_address }}">
                                        {{ $p->full_address ?? '-' }}
                                    </p>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light-primary text-primary fw-bold">{{ number_format($p->total_points) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $p->status == 'active' ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }} pelanggan-status px-3">
                                        {{ ucfirst($p->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group gap-1">
                                        <a href="{{ route('admin.customers.show', $p->id) }}" class="btn btn-sm btn-info text-white rounded-2"> Detail</a>
                                        <a href="{{ route('admin.customers.edit', $p->id) }}" class="btn btn-sm btn-warning text-dark rounded-2"> Edit</a>
                                        <form action="{{ route('admin.customers.destroy', $p->id) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger rounded-2 delete-confirm"> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">Data pelanggan tidak ditemukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <p class="text-muted small">Menampilkan {{ $data->firstItem() ?? 0 }} - {{ $data->lastItem() ?? 0 }} dari {{ $data->total() }} pelanggan</p>
                    <nav>{{ $data->links('pagination::bootstrap-5') }}</nav>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- ================= MODAL PREVIEW IMPORT ================= --}}
<div class="modal fade" id="previewModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Konfirmasi Data Import Pelanggan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="resetImport()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i> Periksa kembali data di bawah ini. Menampilkan maksimal 10 baris pertama.
                </div>
                <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead id="previewHead" class="table-light" style="position: sticky; top: 0; z-index: 1;"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <a  href="{{ route('admin.customers') }}" class="btn btn-secondary">Batal</a>
                <button type="button" class="btn btn-success px-4" onclick="document.getElementById('finalImportForm').submit()">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload Sekarang
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Library SheetJS untuk baca Excel di Browser --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('jsSearchInput');
    const statusFilter = document.getElementById('jsStatusFilter');
    const resetBtn = document.getElementById('jsResetBtn');
    const tableRows = document.querySelectorAll('.pelanggan-row');
    const importInput = document.getElementById('importExcelInput');
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));

    /** 1. FILTERING LOGIC **/
    const filterTable = () => {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedStatus = statusFilter.value.toLowerCase();

    // 1. Logika Filter Baris Tabel (Client-side)
    tableRows.forEach(row => {
        const name = row.querySelector('.pelanggan-name').textContent.toLowerCase();
        const email = row.querySelector('.pelanggan-email').textContent.toLowerCase();
        const phone = row.querySelector('.pelanggan-phone').textContent.toLowerCase();
        const status = row.querySelector('.pelanggan-status').textContent.toLowerCase().trim();

        const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm) || phone.includes(searchTerm);
        const matchesStatus = selectedStatus === "" || status === selectedStatus;
        row.style.display = (matchesSearch && matchesStatus) ? "" : "none";
    });

    // 2. Logika Update URL Export secara Dinamis
    const exportBtn = document.getElementById('btnExport');
    if (exportBtn) {
        
        const baseUrl = "{{ route('admin.customers.export') }}";
        

        const params = new URLSearchParams();
        if (searchInput.value) params.append('search', searchInput.value);
        if (statusFilter.value) params.append('status', statusFilter.value);


        const queryString = params.toString();
        exportBtn.href = queryString ? `${baseUrl}?${queryString}` : baseUrl;
    }
};

    searchInput?.addEventListener('input', filterTable);
    statusFilter?.addEventListener('change', filterTable);
    resetBtn?.addEventListener('click', () => {
        searchInput.value = ""; statusFilter.value = ""; filterTable();
    });

    /** 2. IMPORT PREVIEW LOGIC **/
    window.resetImport = function() {
        importInput.value = ""; 
        document.getElementById('previewHead').innerHTML = "";
        document.getElementById('previewBody').innerHTML = "";
    };

    importInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validasi Ekstensi
        const ext = file.name.split('.').pop().toLowerCase();
        if(!['xlsx', 'xls'].includes(ext)) {
            Swal.fire('Error', 'Format file harus Excel (.xlsx atau .xls)', 'error');
            resetImport();
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                const sheetData = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]], {header: 1});
                
                if (sheetData.length > 0) {
                    // Build Header
                    let headHtml = "<tr>"; 
                    sheetData[0].forEach(h => headHtml += `<th class="py-2 px-3 small text-uppercase">${h || '-'}</th>`); 
                    headHtml += "</tr>";
                    document.getElementById('previewHead').innerHTML = headHtml;

                    // Build Body (Preview 10 baris)
                    let bodyHtml = "";
                    const rows = sheetData.slice(1, 11); 
                    rows.forEach(row => {
                        bodyHtml += "<tr>"; 
                        row.forEach(cell => bodyHtml += `<td class="py-2 px-3 small">${cell || '-'}</td>`); 
                        bodyHtml += "</tr>";
                    });
                    document.getElementById('previewBody').innerHTML = bodyHtml;

                    previewModal.show();
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal membaca file Excel!', 'error');
                resetImport();
            }
        };
        reader.readAsArrayBuffer(file);
    });
});
</script>
@endpush