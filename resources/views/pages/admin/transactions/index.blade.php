@extends('layouts.admin')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Riwayat Transaksi</h3>
                <p class="text-subtitle text-muted">Pantau aktivitas penjualan dan penggunaan promo pelanggan Anda.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first text-end">
                <a href="{{ route('admin.transactions.create') }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-cart-plus-fill me-2"></i> Transaksi Baru (POS)
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    {{-- ================= STATISTIK ================= --}}
    <section class="row">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5 text-center">
                    <h6 class="text-muted font-semibold small uppercase">Total Trx</h6>
                    <h5 class="font-extrabold mb-0">{{ number_format($data->total()) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5 text-center">
                    <h6 class="text-muted font-semibold small uppercase text-success">Omzet Bersih</h6>
                    {{-- Menghitung total omzet dari koleksi yang ada --}}
                    <h5 class="font-extrabold mb-0">Rp{{ number_format($data->sum('total_price')) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5 text-center">
                    <h6 class="text-muted font-semibold small uppercase text-danger">Total Diskon</h6>
                    <h5 class="font-extrabold mb-0 text-danger">Rp{{ number_format($data->sum('discount_amount')) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5 text-center">
                    <h6 class="text-muted font-semibold small uppercase text-primary">Tax (11%)</h6>
                    <h5 class="font-extrabold mb-0">Rp{{ number_format($data->sum('tax_total')) }}</h5>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FILTER AREA ================= --}}
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.transactions') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Cari Transaksi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="Invoice / Pelanggan..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold">Filter</button>
                            <a href="{{ route('admin.transactions') }}" class="btn btn-light-secondary shadow-sm fw-bold"><i class="bi bi-arrow-clockwise"></i></a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ================= TABEL DATA ================= --}}
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 py-3">
                <h5 class="mb-0">Daftar Transaksi</h5>
                <div class="btn-group gap-2">
                    <a href="{{ route('admin.transactions.export', request()->query()) }}" class="btn btn-success btn-sm shadow-sm rounded-2">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm shadow-sm rounded-2" data-bs-toggle="modal" data-bs-target="#modalImport">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Promo & Diskon</th>
                                <th>Tanggal</th>
                                <th class="text-end">Total Akhir</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $trx)
                            <tr>
                                <td class="fw-bold text-primary">#{{ $trx->invoice_number }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $trx->customer->name ?? 'Guest' }}</div>
                                    <small class="badge bg-light-info text-info" style="font-size: 0.65rem;">{{ strtoupper($trx->payment_method) }}</small>
                                </td>
                                <td>
    @if($trx->promotion)
        {{-- Nama Promo --}}
        <div class="small fw-bold text-success">
            <i class="bi bi-ticket-perk-fill me-1"></i>{{ $trx->promotion->promo_name }}
        </div>

        {{-- Detail Konfigurasi Promo --}}
        <div class="text-muted" style="font-size: 0.75rem;">
            Diskon: 
            <span class="fw-bold">
                @if($trx->promotion->discount_type == 'percentage')
                    {{ number_format($trx->promotion->discount_value, 0) }}%
                @else
                    Rp{{ number_format($trx->promotion->discount_value, 0, ',', '.') }}
                @endif
            </span>
        </div>

        {{-- Nominal Potongan yang Berhasil Masuk ke Database --}}
        <div class="small text-danger fw-bold">
            Potongan: - Rp{{ number_format($trx->discount_amount, 0, ',', '.') }}
        </div>
    @else
        <span class="text-muted small">Tidak ada promo</span>
    @endif
</td>
                                <td class="small">
                                    {{ $trx->transaction_date->format('d M Y') }}<br>
                                    <span class="text-muted">{{ $trx->transaction_date->format('H:i') }} WIB</span>
                                </td>
                                <td class="text-end fw-bold">Rp{{ number_format($trx->total_price) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $trx->status == 'completed' ? 'bg-light-success text-success' : ($trx->status == 'pending' ? 'bg-light-warning text-warning' : 'bg-light-danger text-danger') }} px-3">
                                        {{ ucfirst($trx->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group gap-1">
                                        <a href="{{ route('admin.transactions.show', $trx->id) }}" class="btn btn-sm btn-info text-white rounded-2"><i class="bi bi-eye"></i></a>
                                        <button class="btn btn-sm btn-secondary rounded-2" onclick="window.print()"><i class="bi bi-printer"></i></button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada transaksi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <p class="text-muted small">Menampilkan {{ $data->firstItem() ?? 0 }} - {{ $data->lastItem() ?? 0 }} dari {{ $data->total() }} transaksi</p>
                    <nav>{{ $data->links('pagination::bootstrap-5') }}</nav>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- ================= MODAL IMPORT ================= --}}
{{-- ================= MODAL IMPORT ================= --}}
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('admin.transactions.import') }}" method="POST" enctype="multipart/form-data" id="formImport">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Import Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    {{-- TAMBAHAN DOWNLOAD TEMPLATE (TIDAK MENGUBAH UI) --}}
                    <div class="text-end mb-2">
                        <button type="button" class="btn btn-success btn-sm" id="downloadTemplate">
                            <i class="bi bi-download me-1"></i> Download Template
                        </button>
                    </div>

                    {{-- DRAG & DROP --}}
                    <div id="dropArea" class="border border-2 border-dashed rounded p-4 text-center mb-3" style="cursor:pointer;">
                        <p class="mb-1 fw-bold">Drag & Drop File Excel</p>
                        <small class="text-muted">atau klik untuk pilih file</small>

                        {{-- FIX: jangan pakai hidden --}}
                        <input type="file" name="file" id="inputExcel" class="d-none" accept=".xlsx,.xls">
                    </div>

                    {{-- PROGRESS BAR --}}
                    <div class="progress mb-3" style="height:20px; display:none;" id="progressContainer">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" style="width:0%">0%</div>
                    </div>

                    {{-- PREVIEW --}}
                    <div id="previewArea" style="display:none;">
                        <h6 class="fw-bold text-primary">Preview Data</h6>
                        <div class="table-responsive" style="max-height:400px;">
                            <table class="table table-sm table-bordered" id="tablePreview">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitImport" disabled>
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const dropArea = document.getElementById('dropArea');
    const inputExcel = document.getElementById('inputExcel');
    const previewArea = document.getElementById('previewArea');
    const tablePreview = document.getElementById('tablePreview');
    const btnSubmit = document.getElementById('btnSubmitImport');
    const progressBar = document.getElementById('progressBar');
    const progressContainer = document.getElementById('progressContainer');
    const btnDownloadTemplate = document.getElementById('downloadTemplate');

    const TEMPLATE_HEADERS = [
        "invoice_number",
        "transaction_date",
        "customer_name",
        "customer_phone_or_email",
        "payment_method",
        "status",
        "product_name",
        "variant",
        "quantity",
        "price_at_purchase",
        "discount_amount",
        "tax_total",
        "notes",
        "promotion_code",
        "subtotal"
    ];

    const REQUIRED_HEADERS = [
        "invoice_number",
        "customer_phone_or_email",
        "product_name",
        "subtotal"
    ];

    // ================= DOWNLOAD TEMPLATE =================
    if (btnDownloadTemplate) {
        btnDownloadTemplate.addEventListener('click', () => {
            const data = [
                TEMPLATE_HEADERS,
                ["INV-001","2024-03-25","John Doe","08123456789","cash","completed","Kopi Susu","","2","15000","0","3300","Less sugar","", "18300"],
                ["INV-001","2024-03-25","John Doe","08123456789","cash","completed","Roti Bakar","","1","20000","0","2200","Extra cheese","","22200"]
            ];

            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Template");
            XLSX.writeFile(wb, "Template_Import_Transaksi.xlsx");
        });
    }

    // ================= FIX CLICK =================
    if (dropArea && inputExcel) {
        dropArea.addEventListener('click', () => {
            inputExcel.click(); // FIX utama
        });

        dropArea.addEventListener('dragover', e => {
            e.preventDefault();
            dropArea.classList.add('bg-light');
        });

        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('bg-light');
        });

        dropArea.addEventListener('drop', e => {
            e.preventDefault();
            dropArea.classList.remove('bg-light');
            inputExcel.files = e.dataTransfer.files;
            handleFile(e.dataTransfer.files[0]);
        });
    }

    if (inputExcel) {
        inputExcel.addEventListener('change', e => {
            handleFile(e.target.files[0]);
        });
    }

    function handleFile(file) {
        if (!file) return;

        const reader = new FileReader();

        reader.onload = function (e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const json = XLSX.utils.sheet_to_json(sheet, { defval: "" });

            renderPreview(json);
        };

        reader.readAsArrayBuffer(file);
    }

    function renderPreview(data) {
        const thead = tablePreview.querySelector('thead');
        const tbody = tablePreview.querySelector('tbody');

        thead.innerHTML = '';
        tbody.innerHTML = '';

        if (data.length === 0) return;

        const headers = Object.keys(data[0]);

        let trHead = document.createElement('tr');
        headers.forEach(h => {
            let th = document.createElement('th');
            th.innerText = h;
            trHead.appendChild(th);
        });
        thead.appendChild(trHead);

        let hasError = false;

        data.forEach(row => {
            let tr = document.createElement('tr');
            let rowError = false;

            headers.forEach(h => {
                let td = document.createElement('td');
                td.innerText = row[h];

                if (REQUIRED_HEADERS.includes(h) && !row[h]) {
                    td.classList.add('bg-danger', 'text-white');
                    rowError = true;
                }

                tr.appendChild(td);
            });

            if (rowError) {
                tr.classList.add('table-danger');
                hasError = true;
            }

            tbody.appendChild(tr);
        });

        previewArea.style.display = 'block';
        btnSubmit.disabled = hasError;
    }

    document.getElementById('formImport').addEventListener('submit', function () {
        progressContainer.style.display = 'block';

        let progress = 0;
        const interval = setInterval(() => {
            progress += 10;
            progressBar.style.width = progress + '%';
            progressBar.innerText = progress + '%';

            if (progress >= 100) clearInterval(interval);
        }, 200);
    });

});
</script>
@endpush