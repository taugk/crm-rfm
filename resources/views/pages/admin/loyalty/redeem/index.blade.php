@extends('layouts.admin')

@section('title', 'Riwayat Penukaran Poin')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Riwayat Penukaran Poin</h3>
                <p class="text-subtitle text-muted">Pantau dan kelola klaim hadiah oleh pelanggan secara real-time.</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <section class="section">
        <div class="card shadow-sm border-0">
            {{-- HEADER DENGAN FILTER & SEARCH --}}
            <div class="card-header bg-white border-bottom-0 py-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <h5 class="mb-0">Daftar Klaim Hadiah</h5>
                        <p class="text-muted small mb-0">Total: <span id="rowCount">{{ $redemptions->count() }}</span> data ditemukan</p>
                    </div>
                    {{-- Search Bar --}}
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="realTimeSearch" class="form-control bg-light border-0" 
                                placeholder="Cari Kode, Nama Pelanggan, atau Nama Hadiah...">
                        </div>
                    </div>
                    {{-- Status Filter --}}
                    <div class="col-md-3">
                        <select id="statusFilter" class="form-select border-0 bg-light">
                            <option value="all">Semua Status</option>
                            <option value="pending">🟡 PENDING</option>
                            <option value="process">🔵 PROSES</option>
                            <option value="completed">🟢 SELESAI</option>
                            <option value="cancelled">🔴 DIBATALKAN</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="redemptionTable">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Pelanggan</th>
                                <th>Hadiah</th>
                                <th>Poin Digunakan</th>
                                <th>Tanggal</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($redemptions as $item)
                            <tr class="redemption-row">
                                <td class="fw-bold text-primary">{{ $item->redemption_code }}</td>
                                <td>
                                    <span class="d-block fw-bold text-dark">{{ $item->customer->name }}</span>
                                    <small class="text-muted">{{ $item->customer->phone ?? 'Tidak ada telepon' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light-info text-dark border">{{ $item->reward->name }}</span>
                                </td>
                                <td class="text-danger fw-bold">
                                    -{{ number_format($item->points_used) }} Pts
                                </td>
                                <td>
                                    <small class="d-block text-dark">{{ $item->created_at->format('d M Y') }}</small>
                                    <small class="text-muted">{{ $item->created_at->format('H:i') }} WIB</small>
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusClass = [
                                            'pending' => 'bg-warning',
                                            'process' => 'bg-info',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger'
                                        ][$item->status] ?? 'bg-secondary';
                                    @endphp
                                    <span class="badge {{ $statusClass }} text-uppercase">{{ $item->status }}</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" 
                                            data-bs-toggle="modal" data-bs-target="#modalDetail{{ $item->id }}">
                                        Update Status
                                    </button>
                                </td>
                            </tr>

                            {{-- MODAL UPDATE STATUS --}}
                            <div class="modal fade" id="modalDetail{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form action="{{ route('admin.loyalty.redemptions.update', $item->id) }}" 
                                              method="POST" id="formStatus{{ $item->id }}">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Kelola Penukaran #{{ $item->redemption_code }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="p-3 bg-light rounded mb-3">
                                                    <div class="row">
                                                        <div class="col-6 small text-muted">Pelanggan:</div>
                                                        <div class="col-6 small fw-bold text-end">{{ $item->customer->name }}</div>
                                                        <div class="col-6 small text-muted">Hadiah:</div>
                                                        <div class="col-6 small fw-bold text-end">{{ $item->reward->name }}</div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Pilih Status Baru</label>
                                                    <select name="status" class="form-select border-primary">
                                                        <option value="pending" {{ $item->status == 'pending' ? 'selected' : '' }}>PENDING</option>
                                                        <option value="process" {{ $item->status == 'process' ? 'selected' : '' }}>DIPROSES</option>
                                                        <option value="completed" {{ $item->status == 'completed' ? 'selected' : '' }}>SELESAI / DIAMBIL</option>
                                                        <option value="cancelled" {{ $item->status == 'cancelled' ? 'selected' : '' }}>DIBATALKAN</option>
                                                    </select>
                                                </div>

                                                <div class="mb-0">
                                                    <label class="form-label fw-bold">Catatan Admin</label>
                                                    <textarea name="admin_notes" class="form-control" rows="3" 
                                                        placeholder="Masukkan nomor resi atau info pengambilan">{{ $item->admin_notes }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-primary">Update Data</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr id="initialEmptyRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                                    Belum ada riwayat penukaran poin.
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
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('realTimeSearch');
    const statusFilter = document.getElementById('statusFilter');
    const tableRows = document.querySelectorAll('.redemption-row');
    const rowCountText = document.getElementById('rowCount');

    /**
     * LOGIKA FILTER & SEARCH REAL-TIME
     */
    function performFilter() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterStatus = statusFilter.value.toLowerCase();
        let visibleCount = 0;

        tableRows.forEach(row => {
            const kode = row.cells[0].textContent.toLowerCase();
            const pelanggan = row.cells[1].textContent.toLowerCase();
            const hadiah = row.cells[2].textContent.toLowerCase();
            const status = row.querySelector('.badge:last-child').textContent.toLowerCase();

            const matchesSearch = kode.includes(searchTerm) || pelanggan.includes(searchTerm) || hadiah.includes(searchTerm);
            const matchesStatus = (filterStatus === 'all' || status === filterStatus);

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                row.classList.add('fade-in');
                visibleCount++;
            } else {
                row.style.display = 'none';
                row.classList.remove('fade-in');
            }
        });

        rowCountText.innerText = visibleCount;
        handleEmptyState(visibleCount);
    }

    function handleEmptyState(count) {
        let emptyMsg = document.getElementById('noDataFound');
        if (count === 0) {
            if (!emptyMsg) {
                const tr = document.createElement('tr');
                tr.id = 'noDataFound';
                tr.innerHTML = `<td colspan="7" class="text-center py-5 text-muted">Data tidak ditemukan.</td>`;
                document.querySelector('#redemptionTable tbody').appendChild(tr);
            }
        } else if (emptyMsg) {
            emptyMsg.remove();
        }
    }

    // Event Listeners
    searchInput.addEventListener('input', performFilter);
    statusFilter.addEventListener('change', performFilter);

    /**
     * SWEETALERT NOTIFIKASI
     */
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            timer: 2000,
            showConfirmButton: false
        });
    @endif

    // Konfirmasi pembatalan status via modal
    const forms = document.querySelectorAll('form[id^="formStatus"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const statusSelect = this.querySelector('select[name="status"]').value;
            if (statusSelect === 'cancelled') {
                e.preventDefault();
                Swal.fire({
                    title: 'Batalkan Klaim?',
                    text: "Poin pelanggan mungkin perlu dikembalikan secara manual.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Batalkan'
                }).then((result) => {
                    if (result.isConfirmed) this.submit();
                });
            }
        });
    });
});
</script>

<style>
    .fade-in { animation: fadeIn 0.4s ease-in; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .redemption-row:hover { background-color: #f8f9fa; }
    .input-group:focus-within { box-shadow: 0 0 0 0.25rem rgba(67, 94, 190, 0.15); border-radius: 0.25rem; }
</style>
@endpush