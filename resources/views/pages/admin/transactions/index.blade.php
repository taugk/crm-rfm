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
<div class="modal fade" id="modalImport" tabindex="-1" aria-labelledby="modalImportLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('admin.transactions.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalImportLabel">Import Transaksi & Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle me-2 fs-5"></i>
                            <div>
                                <strong>Panduan:</strong> Gunakan nomor invoice yang sama untuk transaksi dengan banyak produk.
                                <br>
                                <a href="javascript:void(0)" id="downloadTemplate" class="fw-bold text-decoration-underline text-primary">
                                    <i class="bi bi-download me-1"></i>Download Template Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih File (.xlsx / .xls)</label>
                        <input type="file" name="file" id="inputExcel" class="form-control" accept=".xlsx, .xls" required>
                    </div>

                    {{-- Area Preview --}}
                    <div id="previewArea" style="display: none;">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-primary">Preview Data (10 Baris Pertama)</h6>
                            <span class="badge bg-light-primary text-primary" id="rowCountLabelText"></span>
                        </div>
                        <div class="table-responsive border rounded" style="max-height: 400px;">
                            <table class="table table-sm table-striped mb-0 text-nowrap" id="tablePreview">
                                <thead class="table-light sticky-top"></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-secondary py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="btnSubmitImport" disabled>
                        <i class="bi bi-cloud-arrow-up me-1"></i> Mulai Import
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
document.addEventListener('DOMContentLoaded', function() {
    // --- SELECTOR ---
    const searchInput = document.getElementById('trxSearchInput');
    const statusFilter = document.getElementById('trxStatusFilter');
    const startDateInput = document.getElementById('trxStartDate');
    const endDateInput = document.getElementById('trxEndDate');
    const resetBtn = document.getElementById('trxResetBtn');
    const tableRows = document.querySelectorAll('.trx-row');
    
    const inputExcel = document.getElementById('inputExcel');
    const previewArea = document.getElementById('previewArea');
    const tablePreview = document.getElementById('tablePreview');
    const btnSubmitImport = document.getElementById('btnSubmitImport');
    const rowCountLabel = document.getElementById('rowCountLabelText');
    const btnDownloadTemplate = document.getElementById('downloadTemplate');

    // --- CONFIG KOLOM ---
    const TEMPLATE_HEADERS = [
        "Invoice Number", "Date (YYYY-MM-DD)", "Customer Name", 
        "Payment Method", "Status", "Product Name", 
        "Quantity", "Price per Item", "Sub Total", "Notes"
    ];

    // --- 1. FILTER LOGIC ---
    function filterTransactions() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value.toLowerCase();
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        tableRows.forEach(row => {
            const invoice = row.querySelector('.trx-invoice').innerText.toLowerCase();
            const customer = row.querySelector('.trx-customer').innerText.toLowerCase();
            const statusBadge = row.querySelector('.trx-status-badge');
            const status = statusBadge ? statusBadge.getAttribute('data-status').toLowerCase() : '';
            const trxDate = row.querySelector('.trx-date').getAttribute('data-date');

            const matchesSearch = invoice.includes(searchTerm) || customer.includes(searchTerm);
            const matchesStatus = selectedStatus === "" || status === selectedStatus;
            
            let matchesDate = true;
            if (startDate && trxDate < startDate) matchesDate = false;
            if (endDate && trxDate > endDate) matchesDate = false;

            row.style.display = (matchesSearch && matchesStatus && matchesDate) ? "" : "none";
        });
    }

    [searchInput, statusFilter, startDateInput, endDateInput].forEach(el => {
        el.addEventListener('input', filterTransactions);
    });

    resetBtn.addEventListener('click', () => {
        searchInput.value = ""; statusFilter.value = ""; 
        startDateInput.value = ""; endDateInput.value = "";
        filterTransactions();
    });

    // --- 2. TEMPLATE GENERATOR ---
    btnDownloadTemplate.addEventListener('click', () => {
        const data = [
            TEMPLATE_HEADERS,
            ["INV-001", "2024-03-25", "John Doe", "Cash", "completed", "Kopi Susu", "2", "15000", "30000", "Less sugar"],
            ["INV-001", "2024-03-25", "John Doe", "Cash", "completed", "Roti Bakar", "1", "20000", "20000", "Extra cheese"]
        ];
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Template");
        XLSX.writeFile(wb, "Template_Import_Transaksi.xlsx");
    });

    // --- 3. PREVIEW & AUTO-CALCULATE SUB TOTAL ---
    inputExcel.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const worksheet = workbook.Sheets[workbook.SheetNames[0]];
            const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

            if (json.length > 1) {
                renderPreview(json.slice(0, 11)); 
                rowCountLabel.textContent = (json.length - 1) + " Baris terdeteksi";
                previewArea.style.display = 'block';
                btnSubmitImport.disabled = false;
            }
        };
        reader.readAsArrayBuffer(file);
    });

    function renderPreview(rows) {
        const thead = tablePreview.querySelector('thead');
        const tbody = tablePreview.querySelector('tbody');
        thead.innerHTML = ''; tbody.innerHTML = '';

        rows.forEach((row, index) => {
            const tr = document.createElement('tr');
            
            // Hitung Subtotal otomatis untuk preview (Qty * Price)
            const qty = parseFloat(row[6]) || 0;
            const price = parseFloat(row[7]) || 0;
            const subtotal = qty * price;

            TEMPLATE_HEADERS.forEach((header, colIdx) => {
                const el = document.createElement(index === 0 ? 'th' : 'td');
                
                if (index > 0 && header === "Sub Total") {
                    // Gunakan nilai dari Excel jika ada, jika tidak hitung sendiri
                    const val = row[colIdx] ? row[colIdx] : subtotal;
                    el.innerText = Number(val).toLocaleString('id-ID');
                    el.classList.add('text-end', 'fw-bold');
                } else {
                    el.innerText = row[colIdx] || '';
                    if (index > 0 && (header === "Quantity" || header === "Price per Item")) {
                        el.classList.add('text-end');
                    }
                }
                tr.appendChild(el);
            });

            if (index === 0) thead.appendChild(tr);
            else tbody.appendChild(tr);
        });
    }
});
</script>
@endpush