@php
    $layout = match(auth()->user()->role) {
        'manager' => 'layouts.manager',
        'admin'   => 'layouts.admin',
        default   => 'layouts.admin',
    };
    $routePrefix = match(auth()->user()->role) {
        'manager' => 'manager',
        'admin'   => 'admin',
        default   => 'admin',
    };
@endphp

@extends($layout)

@section('title', 'Data Promosi')

@section('content')

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Data Promosi</h3>
                <p class="text-subtitle text-muted">
                    Kelola diskon, voucher, dan penawaran khusus untuk pelanggan Anda.
                </p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Data Promosi</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="row">
        @php
            $totalAll    = $promos->count();
            $totalAktif  = $promos->filter(fn($p) => strtolower(strip_tags($p->status_label)) === 'aktif')->count();
            $totalMendatang = $promos->filter(fn($p) => strtolower(strip_tags($p->status_label)) === 'mendatang')->count();
            $totalSelesai   = $promos->filter(fn($p) => strtolower(strip_tags($p->status_label)) === 'selesai')->count();
        @endphp

        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon purple mb-2">
                                <i class="iconly-boldDiscount icon-lg"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Total Promosi</h6>
                            <h6 class="font-extrabold mb-0">{{ $totalAll }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon green mb-2">
                                <i class="iconly-boldTicket-Star icon-lg"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Aktif</h6>
                            <h6 class="font-extrabold mb-0">{{ $totalAktif }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon blue mb-2">
                                <i class="iconly-boldCalendar icon-lg"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Mendatang</h6>
                            <h6 class="font-extrabold mb-0">{{ $totalMendatang }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon red mb-2">
                                <i class="iconly-boldDanger icon-lg"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Selesai</h6>
                            <h6 class="font-extrabold mb-0">{{ $totalSelesai }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-content">

    {{-- ── Filter & Action Bar ── --}}
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Cari Promosi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="jsSearchInput" class="form-control"
                                   placeholder="Nama promo atau kode...">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="jsStatusFilter" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="mendatang">Mendatang</option>
                            <option value="selesai">Selesai</option>
                            <option value="non-aktif">Non-Aktif</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <button id="jsResetBtn" class="btn btn-light-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset
                            </button>

                            {{-- Export --}}
                            <a href="{{ route($routePrefix . '.promo.export') }}"
                               class="btn btn-success">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export
                            </a>

                            {{-- Import --}}
                            <button type="button" class="btn btn-info text-white"
                                    onclick="document.getElementById('importExcelInput').click()">
                                <i class="bi bi-upload me-1"></i> Import
                            </button>
                            <form action="{{ route($routePrefix . '.promo.import') }}" method="POST"
                                  enctype="multipart/form-data" id="finalImportForm" class="d-none">
                                @csrf
                                <input type="file" name="file" id="importExcelInput" accept=".xlsx,.xls">
                            </form>

                            {{-- Tambah --}}
                            <a href="{{ route($routePrefix . '.promo.create') }}"
                               class="btn btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Tabel Promosi ── --}}
    <section class="section">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Daftar Promosi</h4>
                <p class="text-subtitle text-muted mb-0">
                    Total <strong>{{ $promos->count() }}</strong> promosi tersedia
                </p>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="promoTable">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Informasi Promosi</th>
                                <th>Diskon</th>
                                <th class="text-center">Kuota (Terpakai)</th>
                                <th>Masa Berlaku</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:200px">Aksi</th>
                            </tr>
                        </thead>

                        <tbody id="promoTableBody">
                            @forelse($promos as $promo)
                            <tr class="promo-row">
                                {{-- No --}}
                                <td class="text-muted small">{{ $loop->iteration }}</td>

                                {{-- Info Promosi --}}
                                <td>
                                    <p class="font-bold mb-0 text-capitalize promo-name">
                                        {{ $promo->promo_name }}
                                    </p>
                                    <span class="badge bg-light-secondary text-secondary font-monospace promo-code">
                                        {{ $promo->promo_code ?? 'TANPA KODE' }}
                                    </span>
                                </td>

                                {{-- Diskon --}}
                                <td>
                                    <span class="font-bold text-success">
                                        @if($promo->discount_type === 'percentage')
                                            {{ number_format($promo->discount_value, 0) }}%
                                        @else
                                            Rp {{ number_format($promo->discount_value, 0, ',', '.') }}
                                        @endif
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        Min: Rp {{ number_format($promo->min_spend, 0, ',', '.') }}
                                    </small>
                                </td>

                                {{-- Kuota --}}
                                <td class="text-center">
                                    <span class="font-bold
                                        {{ $promo->used_count >= ($promo->usage_limit ?? PHP_INT_MAX)
                                            ? 'text-danger' : '' }}">
                                        {{ $promo->used_count }}
                                    </span>
                                    <span class="text-muted">/ {{ $promo->usage_limit ?? '∞' }}</span>

                                    @if($promo->usage_limit)
                                    <div class="progress mt-1" style="height:4px;">
                                        @php
                                            $pct = min(100, round($promo->used_count / $promo->usage_limit * 100));
                                            $color = $pct >= 100 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                                        @endphp
                                        <div class="progress-bar {{ $color }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                    @endif
                                </td>

                                {{-- Masa Berlaku --}}
                                <td>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-play-circle me-1 text-success"></i>
                                        {{ $promo->start_date->format('d M Y, H:i') }}
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-stop-circle me-1 text-danger"></i>
                                        {{ $promo->end_date->format('d M Y, H:i') }}
                                    </small>
                                </td>

                                {{-- Status --}}
                                <td class="text-center promo-status">
                                    {!! $promo->status_label !!}
                                </td>

                                {{-- Aksi --}}
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route($routePrefix . '.promo.show', $promo->id) }}"
                                           class="btn btn-sm btn-info text-white"
                                           title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route($routePrefix . '.promo.edit', $promo->id) }}"
                                           class="btn btn-sm btn-warning"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-danger btn-delete"
                                                data-id="{{ $promo->id }}"
                                                data-name="{{ $promo->promo_name }}"
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center gap-2 text-muted">
                                        <i class="bi bi-megaphone fs-1"></i>
                                        <span>Belum ada data promosi.</span>
                                        <a href="{{ route($routePrefix . '.promo.create') }}"
                                           class="btn btn-primary btn-sm mt-1">
                                            <i class="bi bi-plus-lg me-1"></i> Tambah Promosi
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

</div>{{-- /page-content --}}


{{-- ══════════ MODAL PREVIEW IMPORT ══════════ --}}
<div class="modal fade" id="previewModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-eye me-2"></i>Konfirmasi Import Promo
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" onclick="resetImport()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light-info">
                    <i class="bi bi-info-circle me-1"></i>
                    Pratinjau <strong>10 baris pertama</strong> dari file yang Anda pilih.
                    Pastikan data sudah benar sebelum melanjutkan.
                </div>
                <div class="table-responsive border rounded"
                     style="max-height:400px; overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead id="previewHead" class="table-light"
                               style="position:sticky;top:0;z-index:1;"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary"
                        data-bs-dismiss="modal" onclick="resetImport()">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-success"
                        onclick="document.getElementById('finalImportForm').submit()">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload Sekarang
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══════════ MODAL HAPUS ══════════ --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus promosi
                   <strong id="deletePromoName"></strong>?
                </p>
                <div class="alert alert-light-danger py-2 mb-0 small">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Tindakan ini <strong>tidak dapat dibatalkan</strong>.
                    Semua data terkait promosi ini akan ikut terhapus.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary"
                        data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection


@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* ── Refs ── */
    const searchInput   = document.getElementById('jsSearchInput');
    const statusFilter  = document.getElementById('jsStatusFilter');
    const resetBtn      = document.getElementById('jsResetBtn');
    const tableRows     = document.querySelectorAll('.promo-row');
    const importInput   = document.getElementById('importExcelInput');
    const previewModal  = new bootstrap.Modal(document.getElementById('previewModal'));
    const deleteModal   = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteForm    = document.getElementById('deleteForm');
    const deletePromoName = document.getElementById('deletePromoName');
    const routePrefix   = '{{ $routePrefix }}';

    /* ── Filter ── */
    const filterTable = () => {
        const q  = searchInput.value.toLowerCase();
        const st = statusFilter.value.toLowerCase();

        tableRows.forEach(row => {
            const name   = row.querySelector('.promo-name')?.textContent.toLowerCase()   || '';
            const code   = row.querySelector('.promo-code')?.textContent.toLowerCase()   || '';
            const status = row.querySelector('.promo-status')?.textContent.toLowerCase().trim() || '';

            const matchSearch = !q || name.includes(q) || code.includes(q);
            const matchStatus = !st || status === st;

            row.style.display = (matchSearch && matchStatus) ? '' : 'none';
        });
    };

    searchInput?.addEventListener('input',  filterTable);
    statusFilter?.addEventListener('change', filterTable);
    resetBtn?.addEventListener('click', () => {
        searchInput.value  = '';
        statusFilter.value = '';
        filterTable();
    });

    /* ── Hapus ── */
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function () {
            deletePromoName.textContent = this.dataset.name;
            deleteForm.action = `/${routePrefix}/promotions/${this.dataset.id}`;
            deleteModal.show();
        });
    });

    /* ── Import helpers ── */
    window.resetImport = () => {
        importInput.value = '';
        document.getElementById('previewHead').innerHTML = '';
        document.getElementById('previewBody').innerHTML = '';
    };

    importInput?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            try {
                const wb   = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
                const data = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], { header: 1 });

                if (!data.length) {
                    Swal.fire('Perhatian', 'File Excel kosong!', 'warning');
                    return resetImport();
                }

                /* Header */
                document.getElementById('previewHead').innerHTML =
                    '<tr>' + data[0].map(h =>
                        `<th class="py-2 px-3 small text-uppercase text-nowrap">${h ?? '-'}</th>`
                    ).join('') + '</tr>';

                /* Rows (maks 10) */
                document.getElementById('previewBody').innerHTML =
                    data.slice(1, 11).map(row =>
                        '<tr>' + row.map(c =>
                            `<td class="py-2 px-3 small">${c ?? '-'}</td>`
                        ).join('') + '</tr>'
                    ).join('');

                previewModal.show();
            } catch {
                Swal.fire('Error', 'Gagal membaca file Excel!', 'error');
                resetImport();
            }
        };
        reader.readAsArrayBuffer(file);
    });

    /* ── Notifikasi session ── */
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '{{ session("success") }}',
        timer: 2500,
        showConfirmButton: false
    });
    @endif

    @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: '{{ session("error") }}',
        timer: 3000,
        showConfirmButton: true
    });
    @endif

});
</script>
@endpush