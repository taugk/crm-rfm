@extends('layouts.kasir')

@section('title', 'Penukaran Poin')

@section('content')
<div class="page-heading d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-dark">🎁 Penukaran Poin</h3>
        <p class="text-muted mb-0">Kelola poin dan hadiah member Alunea Café</p>
    </div>
</div>

<div class="row g-4">
    <!-- KOLOM KIRI: MEMBER SELECTION & LIST -->
    <div class="col-lg-4">
        <div class="sticky-top" style="top: 90px; z-index: 10;">
            
            <!-- Info Member Terpilih (Muncul Jika Sudah Dipilih) -->
            <div id="customerInfo" class="card border-0 shadow-sm rounded-4 mb-4" style="display: none;">
                <div class="card-body p-4 text-center">
                    <div class="position-relative d-inline-block mb-3">
                        <img id="customerPhoto" src="https://www.w3schools.com/howto/img_avatar.png" 
                             class="rounded-circle border border-4 border-white shadow-sm" 
                             style="width: 80px; height: 80px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 bg-success border border-3 border-white rounded-circle p-2"></span>
                    </div>
                    <h5 id="customerName" class="fw-bold mb-1">-</h5>
                    <p id="customerPhone" class="text-muted small mb-3">-</p>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-4 mb-3">
                        <small class="text-muted d-block">Poin Aktif</small>
                        <h4 id="customerPoints" class="fw-bold text-primary mb-0">0</h4>
                    </div>
                    <button class="btn btn-outline-danger btn-sm w-100 rounded-pill" id="clearCustomerBtn">
                        <i class="bi bi-person-x me-2"></i>Ganti Member
                    </button>
                </div>
            </div>

            <!-- Panel Daftar Member & Search -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-people me-2"></i>Daftar Member</h6>
                        <div class="input-group shadow-sm rounded-3 overflow-hidden border">
                            <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchMemberInput" class="form-control border-0 ps-0 shadow-none" 
                                   placeholder="Cari nama atau nomor...">
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div id="searchLoading" class="text-center py-3" style="display: none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2 text-muted small">Mencari member...</span>
                    </div>

                    <!-- Scrollable Member List -->
                    <div class="list-group list-group-flush border-top" id="memberListArea" style="max-height: 450px; overflow-y: auto;">
                        @forelse($members ?? [] as $member)
                        <a href="javascript:void(0)" 
                           class="list-group-item list-group-item-action py-3 px-4 member-item-row"
                           data-member-id="{{ $member->id }}"
                           data-member-name="{{ $member->name }}"
                           data-member-phone="{{ $member->phone }}"
                           data-member-points="{{ $member->total_points }}"
                           data-member-photo="{{ $member->profile_photo }}"
                           onclick="window.setSelectedCustomer({{ $member->id }}, '{{ addslashes($member->name) }}', '{{ $member->phone }}', {{ $member->total_points }}, '{{ $member->profile_photo }}')">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="member-data">
                                    <div class="fw-bold text-dark member-name">{{ $member->name }}</div>
                                    <small class="text-muted member-phone">{{ $member->phone }}</small>
                                </div>
                                <span class="badge bg-primary-soft text-primary rounded-pill">{{ number_format($member->total_points) }} pts</span>
                            </div>
                        </a>
                        @empty
                        <div class="p-5 text-center text-muted" id="emptyMemberList">
                            <i class="bi bi-person-exclamation fs-2 d-block mb-2"></i>
                            <small>Tidak ada data member</small>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: KATALOG HADIAH -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 p-4">
                <h5 class="mb-0 fw-bold">🎁 Katalog Hadiah</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <div class="row g-3">
                    @forelse($rewards as $reward)
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 reward-card rounded-4 border {{ $reward->stock <= 0 ? 'bg-light opacity-75' : '' }}"
                             onclick="window.selectReward({{ $reward->id }}, '{{ addslashes($reward->name) }}', {{ $reward->points_required }})">
                            <div class="card-body p-3 text-center">
                                <div class="reward-icon-bg mb-3 mx-auto 
                                    {{ $reward->reward_type == 'product' ? 'bg-primary-soft text-primary' : ($reward->reward_type == 'voucher' ? 'bg-info-soft text-info' : 'bg-success-soft text-success') }}">
                                    <i class="bi {{ $reward->reward_type == 'product' ? 'bi-cup-hot' : ($reward->reward_type == 'voucher' ? 'bi-ticket-perforated' : 'bi-gift') }}"></i>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">{{ $reward->name }}</h6>
                                <p class="text-muted x-small mb-3">{{ Str::limit($reward->description ?? 'Redeem reward', 40) }}</p>
                                <div class="badge bg-light text-primary rounded-pill px-3 py-2 w-100">
                                    <i class="bi bi-star-fill text-warning me-1"></i> {{ number_format($reward->points_required) }} Pts
                                </div>
                                <div class="mt-2 x-small {{ $reward->stock > 0 ? 'text-muted' : 'text-danger fw-bold' }}">
                                    Stok: {{ $reward->stock }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 py-5 text-center text-muted">
                        <p>Katalog hadiah kosong.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI -->
<div class="modal fade" id="redeemModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center">
            <div class="modal-body p-4">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                    <i class="bi bi-check2-circle text-success fs-1"></i>
                </div>
                <h5 class="fw-bold">Konfirmasi</h5>
                <p class="text-muted small">Tukarkan poin member untuk:</p>
                <div class="p-2 bg-light rounded-3 fw-bold text-primary mb-3" id="modalRewardName">-</div>
                <div class="d-grid gap-2">
                    <button class="btn btn-success rounded-3 fw-bold" id="confirmRedeemBtn">Ya, Tukar Sekarang</button>
                    <button class="btn btn-light rounded-3 text-muted" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .reward-icon-bg { width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; border-radius: 14px; font-size: 1.6rem; }
    .x-small { font-size: 0.72rem; }
    .reward-card { transition: all 0.2s; cursor: pointer; border: 1px solid #eee !important; }
    .reward-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: var(--bs-primary) !important; }
    .member-item-row.active { background-color: #f0f7ff; border-left: 4px solid #0d6efd; }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    let currentCustomer = null;
    let selectedReward = null;
    let searchTimeout;

    // Fungsi untuk escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Fungsi untuk memuat member dari server (AJAX)
    function loadMembers(searchQuery = '') {
        $('#searchLoading').show();
        
        $.ajax({
            url: "{{ route('kasir.point-rewards.search-customer') }}",
            method: 'GET',
            data: { q: searchQuery },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success && res.data && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(member => {
                        html += `
                            <a href="javascript:void(0)" 
                               class="list-group-item list-group-item-action py-3 px-4 member-item-row"
                               data-member-id="${member.id}"
                               data-member-name="${escapeHtml(member.name)}"
                               data-member-phone="${member.phone}"
                               data-member-points="${member.total_points}"
                               data-member-photo="${member.profile_photo || ''}"
                               onclick="window.setSelectedCustomer(${member.id}, '${escapeHtml(member.name)}', '${member.phone}', ${member.total_points}, '${member.profile_photo || ''}')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="member-data">
                                        <div class="fw-bold text-dark member-name">${escapeHtml(member.name)}</div>
                                        <small class="text-muted member-phone">${member.phone}</small>
                                    </div>
                                    <span class="badge bg-primary-soft text-primary rounded-pill">${Number(member.total_points).toLocaleString()} pts</span>
                                </div>
                            </a>
                        `;
                    });
                    $('#memberListArea').html(html);
                } else {
                    $('#memberListArea').html(`
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-person-exclamation fs-2 d-block mb-2"></i>
                            <small>Member tidak ditemukan</small>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                console.error('Search error:', xhr);
                $('#memberListArea').html(`
                    <div class="p-5 text-center text-danger">
                        <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                        <small>Gagal mencari member. Silakan coba lagi.</small>
                    </div>
                `);
            },
            complete: function() {
                $('#searchLoading').hide();
            }
        });
    }

    // Set selected customer
    window.setSelectedCustomer = function(id, name, phone, points, photo) {
        currentCustomer = { id, name, phone, points: parseInt(points) };
        
        $('#customerName').text(name);
        $('#customerPhone').text(phone);
        $('#customerPoints').text(points.toLocaleString());
        $('#customerPointsValue').text((points * 10).toLocaleString());
        $('#customerPhoto').attr('src', photo || 'https://www.w3schools.com/howto/img_avatar.png');
        
        $('#customerInfo').slideDown();
        
        // Highlight di daftar
        $('.member-item-row').removeClass('active');
        $(event.currentTarget).addClass('active');
    };

    // Select reward
    window.selectReward = function(id, name, points) {
        if (!currentCustomer) {
            Swal.fire('Member Belum Dipilih', 'Pilih member dari daftar di sebelah kiri.', 'warning');
            return;
        }
        if (currentCustomer.points < points) {
            Swal.fire('Poin Kurang', `Poin member (${currentCustomer.points.toLocaleString()}) tidak cukup untuk menukar ${name}.`, 'error');
            return;
        }
        selectedReward = { id, name, points: parseInt(points) };
        $('#modalRewardName').text(name);
        $('#redeemModal').modal('show');
    };

    $(document).ready(function() {
        // ==================== SEARCH WITH DEBOUNCE ====================
        $('#searchMemberInput').on('input', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val();
            
            // Jika query kosong, load semua member (kosongkan search)
            if (query === '') {
                loadMembers('');
            } else {
                // Tampilkan loading dan cari
                searchTimeout = setTimeout(() => {
                    loadMembers(query);
                }, 500);
            }
        });
        
        // ==================== CLEAR CUSTOMER ====================
        $('#clearCustomerBtn').click(function() {
            currentCustomer = null;
            selectedReward = null;
            $('#customerInfo').slideUp();
            $('.member-item-row').removeClass('active');
        });

        // ==================== REDEEM ====================
        $('#confirmRedeemBtn').click(function() {
            if (!currentCustomer || !selectedReward) {
                Swal.fire('Error', 'Data penukaran tidak lengkap.', 'error');
                return;
            }
            
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i>Memproses...');
            
            $.ajax({
                url: "{{ route('kasir.point-rewards.redeem') }}",
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: {
                    customer_id: currentCustomer.id,
                    reward_id: selectedReward.id
                },
                success: function(res) {
                    if (res.success) {
                        $('#redeemModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            html: `Poin berhasil ditukarkan.<br><strong>Kode: ${res.redemption_code}</strong>`,
                            confirmButtonColor: '#10b981'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan pada server.';
                    Swal.fire('Error', errorMsg, 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('Ya, Tukar Sekarang');
                }
            });
        });
        
        // Debug: Tampilkan jumlah member awal
        console.log('Initial members loaded:', $('.member-item-row').length);
    });
</script>
@endpush