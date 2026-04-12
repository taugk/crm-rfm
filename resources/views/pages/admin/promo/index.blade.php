@extends('layouts.admin')

@section('title', 'Data Promosi')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Data Promosi</h3>
                <p class="text-subtitle text-muted">Kelola diskon, voucher, dan penawaran khusus untuk pelanggan Anda.</p>
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
                        <label class="form-label fw-bold small">Cari Promosi</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" id="jsSearchInput" class="form-control" placeholder="Cari nama atau kode promo...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Status Promosi</label>
                        <select id="jsStatusFilter" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="mendatang">Mendatang</option>
                            <option value="selesai">Selesai</option>
                            <option value="non-aktif">Non-Aktif</option>
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
                <h5 class="mb-0">Daftar Promosi</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.promo.export') }}" id="btnExport" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export
                    </a>

                    <button type="button" class="btn btn-outline-success btn-sm" onclick="document.getElementById('importExcelInput').click()">
                        <i class="bi bi-upload me-1"></i> Import
                    </button>
                    
                    <form action="{{ route('admin.promo.import') }}" method="POST" enctype="multipart/form-data" id="finalImportForm" class="d-none">
                        @csrf
                        <input type="file" name="file" id="importExcelInput" accept=".xlsx, .xls">
                    </form>

                    <a href="{{ route('admin.promo.create') }}" class="btn btn-primary btn-sm shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Promo
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="promoTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Informasi Promosi</th>
                                <th>Diskon</th>
                                <th class="text-center">Kuota (Terpakai)</th>
                                <th>Masa Berlaku</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="promoTableBody">
                            @forelse($promos as $promo)
                            <tr class="promo-row">
                                <td class="text-center small">{{ $loop->iteration }}</td>
                                <td>
                                    <div>
                                        <span class="fw-bold promo-name text-dark d-block text-capitalize">{{ $promo->promo_name }}</span>
                                        <code class="promo-code text-primary small">{{ $promo->promo_code ?? 'TANPA KODE' }}</code>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold">
                                        {{ $promo->discount_type == 'percentage' ? number_format($promo->discount_value, 0).'%' : 'Rp '.number_format($promo->discount_value, 0, ',', '.') }}
                                    </span>
                                    <br>
                                    <small class="text-muted">Min: Rp{{ number_format($promo->min_spend, 0, ',', '.') }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold">{{ $promo->used_count }}</span> 
                                    <span class="text-muted">/ {{ $promo->usage_limit ?? '∞' }}</span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="text-muted"><i class="bi bi-calendar-event me-1"></i> {{ $promo->start_date->format('d M Y H:i') }}</div>
                                        <div class="text-muted"><i class="bi bi-calendar-check me-1"></i> {{ $promo->end_date->format('d M Y H:i') }}</div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="promo-status">
                                        {!! $promo->status_label !!}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group gap-1">
                                        <a href="{{ route('admin.promo.show', $promo->id) }}" class="btn btn-sm btn-info text-white rounded-2">Detail</a>
                                        <a href="{{ route('admin.promo.edit', $promo->id) }}" class="btn btn-sm btn-warning text-dark rounded-2">Edit</a>
                                        <form action="{{ route('admin.promo.destroy', $promo->id) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger rounded-2 delete-confirm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada data promosi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- MODAL PREVIEW IMPORT --}}
<div class="modal fade" id="previewModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Konfirmasi Import Promo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="resetImport()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i> Pratinjau 10 baris pertama dari file Anda.
                </div>
                <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead id="previewHead" class="table-light" style="position: sticky; top: 0; z-index: 1;"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetImport()">Batal</button>
                <button type="button" class="btn btn-success px-4" onclick="document.getElementById('finalImportForm').submit()">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload Sekarang
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('jsSearchInput');
    const statusFilter = document.getElementById('jsStatusFilter');
    const resetBtn = document.getElementById('jsResetBtn');
    const tableRows = document.querySelectorAll('.promo-row');
    const importInput = document.getElementById('importExcelInput');
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));

    const filterTable = () => {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value.toLowerCase();

        tableRows.forEach(row => {
            const name = row.querySelector('.promo-name').textContent.toLowerCase();
            const code = row.querySelector('.promo-code').textContent.toLowerCase();
            const status = row.querySelector('.promo-status').textContent.toLowerCase().trim();

            const matchesSearch = name.includes(searchTerm) || code.includes(searchTerm);
            
            // Logika Filter Status disesuaikan dengan teks badge
            let matchesStatus = false;
            if (selectedStatus === "") {
                matchesStatus = true;
            } else if (selectedStatus === "aktif" && status === "aktif") {
                matchesStatus = true;
            } else if (selectedStatus === "mendatang" && status === "mendatang") {
                matchesStatus = true;
            } else if (selectedStatus === "selesai" && status === "selesai") {
                matchesStatus = true;
            } else if (selectedStatus === "non-aktif" && status === "non-aktif") {
                matchesStatus = true;
            }

            row.style.display = (matchesSearch && matchesStatus) ? "" : "none";
        });
    };

    searchInput?.addEventListener('input', filterTable);
    statusFilter?.addEventListener('change', filterTable);
    resetBtn?.addEventListener('click', () => {
        searchInput.value = ""; 
        statusFilter.value = ""; 
        filterTable();
    });

    window.resetImport = function() {
        importInput.value = ""; 
        document.getElementById('previewHead').innerHTML = "";
        document.getElementById('previewBody').innerHTML = "";
    };

    importInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                const sheetData = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]], {header: 1});
                
                if (sheetData.length > 0) {
                    let headHtml = "<tr>"; 
                    sheetData[0].forEach(h => headHtml += `<th class="py-2 px-3 small text-uppercase">${h || '-'}</th>`); 
                    headHtml += "</tr>";
                    document.getElementById('previewHead').innerHTML = headHtml;

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