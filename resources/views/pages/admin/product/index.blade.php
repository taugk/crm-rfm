@extends('layouts.admin')

@section('title', 'Data Produk')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-md-6">
                <h3 class="fw-bold">Master Data Produk</h3>
                <p class="text-muted">Analisis dan kelola stok serta harga produk Anda.</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content">

    {{-- ================= STATISTIK ================= --}}
    <section class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body p-4">
                    <div class="stats-icon blue mb-3 mx-auto">
                        <i class="bi bi-box-seam text-white"></i>
                    </div>
                    <h6 class="text-muted">Total Produk</h6>
                    <h3 class="fw-bold mb-0">{{ $data->total() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body p-4">
                    <div class="stats-icon red mb-3 mx-auto">
                        <i class="bi bi-exclamation-triangle text-white"></i>
                    </div>
                    <h6 class="text-muted">Stok Menipis</h6>
                    <h3 class="fw-bold mb-0 text-danger">{{ $lowStockCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body p-4">
                    <div class="stats-icon green mb-3 mx-auto">
                        <i class="bi bi-tags text-white"></i>
                    </div>
                    <h6 class="text-muted">Total Kategori</h6>
                    <h3 class="fw-bold mb-0">{{ $categories->count() }}</h3>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FILTER & SEARCH ================= --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" id="jsSearchInput" class="form-control" placeholder="Cari SKU atau Nama Produk...">
                </div>
                <div class="col-md-3">
                    <select id="jsCategoryFilter" class="form-select">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ strtolower($cat->name) }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="jsSort" class="form-select">
                        <option value="">Urutkan Berdasarkan</option>
                        <option value="stok_asc">Stok Terendah</option>
                        <option value="stok_desc">Stok Tertinggi</option>
                        <option value="harga_asc">Harga Termurah</option>
                        <option value="harga_desc">Harga Termahal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="jsResetBtn" class="btn btn-secondary w-100">Reset</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= TABLE AREA ================= --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">Daftar Produk</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.products.export') }}" id="btnExport" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </a>

                <button type="button" class="btn btn-outline-success btn-sm" onclick="document.getElementById('importExcelInput').click()">
                    <i class="bi bi-upload"></i> Import
                </button>
                
                <form action="{{ route('admin.products.import.process') }}" method="POST" enctype="multipart/form-data" id="finalImportForm" class="d-none">
                    @csrf
                    <input type="file" name="file_excel" id="importExcelInput" accept=".xlsx, .xls">
                </form>

                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">+ Tambah Baru</a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="productTable">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center no-export">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        @forelse($data as $p)
                        @php $detail = $p->details ?? (object)['stock' => 0, 'variant' => '-']; @endphp
                        <tr class="product-row" data-stok="{{ $detail->stock }}" data-harga="{{ $p->price }}">
                            <td><code class="text-primary fw-bold">{{ $p->sku }}</code></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ $p->image ? asset('storage/' . $p->image) : 'https://via.placeholder.com/45' }}"
                                        class="rounded border me-3 product-img" 
                                        width="45" 
                                        height="45" 
                                        style="object-fit: cover;"
                                        data-fallback="https://via.placeholder.com/45"
                                        onerror="this.src=this.getAttribute('data-fallback')">
                                    
                                    <div>
                                        <div class="fw-bold product-name">{{ $p->name }}</div>
                                        
                                        @if($detail->variant)
                                            <small class="text-muted d-block">Varian: {{ $detail->variant }}</small>
                                        @endif
                                        
                                        <small class="text-secondary small" style="font-size: 0.7rem;">SKU: {{ $p->sku }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="product-category">
                                <span class="badge bg-light text-dark border">{{ $p->category->name ?? 'N/A' }}</span>
                            </td>
                            <td class="fw-bold">Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $detail->stock <= 5 ? 'bg-danger' : 'bg-success' }} rounded-pill">
                                    {{ $detail->stock <= 5 ? 'Menipis: ' : '' }}{{ $detail->stock }}
                                </span>
                            </td>
                            <td class="text-center no-export">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('admin.products.show', $p->id) }}" class="btn btn-sm btn-info text-white">Detail</a>
                                    <a href="{{ route('admin.products.edit', $p->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('admin.products.destroy', $p->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger delete-confirm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data produk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $data->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW IMPORT --}}
<div class="modal fade" id="previewModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Konfirmasi Data Import</h5>
                {{-- Tombol X juga diarahkan kembali ke index --}}
                <a href="{{ route('admin.products') }}" class="btn-close btn-close-white"></a>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i> Menampilkan maksimal 10 baris pertama sebagai preview.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead id="previewHead" class="table-light"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                {{-- Tombol Batal memicu reload ke route admin.products --}}
                <a href="{{ route('admin.products') }}" class="btn btn-secondary">Batal</a>
                
                <button type="button" class="btn btn-success" onclick="document.getElementById('finalImportForm').submit()">
                    <i class="bi bi-check-circle me-1"></i> Upload Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.stats-icon { width: 3rem; height: 3rem; border-radius: .5rem; display: flex; align-items: center; justify-content: center; }
.stats-icon.blue { background:#435ebe; } .stats-icon.red { background:#dc3545; } .stats-icon.green { background:#198754; }
</style>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('jsSearchInput');
    const category = document.getElementById('jsCategoryFilter');
    const btnExport = document.getElementById('btnExport');
    const tbody = document.getElementById('productTableBody');
    const importInput = document.getElementById('importExcelInput');
    const previewModalEl = document.getElementById('previewModal');
    const previewModal = new bootstrap.Modal(previewModalEl);

    /** 1. RESET LOGIC (PENTING UNTUK BATAL) **/
    window.resetImport = function() {
        importInput.value = ""; // Clear file path
        document.getElementById('previewHead').innerHTML = "";
        document.getElementById('previewBody').innerHTML = "";
    };

    // Trigger reset jika modal ditutup (klik luar, tombol X, atau tombol Batal)
    previewModalEl.addEventListener('hidden.bs.modal', resetImport);

    /** 2. LIVE FILTER & EXPORT URL **/
    const updateView = () => {
        const keyword = search.value.toLowerCase();
        const catValue = category.value.toLowerCase();
        
        // Update Link Export
        const url = new URL("{{ route('admin.products.export') }}");
        url.searchParams.set('search', search.value);
        url.searchParams.set('category', category.value);
        btnExport.href = url.href;

        document.querySelectorAll('.product-row').forEach(row => {
            const name = row.querySelector('.product-name').textContent.toLowerCase();
            const sku = row.querySelector('code').textContent.toLowerCase();
            const kategori = row.querySelector('.product-category').textContent.trim().toLowerCase();
            row.style.display = (name.includes(keyword) || sku.includes(keyword)) && (!catValue || kategori === catValue) ? '' : 'none';
        });
    };

    /** 3. IMPORT PREVIEW **/
    importInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validasi Ekstensi
        const ext = file.name.split('.').pop().toLowerCase();
        if(!['xlsx', 'xls'].includes(ext)) {
            alert("Format file harus Excel!");
            resetImport();
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const workbook = XLSX.read(new Uint8Array(e.target.result), {type: 'array'});
                const data = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]], {header: 1});
                
                if (data.length > 0) {
                    let headHtml = "<tr>"; data[0].forEach(h => headHtml += `<th>${h || ''}</th>`); headHtml += "</tr>";
                    document.getElementById('previewHead').innerHTML = headHtml;

                    let bodyHtml = "";
                    for (let i = 1; i < Math.min(data.length, 11); i++) {
                        bodyHtml += "<tr>"; data[i].forEach(c => bodyHtml += `<td>${c || '-'}</td>`); bodyHtml += "</tr>";
                    }
                    document.getElementById('previewBody').innerHTML = bodyHtml;
                    previewModal.show();
                }
            } catch (err) {
                alert("Gagal membaca file Excel!");
                resetImport();
            }
        };
        reader.readAsArrayBuffer(file);
    });

    /** 4. IMAGE TIMEOUT **/
    document.querySelectorAll('.product-img').forEach(img => {
        if (!img.complete) {
            img.timer = setTimeout(() => { if(!img.complete) img.src = img.getAttribute('data-fallback'); }, 5000);
        }
    });

    search.addEventListener('input', updateView);
    category.addEventListener('change', updateView);
    document.getElementById('jsResetBtn').addEventListener('click', () => { 
        search.value = ''; category.value = ''; updateView(); 
    });

    updateView(); // Initial sync
});
</script>
@endpush