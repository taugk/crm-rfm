@php
    $layout = match(auth()->user()->role) {
        'manager' => 'layouts.manager',
        'admin' => 'layouts.admin',
        default => 'layouts.admin',
    };
    
    $routePrefix = match(auth()->user()->role) {
        'manager' => 'manager',
        'admin' => 'admin',
        default => 'admin'
    };
@endphp

@extends($layout)

@section('title', 'Katalog Hadiah Poin')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Katalog Hadiah Poin</h3>
                <p class="text-subtitle text-muted">Kelola daftar produk dan voucher yang dapat ditukar oleh pelanggan.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first text-end">
                <a href="{{ route($routePrefix . '.loyalty.rewards.create') }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Hadiah Baru
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    {{-- ================= STATS WIDGET ================= --}}
    <section class="row">
        <div class="col-6 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon purple mb-2 me-3"><i class="bi bi-gift"></i></div>
                        <div>
                            <h6 class="text-muted font-semibold">Total Item Hadiah</h6>
                            <h6 class="font-extrabold mb-0">{{ $rewards->count() }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon blue mb-2 me-3"><i class="bi bi-box-seam"></i></div>
                        <div>
                            <h6 class="text-muted font-semibold">Stok Hadiah Fisik</h6>
                            <h6 class="font-extrabold mb-0">{{ $rewards->where('reward_type', 'product')->sum('stock') }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body px-4 py-4-5">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon green mb-2 me-3"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <h6 class="text-muted font-semibold">Hadiah Aktif</h6>
                            <h6 class="font-extrabold mb-0">{{ $rewards->where('is_active', true)->count() }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TABLE AREA ================= --}}
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="rewardTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Informasi Hadiah</th>
                                <th>Tipe</th>
                                <th class="text-center">Biaya Poin</th>
                                <th class="text-center">Stok / Nilai</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rewards as $reward)
                            <tr>
                                <td class="text-center small">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($reward->image)
                                            <img src="{{ asset('storage/'.$reward->image) }}" class="rounded me-3" width="50" height="50" style="object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="fw-bold d-block text-dark">{{ $reward->name }}</span>
                                            <small class="text-muted d-block text-truncate" style="max-width: 200px;">{{ $reward->description ?? 'Tidak ada deskripsi' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($reward->reward_type == 'product')
                                        <span class="badge bg-light-primary text-primary">Barang</span>
                                    @elseif($reward->reward_type == 'voucher')
                                        <span class="badge bg-light-success text-success">Voucher</span>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary">Lainnya</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-primary"><i class="bi bi-star-fill me-1"></i>{{ number_format($reward->points_required, 0) }} Pts</span>
                                </td>
                                <td class="text-center">
                                    @if($reward->reward_type == 'product')
                                        <span class="fw-bold {{ $reward->stock <= 5 ? 'text-danger' : '' }}">{{ $reward->stock }}</span> <small class="text-muted">Unit</small>
                                    @elseif($reward->reward_type == 'voucher')
                                        <span class="fw-bold text-success">Rp {{ number_format($reward->value_amount, 0, ',', '.') }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($reward->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group gap-1">
                                        <a href="{{ route($routePrefix . '.loyalty.rewards.show', $reward->id) }}" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <a href="{{ route($routePrefix . '.loyalty.rewards.edit', $reward->id) }}" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-delete" 
                                                data-id="{{ $reward->id }}" 
                                                data-name="{{ $reward->name }}">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-gift fs-1 d-block mb-3"></i>
                                    Belum ada hadiah yang didaftarkan.
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

{{-- MODAL KONFIRMASI HAPUS --}}
<div class="modal fade" id="deleteModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus hadiah <strong id="deleteRewardName"></strong>?</p>
                <p class="text-danger small">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Konfirmasi Hapus dengan Modal
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const routePrefix = '{{ $routePrefix }}';
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const deleteForm = document.getElementById('deleteForm');
            
            document.getElementById('deleteRewardName').innerText = name;
            deleteForm.action = `/${routePrefix}/loyalty/rewards/${id}`;
            
            modal.show();
        });
    });

    // Notifikasi
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: "{{ session('success') }}",
        timer: 2000,
        showConfirmButton: false
    });
    @endif

    @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: "{{ session('error') }}",
        timer: 3000,
        showConfirmButton: true
    });
    @endif
});
</script>
@endpush