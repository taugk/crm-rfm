@extends('layouts.kasir')

@section('title', 'Konfirmasi Penukaran Poin')

@section('content')
<div class="page-heading d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-dark">⏳ Konfirmasi Penukaran</h3>
        <p class="text-muted mb-0">Kelola permintaan reward member yang menunggu persetujuan</p>
    </div>
    <div>
        <a href="{{ route('kasir.point-rewards.index') }}" class="btn btn-white shadow-sm border rounded-pill px-4 fw-bold">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>

<!-- Statistik Ringkas -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-4 bg-warning bg-opacity-10 p-3 me-3">
                    <i class="bi bi-hourglass-split text-warning fs-3"></i>
                </div>
                <div>
                    <small class="text-muted d-block fw-semibold text-uppercase x-small">Pending Request</small>
                    <h3 class="fw-bold mb-0 text-dark" id="pendingCountDisplay">{{ $pendingRedemptions->count() }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-4 bg-primary bg-opacity-10 p-3 me-3">
                    <i class="bi bi-star-fill text-primary fs-3"></i>
                </div>
                <div>
                    <small class="text-muted d-block fw-semibold text-uppercase x-small">Total Poin Diminta</small>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($pendingRedemptions->sum('points_used')) }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-4 bg-emerald bg-opacity-10 p-3 me-3">
                    <i class="bi bi-people text-light fs-3"></i>
                </div>
                <div>
                    <small class="text-muted d-block fw-semibold text-uppercase x-small">Member Menunggu</small>
                    <h3 class="fw-bold mb-0 text-dark">{{ $pendingRedemptions->unique('customer_id')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Utama -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="card-header bg-white border-0 p-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Daftar Antrean Penukaran</h5>
            <button class="btn btn-light btn-sm rounded-3 px-3" onclick="location.reload()">
                <i class="bi bi-arrow-repeat me-1"></i> Refresh Data
            </button>
        </div>
    </div>
    
    <div class="card-body p-0">
        @if($pendingRedemptions->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 custom-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3" style="width: 40px;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th class="py-3">Member</th>
                            <th class="py-3">Detail Hadiah</th>
                            <th class="py-3 text-center">Poin Ditukar</th>
                            <th class="py-3 text-center">Status Poin</th>
                            <th class="py-3">Diajukan Pada</th>
                            <th class="py-3 text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingRedemptions as $redemption)
                        @php
                            $isPointsEnough = $redemption->customer->total_points >= 0; // Poin sudah dipotong, jadi selalu "sudah dipotong"
                            $pointsDeducted = true; // Poin sudah dikurangi saat request
                        @endphp
                        <tr id="row-{{ $redemption->id }}" class="pending-row">
                            <td class="ps-4">
                                <div class="form-check">
                                    <input class="form-check-input select-row" type="checkbox" data-id="{{ $redemption->id }}">
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-box me-3">
                                        @if($redemption->customer->profile_photo)
                                            <img src="{{ asset($redemption->customer->profile_photo) }}" class="rounded-3 shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                                        @else
                                            <div class="initials-avatar bg-primary d-flex align-items-center justify-content-center text-white fw-bold" style="width: 45px; height: 45px; border-radius: 12px;">
                                                {{ strtoupper(substr($redemption->customer->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $redemption->customer->name }}</div>
                                        <div class="text-muted x-small"><i class="bi bi-phone me-1"></i>{{ $redemption->customer->phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-primary mb-1">{{ $redemption->reward->name }}</div>
                                <span class="badge bg-light text-muted fw-normal border x-small">{{ ucfirst($redemption->reward->reward_type) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-warning fs-5">{{ number_format($redemption->points_used) }}</span>
                                <div class="x-small text-muted fw-bold">POIN</div>
                            </td>
                            <td class="text-center">
                                <div class="text-emerald small fw-bold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Sudah Dipotong
                                </div>
                                <div class="x-small text-muted">Sisa: {{ number_format($redemption->customer->total_points) }} poin</div>
                            </td>
                            <td>
                                <div class="small text-dark fw-medium">{{ $redemption->created_at->format('d M Y') }}</div>
                                <div class="x-small text-muted">{{ $redemption->created_at->format('H:i') }} WIB</div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-emerald btn-sm rounded-pill px-3 shadow-sm" 
                                            onclick="confirmSingle({{ $redemption->id }})" title="Setujui">
                                        <i class="bi bi-check-lg"></i> Setujui
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3" 
                                            onclick="cancelSingle({{ $redemption->id }})" title="Tolak">
                                        <i class="bi bi-x"></i> Tolak
                                    </button>
                                    <button class="btn btn-light btn-sm rounded-pill shadow-sm" 
                                            onclick="showDetail({{ $redemption->id }})">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Floating Action Bar for Bulk Actions -->
            <div id="bulkActionBar" class="bulk-action-float bg-dark rounded-pill px-4 py-3 shadow-lg" style="display: none;">
                <div class="d-flex align-items-center text-white gap-4">
                    <span class="fw-medium mb-0 small">
                        <span id="selectedCount" class="badge bg-emerald me-1">0</span> request terpilih
                    </span>
                    <div class="h-divider"></div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-emerald btn-sm rounded-pill px-4 fw-bold" id="bulkConfirmBtn">
                            <i class="bi bi-check-all me-1"></i> Setujui Semua
                        </button>
                        <button class="btn btn-outline-light btn-sm rounded-pill px-4" id="bulkCancelBtn">
                            <i class="bi bi-x-lg me-1"></i> Tolak Semua
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-3 opacity-25">
                    <i class="bi bi-check2-all" style="font-size: 5rem;"></i>
                </div>
                <h5 class="fw-bold text-dark">Tidak ada request pending</h5>
                <p class="text-muted">Semua permintaan penukaran poin sudah bersih.</p>
                <a href="{{ route('kasir.point-rewards.index') }}" class="btn btn-primary rounded-pill px-4 mt-2">
                    <i class="bi bi-gift me-2"></i> Tukar Poin Manual
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Detail Permintaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailContent">
                <!-- Ajax Content -->
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-emerald rounded-pill px-4 fw-bold" id="modalConfirmBtn">Setujui</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Loading -->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 mb-0">Memproses...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    :root { --emerald-color: #10b981; }
    .btn-emerald { background: var(--emerald-color); color: white; border: none; }
    .btn-emerald:hover { background: #059669; color: white; }
    .text-emerald { color: var(--emerald-color); }
    .bg-emerald { background-color: var(--emerald-color); }

    .custom-table thead th {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 2px solid #edf2f7;
    }

    .pending-row { transition: 0.2s; }

    .x-small { font-size: 0.65rem; }

    .bulk-action-float {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        animation: floatUp 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28);
    }
    @keyframes floatUp { 
        from { bottom: -100px; opacity: 0; } 
        to { bottom: 30px; opacity: 1; } 
    }

    .h-divider { width: 1px; height: 25px; background: rgba(255,255,255,0.2); }

    .form-check-input:checked {
        background-color: var(--emerald-color);
        border-color: var(--emerald-color);
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let selectedIds = [];

    $(document).ready(function() {
        // Select All Logic
        $('#selectAll').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.select-row').prop('checked', isChecked).trigger('change');
        });

        // Single Row Checkbox Logic
        $(document).on('change', '.select-row', function() {
            const id = $(this).data('id');
            const row = $(this).closest('tr');

            if ($(this).is(':checked')) {
                if (!selectedIds.includes(id)) selectedIds.push(id);
                row.addClass('bg-light');
            } else {
                selectedIds = selectedIds.filter(item => item !== id);
                row.removeClass('bg-light');
                $('#selectAll').prop('checked', false);
            }

            updateBulkBar();
        });

        function updateBulkBar() {
            if (selectedIds.length > 0) {
                $('#selectedCount').text(selectedIds.length);
                $('#bulkActionBar').fadeIn();
            } else {
                $('#bulkActionBar').fadeOut();
            }
        }

        // Bulk Actions
        $('#bulkConfirmBtn').on('click', function() {
            if (selectedIds.length === 0) {
                Swal.fire('Peringatan', 'Tidak ada request yang dipilih', 'warning');
                return;
            }
            confirmAction('confirm_bulk', selectedIds);
        });

        $('#bulkCancelBtn').on('click', function() {
            if (selectedIds.length === 0) {
                Swal.fire('Peringatan', 'Tidak ada request yang dipilih', 'warning');
                return;
            }
            confirmAction('cancel_bulk', selectedIds);
        });
    });

    function confirmSingle(id) { 
        confirmAction('confirm', id); 
    }
    
    function cancelSingle(id) { 
        confirmAction('cancel', id); 
    }

    function confirmAction(type, id) {
        const config = {
            confirm: { 
                title: 'Setujui Penukaran?', 
                text: 'Poin member akan tetap terpotong (sudah dipotong saat request). Stok hadiah akan berkurang.', 
                icon: 'question', 
                btn: '#10b981',
                confirmText: 'Ya, Setujui'
            },
            cancel: { 
                title: 'Tolak Penukaran?', 
                text: 'Poin akan dikembalikan ke member dan stok hadiah akan dikembalikan.', 
                icon: 'warning', 
                btn: '#ef4444',
                confirmText: 'Ya, Tolak'
            },
            confirm_bulk: { 
                title: 'Setujui Massal?', 
                text: `Setujui ${selectedIds.length} permintaan sekaligus. Poin member akan tetap terpotong.`, 
                icon: 'question', 
                btn: '#10b981',
                confirmText: 'Ya, Setujui Semua'
            },
            cancel_bulk: { 
                title: 'Tolak Massal?', 
                text: `Tolak ${selectedIds.length} permintaan sekaligus. Poin akan dikembalikan ke member.`, 
                icon: 'warning', 
                btn: '#ef4444',
                confirmText: 'Ya, Tolak Semua'
            }
        };

        const active = config[type];

        Swal.fire({
            title: active.title,
            text: active.text,
            icon: active.icon,
            showCancelButton: true,
            confirmButtonColor: active.btn,
            cancelButtonColor: '#6c757d',
            confirmButtonText: active.confirmText,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const action = type.includes('confirm') ? 'confirm' : 'cancel';
                const ids = Array.isArray(id) ? id : [id];
                executeRequest(action, ids);
            }
        });
    }

    function executeRequest(action, ids) {
        Swal.fire({
            title: 'Memproses...',
            text: 'Harap tunggu',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        const promises = ids.map(id => {
            let url = '';
            if (action === 'confirm') {
                url = `/kasir/point-rewards/redemption/${id}/confirm`;
            } else {
                url = `/kasir/point-rewards/redemption/${id}/cancel`;
            }
            
            return $.ajax({
                url: url,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
        });

        Promise.all(promises)
            .then((results) => {
                const allSuccess = results.every(r => r.success);
                if (allSuccess) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: `${results.length} request berhasil diproses.`,
                        confirmButtonColor: '#10b981'
                    }).then(() => location.reload());
                } else {
                    const errors = results.filter(r => !r.success).map(r => r.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: errors.join('\n'),
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: err.responseJSON?.message || 'Terjadi kesalahan saat memproses data.',
                    confirmButtonColor: '#ef4444'
                });
            });
    }

    function showDetail(id) {
        const detailContent = document.getElementById('detailContent');
        detailContent.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        $.get(`/kasir/point-rewards/redemption/${id}`, function(res) {
            if (res.success) {
                const data = res.data;
                const html = `
                    <div class="text-center mb-4">
                        <h2 class="fw-black text-primary mb-0">${data.redemption_code}</h2>
                        <small class="text-muted">KODE PENUKARAN</small>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="x-small text-muted fw-bold">MEMBER</label>
                            <p class="mb-0 fw-bold">${escapeHtml(data.customer.name)}</p>
                            <small class="text-muted">${data.customer.phone}</small>
                        </div>
                        <div class="col-6">
                            <label class="x-small text-muted fw-bold">HADIAH</label>
                            <p class="mb-0 fw-bold text-emerald">${escapeHtml(data.reward.name)}</p>
                            <small class="text-muted">${data.reward.reward_type}</small>
                        </div>
                        <div class="col-6">
                            <label class="x-small text-muted fw-bold">POIN DIGUNAKAN</label>
                            <p class="mb-0 fw-bold text-warning">${Number(data.points_used).toLocaleString()} POIN</p>
                            <small class="text-muted">Sudah dipotong dari saldo member</small>
                        </div>
                        <div class="col-6">
                            <label class="x-small text-muted fw-bold">SALDO SETELAH DIPOTONG</label>
                            <p class="mb-0 fw-bold">${Number(data.customer.total_points).toLocaleString()} POIN</p>
                        </div>
                        <div class="col-12">
                            <label class="x-small text-muted fw-bold">STOK HADIAH TERSISA</label>
                            <p class="mb-0 fw-bold">${Number(data.reward.stock).toLocaleString()}</p>
                        </div>
                        <div class="col-12">
                            <label class="x-small text-muted fw-bold">TANGGAL REQUEST</label>
                            <p class="mb-0">${new Date(data.created_at).toLocaleString('id-ID')}</p>
                        </div>
                        ${data.admin_notes ? `
                        <div class="col-12">
                            <label class="x-small text-muted fw-bold">CATATAN</label>
                            <p class="mb-0 bg-light p-2 rounded small">${escapeHtml(data.admin_notes)}</p>
                        </div>
                        ` : ''}
                    </div>
                `;
                detailContent.innerHTML = html;
                
                // Re-attach modal confirm button
                const modalConfirmBtn = document.getElementById('modalConfirmBtn');
                const newBtn = modalConfirmBtn.cloneNode(true);
                modalConfirmBtn.parentNode.replaceChild(newBtn, modalConfirmBtn);
                newBtn.addEventListener('click', () => {
                    bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
                    confirmSingle(id);
                });
                
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        }).fail(function(err) {
            detailContent.innerHTML = '<div class="text-center text-danger py-3">Gagal memuat detail</div>';
        });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
@endpush