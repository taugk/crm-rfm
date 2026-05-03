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
                    <h3 class="fw-bold mb-0" id="totalProdukCount">{{ $data->total() }}</h3>
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
                        @php 
                            $detail = $p->details ?? (object)['stock' => 0, 'variant' => '-']; 
                            $harga = $p->price;
                            $stok = $detail->stock;
                            $hasImage = $p->image && file_exists(public_path('storage/' . $p->image));
                        @endphp
                        <tr class="product-row" data-stok="{{ $stok }}" data-harga="{{ $harga }}">
                            <td><code class="text-primary fw-bold">{{ $p->sku }}</code></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($hasImage)
                                        <img src="{{ asset('storage/' . $p->image) }}"
                                             class="rounded border me-3 product-img" 
                                             width="45" 
                                             height="45" 
                                             style="object-fit: cover;"
                                             data-fallback="https://via.placeholder.com/45?text=No+Image"
                                             loading="lazy">
                                    @else
                                        <img src="https://via.placeholder.com/45?text=No+Image"
                                             class="rounded border me-3 product-img" 
                                             width="45" 
                                             height="45" 
                                             style="object-fit: cover;">
                                    @endif
                                    
                                    <div>
                                        <div class="fw-bold product-name">{{ $p->name }}</div>
                                        
                                        @if($detail->variant && $detail->variant != '-')
                                            <small class="text-muted d-block">Varian: {{ $detail->variant }}</small>
                                        @endif
                                        
                                        <small class="text-secondary small" style="font-size: 0.7rem;">SKU: {{ $p->sku }}</small>
                                    </div>
                                </div>
                             </td>
                            <td class="product-category">
                                <span class="badge bg-light text-dark border">{{ $p->category->name ?? 'N/A' }}</span>
                             </td>
                            <td class="fw-bold product-price">Rp {{ number_format($harga, 0, ',', '.') }}</td>
                            <td class="text-center product-stock">
                                <span class="badge {{ $stok <= 5 ? 'bg-danger' : 'bg-success' }} rounded-pill">
                                    {{ $stok <= 5 ? 'Menipis: ' : '' }}{{ $stok }}
                                </span>
                             </td>
                            <td class="text-center no-export">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('admin.products.show', $p->id) }}" class="btn btn-sm btn-info text-white">Detail</a>
                                    <a href="{{ route('admin.products.edit', $p->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('admin.products.destroy', $p->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger delete-confirm">Hapus</button>
                                    </form>
                                </div>
                             </td>
                        </tr>
                        @empty
                        <tr class="empty-row">
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada data produk.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4" id="paginationLinks">
                {{ $data->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW IMPORT --}}
<div class="modal fade" id="previewModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Konfirmasi Data Import</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" onclick="submitImport()">
                    <i class="bi bi-check-circle me-1"></i> Upload Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.stats-icon { 
    width: 3rem; 
    height: 3rem; 
    border-radius: .5rem; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
}
.stats-icon.blue { background:#435ebe; } 
.stats-icon.red { background:#dc3545; } 
.stats-icon.green { background:#198754; }
.product-img {
    background-color: #f8f9fa;
}
.table-responsive {
    overflow-x: auto;
}
</style>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // ==================== DOM ELEMENTS ====================
    const search = document.getElementById('jsSearchInput');
    const category = document.getElementById('jsCategoryFilter');
    const sortSelect = document.getElementById('jsSort');
    const btnExport = document.getElementById('btnExport');
    const importInput = document.getElementById('importExcelInput');
    const previewModalEl = document.getElementById('previewModal');
    const resetBtn = document.getElementById('jsResetBtn');
    let previewModal = null;
    
    // Inisialisasi modal jika ada
    if (previewModalEl) {
        previewModal = new bootstrap.Modal(previewModalEl);
    }

    // ==================== FIX 1: GAMBAR TIDAK ADA ====================
    function fixImages() {
        document.querySelectorAll('.product-img').forEach(img => {
            const placeholder = img.getAttribute('data-fallback') || 'https://via.placeholder.com/45?text=No+Image';
            
            // Cek apakah src valid
            const currentSrc = img.src;
            if (!currentSrc || currentSrc === '' || currentSrc.includes('placeholder') || currentSrc === window.location.href) {
                img.src = placeholder;
                return;
            }
            
            // Handle error loading gambar
            img.onerror = function() {
                if (this.src !== placeholder) {
                    this.src = placeholder;
                    this.onerror = null; // Prevent infinite loop
                }
            };
        });
    }
    
    // Jalankan fix gambar
    fixImages();

    // ==================== FUNGSI GET DATA ====================
    function getAllRows() {
        return Array.from(document.querySelectorAll('#productTableBody .product-row'));
    }

    // ==================== FILTER (CASE-INSENSITIVE) ====================
    function filterRows(rows) {
        const keyword = search.value.trim().toLowerCase();
        const catValue = category.value.toLowerCase();

        if (rows.length === 0) return rows;

        return rows.filter(row => {
            const nameEl = row.querySelector('.product-name');
            const skuEl = row.querySelector('code');
            const categoryEl = row.querySelector('.product-category');
            
            if (!nameEl || !skuEl) return true;
            
            const name = nameEl.textContent.toLowerCase();
            const sku = skuEl.textContent.toLowerCase();
            const kategori = categoryEl ? categoryEl.textContent.trim().toLowerCase() : '';
            
            const matchSearch = keyword === '' || name.includes(keyword) || sku.includes(keyword);
            const matchCategory = catValue === '' || kategori === catValue;
            
            return matchSearch && matchCategory;
        });
    }

    // ==================== SORTING (FIX HARGA) ====================
    function sortRows(rows, sortBy) {
        const sortedRows = [...rows];
        
        if (sortBy === '') return sortedRows;
        
        switch(sortBy) {
            case 'stok_asc':
                sortedRows.sort((a, b) => {
                    let stokA = parseInt(a.dataset.stok);
                    let stokB = parseInt(b.dataset.stok);
                    
                    if (isNaN(stokA)) {
                        const stokCell = a.querySelector('.product-stok, td:nth-child(5)');
                        if (stokCell) {
                            const stokText = stokCell.textContent.replace(/[^0-9]/g, '');
                            stokA = parseInt(stokText) || 0;
                        }
                    }
                    if (isNaN(stokB)) {
                        const stokCell = b.querySelector('.product-stok, td:nth-child(5)');
                        if (stokCell) {
                            const stokText = stokCell.textContent.replace(/[^0-9]/g, '');
                            stokB = parseInt(stokText) || 0;
                        }
                    }
                    return stokA - stokB;
                });
                break;
            case 'stok_desc':
                sortedRows.sort((a, b) => {
                    let stokA = parseInt(a.dataset.stok);
                    let stokB = parseInt(b.dataset.stok);
                    
                    if (isNaN(stokA)) {
                        const stokCell = a.querySelector('.product-stok, td:nth-child(5)');
                        if (stokCell) {
                            const stokText = stokCell.textContent.replace(/[^0-9]/g, '');
                            stokA = parseInt(stokText) || 0;
                        }
                    }
                    if (isNaN(stokB)) {
                        const stokCell = b.querySelector('.product-stok, td:nth-child(5)');
                        if (stokCell) {
                            const stokText = stokCell.textContent.replace(/[^0-9]/g, '');
                            stokB = parseInt(stokText) || 0;
                        }
                    }
                    return stokB - stokA;
                });
                break;
            case 'harga_asc':
                sortedRows.sort((a, b) => {
                    let hargaA = parseInt(a.dataset.harga);
                    let hargaB = parseInt(b.dataset.harga);
                    
                    if (isNaN(hargaA)) {
                        const hargaCell = a.querySelector('.product-price, td:nth-child(4)');
                        if (hargaCell) {
                            const hargaText = hargaCell.textContent.replace(/[^0-9]/g, '');
                            hargaA = parseInt(hargaText) || 0;
                        }
                    }
                    if (isNaN(hargaB)) {
                        const hargaCell = b.querySelector('.product-price, td:nth-child(4)');
                        if (hargaCell) {
                            const hargaText = hargaCell.textContent.replace(/[^0-9]/g, '');
                            hargaB = parseInt(hargaText) || 0;
                        }
                    }
                    return hargaA - hargaB;
                });
                break;
            case 'harga_desc':
                sortedRows.sort((a, b) => {
                    let hargaA = parseInt(a.dataset.harga);
                    let hargaB = parseInt(b.dataset.harga);
                    
                    if (isNaN(hargaA)) {
                        const hargaCell = a.querySelector('.product-price, td:nth-child(4)');
                        if (hargaCell) {
                            const hargaText = hargaCell.textContent.replace(/[^0-9]/g, '');
                            hargaA = parseInt(hargaText) || 0;
                        }
                    }
                    if (isNaN(hargaB)) {
                        const hargaCell = b.querySelector('.product-price, td:nth-child(4)');
                        if (hargaCell) {
                            const hargaText = hargaCell.textContent.replace(/[^0-9]/g, '');
                            hargaB = parseInt(hargaText) || 0;
                        }
                    }
                    return hargaB - hargaA;
                });
                break;
            default:
                break;
        }
        
        return sortedRows;
    }

    // ==================== RENDER UTAMA ====================
    function renderTable() {
        const allRows = getAllRows();
        
        if (allRows.length === 0) return;
        
        let filteredRows = filterRows(allRows);
        const sortValue = sortSelect.value;
        
        if (sortValue) {
            filteredRows = sortRows(filteredRows, sortValue);
        }
        
        // Sembunyikan semua baris
        allRows.forEach(row => row.style.display = 'none');
        
        // Tampilkan baris yang sudah difilter & disortir
        filteredRows.forEach(row => row.style.display = '');
        
        // Update export link
        if (btnExport) {
            const url = new URL(btnExport.href, window.location.origin);
            url.searchParams.set('search', search.value);
            url.searchParams.set('category', category.value);
            btnExport.href = url.toString();
        }
        
        // Update statistik jumlah produk yang tampil
        updateStatsCount(filteredRows.length, allRows.length);
    }
    
    // Update statistik
    function updateStatsCount(visibleCount, totalCount) {
        const totalProdukElement = document.querySelector('.stats-icon.blue ~ h6 ~ h3, .stats-icon.blue + div + h3');
        if (totalProdukElement) {
            const originalTotal = parseInt(totalProdukElement.getAttribute('data-original')) || {{ $data->total() }};
            if (!totalProdukElement.getAttribute('data-original')) {
                totalProdukElement.setAttribute('data-original', originalTotal);
            }
            if (visibleCount !== totalCount) {
                totalProdukElement.innerHTML = `${visibleCount} / ${originalTotal}`;
                totalProdukElement.style.fontSize = '1.3rem';
            } else {
                totalProdukElement.innerHTML = originalTotal;
                totalProdukElement.style.fontSize = '';
            }
        }
    }

    // ==================== RESET ====================
    function resetFilters() {
        if (search) search.value = '';
        if (category) category.value = '';
        if (sortSelect) sortSelect.value = '';
        renderTable();
    }

    // ==================== EVENT LISTENER ====================
    if (search) search.addEventListener('input', renderTable);
    if (category) category.addEventListener('change', renderTable);
    if (sortSelect) sortSelect.addEventListener('change', renderTable);
    if (resetBtn) resetBtn.addEventListener('click', resetFilters);

    // ==================== DELETE CONFIRMATION ====================
    document.querySelectorAll('.delete-confirm').forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
                e.preventDefault();
            }
        });
    });

    // ==================== IMPORT PREVIEW ====================
    if (importInput) {
        importInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

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
                    
                    if (data && data.length > 0) {
                        // Header
                        let headHtml = "<tr>"; 
                        if (data[0]) {
                            data[0].forEach(h => headHtml += `<th>${h || ''}</th>`);
                        }
                        headHtml += "</tr>";
                        const previewHead = document.getElementById('previewHead');
                        if (previewHead) previewHead.innerHTML = headHtml;

                        // Body (max 10 rows)
                        let bodyHtml = "";
                        for (let i = 1; i < Math.min(data.length, 11); i++) {
                            bodyHtml += "<tr>"; 
                            if (data[i]) {
                                data[i].forEach(c => bodyHtml += `<td>${c !== undefined && c !== null ? c : '-'}</td>`);
                            }
                            bodyHtml += "</tr>";
                        }
                        const previewBody = document.getElementById('previewBody');
                        if (previewBody) previewBody.innerHTML = bodyHtml;
                        
                        if (previewModal) previewModal.show();
                    } else {
                        alert("File Excel kosong!");
                        resetImport();
                    }
                } catch (err) {
                    console.error(err);
                    alert("Gagal membaca file Excel! Pastikan format file benar.");
                    resetImport();
                }
            };
            reader.readAsArrayBuffer(file);
        });
    }

    window.resetImport = function() {
        if (importInput) importInput.value = "";
        const previewHead = document.getElementById('previewHead');
        const previewBody = document.getElementById('previewBody');
        if (previewHead) previewHead.innerHTML = "";
        if (previewBody) previewBody.innerHTML = "";
    };

    if (previewModalEl) {
        previewModalEl.addEventListener('hidden.bs.modal', window.resetImport);
    }

    window.submitImport = function() {
        const finalForm = document.getElementById('finalImportForm');
        if (finalForm) finalForm.submit();
    };

    // ==================== ENSURE DATA ATTRIBUTES ====================
    function ensureDataAttributes() {
        document.querySelectorAll('.product-row').forEach(row => {
            // Set data-harga jika belum ada
            if (!row.dataset.harga || row.dataset.harga === 'NaN' || row.dataset.harga === '0') {
                const hargaCell = row.querySelector('.product-price, td:nth-child(4)');
                if (hargaCell) {
                    const hargaText = hargaCell.textContent.replace(/[^0-9]/g, '');
                    const harga = parseInt(hargaText) || 0;
                    row.dataset.harga = harga;
                } else {
                    row.dataset.harga = 0;
                }
            }
            
            // Set data-stok jika belum ada
            if (!row.dataset.stok || row.dataset.stok === 'NaN') {
                const stokSpan = row.querySelector('.product-stok span, td:nth-child(5) span, .badge');
                if (stokSpan) {
                    const stokText = stokSpan.textContent.replace(/[^0-9]/g, '');
                    const stok = parseInt(stokText) || 0;
                    row.dataset.stok = stok;
                } else {
                    row.dataset.stok = 0;
                }
            }
        });
    }
    
    ensureDataAttributes();
    
    // ==================== MUTATION OBSERVER ====================
    const observer = new MutationObserver(function(mutations) {
        let needsUpdate = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                needsUpdate = true;
            }
        });
        if (needsUpdate) {
            fixImages();
            ensureDataAttributes();
            renderTable();
        }
    });
    
    const tableBody = document.getElementById('productTableBody');
    if (tableBody) {
        observer.observe(tableBody, { childList: true, subtree: true });
    }
    
    // Render awal
    renderTable();
});
</script>
@endpush