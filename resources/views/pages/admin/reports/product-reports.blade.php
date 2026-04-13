@extends('layouts.admin')

@section('title', 'Laporan Produk Terlaris')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Laporan Produk Terlaris</h3>
                <p class="text-subtitle text-muted">Analisis produk yang paling diminati oleh pelanggan berdasarkan jumlah penjualan.</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    {{-- ================= SUMMARY CARDS ================= --}}
    <section class="row">
        <div class="col-6 col-lg-4 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon green mb-2"><i class="bi bi-cart-check"></i></div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Total Item Terjual</h6>
                            <h6 class="font-extrabold mb-0">{{ number_format($report->sum('total_sold')) }} unit</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon blue mb-2"><i class="bi bi-bag-plus"></i></div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Produk Terpopuler</h6>
                            <h6 class="font-extrabold mb-0 text-truncate" style="max-width: 150px;">{{ $report->first()->name ?? '-' }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon purple mb-2"><i class="bi bi-graph-up-arrow"></i></div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Estimasi Omzet</h6>
                            <h6 class="font-extrabold mb-0">Rp {{ number_format($report->sum('total_revenue'), 0, ',', '.') }}</h6>
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
                <form action="{{ route('admin.product.reports') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Cari Nama Produk / SKU</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="jsSearchInput" class="form-control" placeholder="Ketik nama produk..." value="{{ request('search') }}">
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
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold">
                            <i class="bi bi-filter"></i>
                        </button>
                        <a href="{{ route('admin.product.reports') }}" class="btn btn-light-secondary w-100 shadow-sm fw-bold">
                            <i class="bi bi-arrow-clockwise"></i>
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
                <h5 class="mb-0">Peringkat Produk Terlaris</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
                    </button>
                    <button class="btn btn-danger btn-sm">
                        <i class="bi bi-printer me-1"></i> Cetak
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="reportTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">Rank</th>
                                <th>Informasi Produk</th>
                                <th>Varian</th>
                                <th class="text-center">Total Terjual</th>
                                <th class="text-end">Total Omzet</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report as $item)
                            <tr class="product-row">
                                <td class="text-center">
                                    @if($loop->iteration <= 3)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-trophy-fill"></i> {{ $loop->iteration }}</span>
                                    @else
                                        <span class="text-muted">{{ $loop->iteration }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">{{ $item->name }}</span>
                                    <small class="text-muted">SKU: {{ $item->sku }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light-secondary">{{ $item->variant ?? 'Default' }}</span>
                                </td>
                                <td class="text-center fw-bold text-primary">
                                    {{ number_format($item->total_sold) }}
                                </td>
                                <td class="text-end fw-bold">
                                    Rp {{ number_format($item->total_revenue, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="#" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                        <i class="bi bi-eye"></i> Tren
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
                                    Data produk tidak ditemukan untuk periode ini.
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
    const tableRows = document.querySelectorAll('.product-row');

    searchInput?.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        tableRows.forEach(row => {
            const productName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            row.style.display = productName.includes(searchTerm) ? "" : "none";
        });
    });
});
</script>
@endpush