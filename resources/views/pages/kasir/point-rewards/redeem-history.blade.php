@extends('layouts.kasir')

@section('title', 'Riwayat Penukaran Poin')

@section('content')
<div class="page-heading d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-dark">📜 Riwayat Penukaran Poin</h3>
        <p class="text-muted mb-0">Menampilkan semua history penukaran poin member</p>
    </div>
    <div>
        <a href="{{ route('kasir.point-rewards.index') }}" class="btn btn-outline-primary rounded-pill">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Penukaran
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <!-- Filter Section -->
        <div class="p-4 border-bottom bg-light rounded-top-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Cari Member</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchMember" class="form-control" placeholder="Nama atau No. Telepon">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Tanggal</label>
                    <input type="date" id="filterDate" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="resetFilter" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-repeat me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2">Memuat data...</p>
        </div>

        <!-- Table Content -->
        <div class="table-responsive" id="tableContent">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3" style="width: 80px;">No</th>
                        <th class="py-3">Kode Penukaran</th>
                        <th class="py-3">Member</th>
                        <th class="py-3">Hadiah</th>
                        <th class="py-3 text-center">Poin</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Tanggal</th>
                        <th class="py-3 text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="redemptionTableBody">
                    @forelse($redemptions as $index => $redemption)
                    <tr>
                        <td class="px-4">{{ $index + 1 }}</td>
                        <td>
                            <span class="fw-bold text-primary">{{ $redemption->redemption_code }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="member-avatar me-2">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 35px; height: 35px; font-size: 14px;">
                                        {{ strtoupper(substr($redemption->customer->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $redemption->customer->name }}</div>
                                    <small class="text-muted">{{ $redemption->customer->phone }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $redemption->reward->name }}</td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-star-fill me-1"></i> {{ number_format($redemption->points_used) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($redemption->status == 'completed')
                                <span class="badge bg-success rounded-pill px-3">
                                    <i class="bi bi-check-circle me-1"></i> Selesai
                                </span>
                            @elseif($redemption->status == 'pending')
                                <span class="badge bg-warning text-dark rounded-pill px-3">
                                    <i class="bi bi-clock me-1"></i> Pending
                                </span>
                            @else
                                <span class="badge bg-danger rounded-pill px-3">
                                    <i class="bi bi-x-circle me-1"></i> Dibatalkan
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <small>{{ $redemption->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-info rounded-circle" 
                                    onclick="showDetail({{ $redemption->id }})" 
                                    title="Detail">
                                <i class="bi bi-eye"></i>
                            </button>
                            @if($redemption->status == 'pending')
                            <button class="btn btn-sm btn-outline-success rounded-circle" 
                                    onclick="confirmRedemption({{ $redemption->id }})" 
                                    title="Konfirmasi">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger rounded-circle" 
                                    onclick="cancelRedemption({{ $redemption->id }})" 
                                    title="Batalkan">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            Belum ada data penukaran poin
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-top">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-muted small mb-2 mb-md-0">
                    Menampilkan {{ $redemptions->firstItem() ?? 0 }} - {{ $redemptions->lastItem() ?? 0 }} dari {{ $redemptions->total() }} data
                </div>
                <div>
                    {{ $redemptions->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Redemption -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Detail Penukaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailContent">
                <!-- Content will be filled by JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="printVoucherBtn" style="display: none;">
                    <i class="bi bi-printer me-1"></i> Cetak Voucher
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-responsive {
        min-height: 400px;
    }
    .member-avatar .rounded-circle {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .pagination {
        margin-bottom: 0;
    }
</style>
@endpush

@push('scripts')
<script>
    let currentUrl = "{{ route('kasir.point-rewards.redeem-history') }}";
    
    // Fungsi untuk memuat ulang tabel dengan filter
    function loadTable() {
        const search = $('#searchMember').val();
        const status = $('#filterStatus').val();
        const date = $('#filterDate').val();
        
        $('#loadingIndicator').show();
        $('#tableContent').hide();
        
        $.ajax({
            url: currentUrl,
            method: 'GET',
            data: {
                search: search,
                status: status,
                date: date,
                ajax: 1
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(res) {
                if (res.success) {
                    renderTable(res.data);
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat data', 'error');
            },
            complete: function() {
                $('#loadingIndicator').hide();
                $('#tableContent').show();
            }
        });
    }
    
    // Render tabel
    function renderTable(data) {
        let html = '';
        
        if (data.data && data.data.length > 0) {
            data.data.forEach((item, index) => {
                const statusBadge = item.status === 'completed' 
                    ? '<span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Selesai</span>'
                    : (item.status === 'pending' 
                        ? '<span class="badge bg-warning text-dark rounded-pill px-3"><i class="bi bi-clock me-1"></i> Pending</span>'
                        : '<span class="badge bg-danger rounded-pill px-3"><i class="bi bi-x-circle me-1"></i> Dibatalkan</span>');
                
                html += `
                    <tr>
                        <td class="px-4">${((data.current_page - 1) * data.per_page) + index + 1}</td>
                        <td><span class="fw-bold text-primary">${item.redemption_code}</span></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="member-avatar me-2">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 35px; height: 35px; font-size: 14px;">
                                        ${item.customer.name.charAt(0).toUpperCase()}
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-bold">${escapeHtml(item.customer.name)}</div>
                                    <small class="text-muted">${item.customer.phone}</small>
                                </div>
                            </div>
                        </td>
                        <td>${escapeHtml(item.reward.name)}</td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-star-fill me-1"></i> ${Number(item.points_used).toLocaleString()}
                            </span>
                        </td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center"><small>${new Date(item.created_at).toLocaleString('id-ID')}</small></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-info rounded-circle" onclick="showDetail(${item.id})" title="Detail">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${item.status === 'pending' ? `
                            <button class="btn btn-sm btn-outline-success rounded-circle" onclick="confirmRedemption(${item.id})" title="Konfirmasi">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="cancelRedemption(${item.id})" title="Batalkan">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            });
        } else {
            html = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        Tidak ada data penukaran poin
                    </td>
                </tr>
            `;
        }
        
        $('#redemptionTableBody').html(html);
        
        // Update pagination
        if (data.links) {
            let paginationHtml = '<nav><ul class="pagination justify-content-end">';
            data.links.forEach(link => {
                const activeClass = link.active ? 'active' : '';
                const disabledClass = !link.url ? 'disabled' : '';
                paginationHtml += `
                    <li class="page-item ${activeClass} ${disabledClass}">
                        <a class="page-link" href="#" onclick="event.preventDefault(); goToPage('${link.url}')">${link.label}</a>
                    </li>
                `;
            });
            paginationHtml += '</ul></nav>';
            $('.pagination-container').html(paginationHtml);
        }
        
        // Update info
        $('.text-muted.small:first').html(`Menampilkan ${data.from || 0} - ${data.to || 0} dari ${data.total} data`);
    }
    
    function goToPage(url) {
        if (url) {
            currentUrl = url;
            loadTable();
        }
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Show Detail
    window.showDetail = function(id) {
        $.ajax({
            url: `/kasir/point-rewards/redemption/${id}`,
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    const data = res.data;
                    const isVoucher = data.reward.reward_type === 'voucher';
                    
                    let html = `
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Kode Penukaran</label>
                            <div class="fw-bold fs-5 text-primary">${data.redemption_code}</div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="text-muted small mb-1">Member</label>
                                <div class="fw-bold">${escapeHtml(data.customer.name)}</div>
                                <small class="text-muted">${data.customer.phone}</small>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small mb-1">Hadiah</label>
                                <div class="fw-bold">${escapeHtml(data.reward.name)}</div>
                                <small class="text-muted">${data.reward.reward_type == 'product' ? 'Produk' : 'Voucher'}</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="text-muted small mb-1">Poin Digunakan</label>
                                <div class="fw-bold text-warning">${Number(data.points_used).toLocaleString()} poin</div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small mb-1">Status</label>
                                <div>
                                    ${data.status === 'completed' ? '<span class="badge bg-success">Selesai</span>' : 
                                      (data.status === 'pending' ? '<span class="badge bg-warning">Pending</span>' : 
                                      '<span class="badge bg-danger">Dibatalkan</span>')}
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Tanggal Penukaran</label>
                            <div>${new Date(data.created_at).toLocaleString('id-ID')}</div>
                        </div>
                        ${data.admin_notes ? `
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Catatan Admin</label>
                            <div class="bg-light p-2 rounded">${escapeHtml(data.admin_notes)}</div>
                        </div>
                        ` : ''}
                    `;
                    
                    $('#detailContent').html(html);
                    
                    if (isVoucher && data.status === 'completed') {
                        $('#printVoucherBtn').show().off('click').on('click', function() {
                            window.open(`/kasir/point-rewards/voucher/${data.id}/print`, '_blank');
                        });
                    } else {
                        $('#printVoucherBtn').hide();
                    }
                    
                    $('#detailModal').modal('show');
                }
            }
        });
    };
    
    // Confirm Redemption
    window.confirmRedemption = function(id) {
        Swal.fire({
            title: 'Konfirmasi Penukaran',
            text: 'Apakah Anda yakin ingin menyetujui penukaran ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/kasir/point-rewards/redemption/${id}/confirm`,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Berhasil!', res.message, 'success').then(() => {
                                loadTable();
                            });
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    };
    
    // Cancel Redemption
    window.cancelRedemption = function(id) {
        Swal.fire({
            title: 'Batalkan Penukaran',
            text: 'Apakah Anda yakin ingin membatalkan penukaran ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/kasir/point-rewards/redemption/${id}/cancel`,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Berhasil!', res.message, 'success').then(() => {
                                loadTable();
                            });
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    };
    
    // Event Listeners
    $('#searchMember').on('input', function() {
        loadTable();
    });
    
    $('#filterStatus').on('change', function() {
        loadTable();
    });
    
    $('#filterDate').on('change', function() {
        loadTable();
    });
    
    $('#resetFilter').on('click', function() {
        $('#searchMember').val('');
        $('#filterStatus').val('');
        $('#filterDate').val('');
        loadTable();
    });
</script>
@endpush