@extends('layouts.admin')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Laporan Penjualan</h3>
                <p class="text-subtitle text-muted">Pantau performa penjualan, pajak, dan data transaksi pelanggan.</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    {{-- ================= SUMMARY CARDS ================= --}}
    <section class="row">
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon green mb-2"><i class="bi bi-cash-stack"></i></div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Total Pendapatan</h6>
                            <h6 class="font-extrabold mb-0">Rp {{ number_format($total_revenue ?? 0, 0, ',', '.') }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon blue mb-2"><i class="bi bi-receipt"></i></div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Total Transaksi</h6>
                            <h6 class="font-extrabold mb-0">{{ $transactions->count() }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon red mb-2"><i class="bi bi-percent"></i></div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Diskon Diberikan</h6>
                            <h6 class="font-extrabold mb-0">Rp {{ number_format($total_discount ?? 0, 0, ',', '.') }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon purple mb-2"><i class="bi bi-wallet2"></i></div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Total Pajak</h6>
                            <h6 class="font-extrabold mb-0">Rp {{ number_format($total_tax ?? 0, 0, ',', '.') }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FILTER AREA ================= --}}
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.reports.transactions') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Pencarian</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="jsSearchInput" class="form-control" placeholder="No. Invoice / Pelanggan..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold">
                            <i class="bi bi-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('admin.reports.transactions') }}" class="btn btn-light-secondary w-100 shadow-sm fw-bold">
                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ================= TABLE AREA ================= --}}
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 py-3">
                <h5 class="mb-0">Histori Transaksi</h5>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
                    </a>
                    <a href="#" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="reportTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Invoice & Tanggal</th>
                                <th>Informasi Pelanggan</th>
                                <th>Metode Bayar</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">Total Akhir</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                            <tr class="transaction-row">
                                <td class="text-center small">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-bold text-dark d-block">#{{ $transaction->invoice_number }}</span>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    <span class="fw-bold d-block">{{ $transaction->customer->name ?? 'Walk-in Customer' }}</span>
                                    <span class="badge bg-light-info text-info small" style="font-size: 0.7rem;">{{ strtoupper($transaction->customer->type ?? 'GUEST') }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light-secondary">{{ strtoupper($transaction->payment_method ?? 'CASH') }}</span>
                                </td>
                                <td class="text-end">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-primary">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($transaction->status == 'completed')
                                        <span class="badge bg-success">Selesai</span>
                                    @elseif($transaction->status == 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @else
                                        <span class="badge bg-danger">Batal</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.reports.transactions.detail', $transaction->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    Tidak ada data transaksi ditemukan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('jsSearchInput');
    const tableRows = document.querySelectorAll('.transaction-row');

    /**
     * Fungsi Filter Real-time
     */
    const filterTable = () => {
        const searchTerm = searchInput.value.toLowerCase();

        tableRows.forEach(row => {
            // Kita ambil teks dari kolom Invoice, Nama Pelanggan, dan Metode Bayar
            const invoice = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const customer = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const method = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

            // Cek apakah kata kunci ada di salah satu kolom tersebut
            const matchesSearch = invoice.includes(searchTerm) || 
                                  customer.includes(searchTerm) || 
                                  method.includes(searchTerm);

            // Tampilkan atau sembunyikan baris
            row.style.display = matchesSearch ? "" : "none";
        });

        // Opsional: Tampilkan pesan "Data tidak ditemukan" jika semua row hidden
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== "none");
        updateEmptyMessage(visibleRows.length === 0);
    };

    /**
     * Update tampilan jika hasil pencarian kosong
     */
    const updateEmptyMessage = (isEmpty) => {
        let emptyMsgRow = document.getElementById('jsEmptyMessage');
        if (isEmpty) {
            if (!emptyMsgRow) {
                const tbody = document.querySelector('#reportTable tbody');
                const newRow = document.createElement('tr');
                newRow.id = 'jsEmptyMessage';
                newRow.innerHTML = `<td colspan="8" class="text-center py-4 text-muted">Data tidak ditemukan untuk pencarian tersebut.</td>`;
                tbody.appendChild(newRow);
            }
        } else {
            if (emptyMsgRow) emptyMsgRow.remove();
        }
    };

    // Listener saat user mengetik
    searchInput?.addEventListener('input', filterTable);

    // Fungsi Reset (untuk tombol reset)
    const resetBtn = document.querySelector('.btn-light-secondary');
    resetBtn?.addEventListener('click', (e) => {
        // Jika Anda ingin reset tanpa reload halaman untuk pencarian JS saja:
         e.preventDefault();
         searchInput.value = "";
        filterTable();
    });
});
</script>
@endpush