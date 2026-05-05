@extends('layouts.customers')

@section('title', 'Riwayat Penukaran Poin')

@section('content')
<style>
    /* Glassmorphism Styles */
    .glass-card {
        background: rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08);
        border-radius: 20px;
    }

    .glass-header {
        background: rgba(255, 255, 255, 0.2) !important;
        backdrop-filter: blur(5px);
    }

    body {
        background: radial-gradient(circle at top right, #fdfcfb 0%, 100%);
        min-height: 100vh;
    }

    .stat-box {
        transition: transform 0.3s ease;
    }

    .stat-box:hover {
        transform: translateY(-5px);
    }

    .icon-shape {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.4);
    }

    .custom-table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #475569;
        border-bottom: none;
    }

    .btn-glass {
        background: rgba(255, 255, 255, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(4px);
        transition: 0.2s;
    }

    .btn-glass:hover {
        background: #fff;
        transform: scale(1.1);
    }
</style>

<div class="container-fluid py-2">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">📜 Riwayat Penukaran</h3>
                    <p class="text-muted">Pantau aktivitas loyalitas Anda di Alunea Café</p>
                </div>
                <a href="{{ route('customers.points.redeem') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-gift me-2"></i> Tukar Poin
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card glass-card border-0 stat-box">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="icon-shape bg-primary bg-opacity-10 me-3">
                        <i class="bi bi-star-fill text-primary fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold">Poin Digunakan</small>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalPointsUsed ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 stat-box">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="icon-shape bg-success bg-opacity-10 me-3">
                        <i class="bi bi-bag-check-fill text-success fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold">Total Klaim</small>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalRedemptions ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 stat-box">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="icon-shape bg-warning bg-opacity-10 me-3">
                        <i class="bi bi-gem text-warning fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold">Poin Anda</small>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($currentPoints ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card glass-card border-0 overflow-hidden">
        <div class="card-header bg-transparent border-0 p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Daftar Aktivitas</h5>
                
                <div class="d-flex gap-2">
                    <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white" style="width: 280px;">
                        <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchHistory" class="form-control border-0 ps-1" placeholder="Cari hadiah...">
                    </div>
                    
                    <select id="statusFilter" class="form-select rounded-pill shadow-sm border-0 px-3" style="width: 160px; cursor: pointer;">
                        <option value="">Semua Status</option>
                        <option value="completed">Selesai</option>
                        <option value="pending">Menunggu</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(isset($redemptions) && $redemptions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 custom-table" id="historyTable">
                        <thead class="glass-header">
                            <tr>
                                <th class="ps-4 py-3">No</th>
                                <th class="py-3">Invoice</th>
                                <th class="py-3">Item Hadiah</th>
                                <th class="py-3 text-center">Poin</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="py-3">Waktu</th>
                                <th class="py-3 text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($redemptions as $redemption)
                            <tr class="history-row border-bottom border-light" 
                                data-reward="{{ strtolower($redemption->reward->name) }}"
                                data-status="{{ $redemption->status }}">
                                <td class="ps-4 text-muted">{{ ($redemptions->currentPage() - 1) * $redemptions->perPage() + $loop->iteration }}</td>
                                <td>
                                    <span class="badge bg-white text-primary border border-primary border-opacity-25 px-2 py-1">
                                        {{ $redemption->redemption_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $redemption->reward->name }}</div>
                                    <div class="x-small text-muted">{{ ucfirst($redemption->reward->reward_type) }}</div>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-warning">{{ number_format($redemption->points_used) }} <small class="x-small">PTS</small></div>
                                </td>
                                <td class="text-center">
                                    @if($redemption->status == 'completed')
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2">
                                            <i class="bi bi-check-circle-fill me-1"></i> Selesai
                                        </span>
                                    @elseif($redemption->status == 'pending')
                                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2">
                                            <i class="bi bi-hourglass-split me-1"></i> Menunggu
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2">
                                            <i class="bi bi-x-circle-fill me-1"></i> Batal
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-dark fw-medium">{{ $redemption->created_at->format('d M Y') }}</div>
                                    <div class="x-small text-muted">{{ $redemption->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button class="btn btn-sm btn-glass rounded-circle p-2" 
                                                onclick="showDetail({{ $redemption->id }})" title="Lihat Detail">
                                            <i class="bi bi-eye text-primary"></i>
                                        </button>
                                        @if($redemption->status == 'completed' && $redemption->reward->reward_type == 'voucher')
                                            <button class="btn btn-sm btn-glass rounded-circle p-2" 
                                                    onclick="printVoucher({{ $redemption->id }})" title="Cetak Voucher">
                                                <i class="bi bi-printer text-emerald"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 bg-transparent">
                    {{ $redemptions->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="text-center py-5">
                    <div class="opacity-25 mb-3">
                        <i class="bi bi-archive" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="fw-bold">Belum Ada Riwayat</h5>
                    <p class="text-muted">Ayo mulai transaksi dan kumpulkan poin!</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Detail (Glass-themed) -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light">
                <h5 class="modal-title fw-bold">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailContent">
                <!-- Content injected via JS -->
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        const $search = $('#searchHistory');
        const $status = $('#statusFilter');
        const $rows = $('.history-row');

        /**
         * Real-time Filter (Case-Insensitive)
         */
        function applyFilter() {
            const query = $search.val().toLowerCase().trim();
            const filterValue = $status.val();
            
            let count = 0;

            $rows.each(function() {
                const $row = $(this);
                const rewardName = $row.data('reward').toString();
                const statusName = $row.data('status').toString();

                const matchesSearch = query === "" || rewardName.includes(query);
                const matchesStatus = filterValue === "" || statusName === filterValue;

                if (matchesSearch && matchesStatus) {
                    $row.show();
                    count++;
                } else {
                    $row.hide();
                }
            });

            // Handle Empty Search State
            $('#no-results').remove();
            if (count === 0) {
                $('#historyTable tbody').append(`
                    <tr id="no-results">
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-search d-block fs-3 mb-2"></i>
                            Data tidak ditemukan
                        </td>
                    </tr>
                `);
            }
        }

        $search.on('input', applyFilter);
        $status.on('change', applyFilter);
    });

    // Function Show Detail AJAX (Silakan sesuaikan URL-nya)
    function showDetail(id) {
        $('#detailContent').html('<div class="text-center py-3"><div class="spinner-border text-primary shadow-sm"></div></div>');
        $('#detailModal').modal('show');
        
        $.get(`/customer/points/detail/${id}`, function(res) {
            if (res.success) {
                const data = res.data;
                $('#detailContent').html(`
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted small">Status Transaksi</span>
                        <span class="fw-bold">${data.status.toUpperCase()}</span>
                    </div>
                    <div class="p-3 rounded-4 bg-white bg-opacity-50 border border-white mb-3">
                        <div class="small text-muted mb-1">Nama Hadiah</div>
                        <div class="fw-bold fs-5">${data.reward.name}</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3 rounded-4 bg-white bg-opacity-50 border border-white h-100">
                                <div class="small text-muted mb-1">Poin Terpakai</div>
                                <div class="fw-bold text-primary">${data.points_used} PTS</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 bg-white bg-opacity-50 border border-white h-100">
                                <div class="small text-muted mb-1">Kode Voucher</div>
                                <div class="fw-bold text-dark">${data.redemption_code}</div>
                            </div>
                        </div>
                    </div>
                `);
            }
        });
    }

    function printVoucher(id) {
        window.open(`/customer/points/print-voucher/${id}`, '_blank');
    }
</script>
@endpush