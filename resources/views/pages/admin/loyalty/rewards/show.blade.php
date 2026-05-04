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

@section('title', 'Detail Hadiah Poin')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Detail Hadiah Poin</h3>
                <p class="text-subtitle text-muted">Informasi lengkap tentang hadiah yang dapat ditukar oleh pelanggan.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first text-end">
                <a href="{{ route($routePrefix . '.loyalty.rewards') }}" class="btn btn-light-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="row">
        {{-- KOLOM KIRI: Gambar & Info Ringkas --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    {{-- Gambar Hadiah --}}
                    @if($reward->image)
                        <img src="{{ asset('storage/'.$reward->image) }}" 
                             class="rounded img-fluid mb-3" 
                             style="width: 200px; height: 200px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto mb-3" 
                             style="width: 200px; height: 200px;">
                            <i class="bi bi-gift fs-1 text-muted"></i>
                        </div>
                    @endif

                    {{-- Nama Hadiah --}}
                    <h4 class="fw-bold mt-3 mb-2">{{ $reward->name }}</h4>
                    
                    {{-- Status Badge --}}
                    <div class="mb-3">
                        @if($reward->is_active)
                            <span class="badge bg-success px-3 py-2">Aktif</span>
                        @else
                            <span class="badge bg-danger px-3 py-2">Non-Aktif</span>
                        @endif
                    </div>

                    {{-- Poin yang Dibutuhkan --}}
                    <div class="alert alert-primary py-2">
                        <i class="bi bi-star-fill me-2"></i>
                        <strong>{{ number_format($reward->points_required, 0) }} Poin</strong>
                        <br>
                        <small>diperlukan untuk menukar hadiah ini</small>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-grid gap-2 mt-3">
                <a href="{{ route($routePrefix . '.loyalty.rewards.edit', $reward->id) }}" 
                   class="btn btn-warning fw-bold shadow-sm">
                    <i class="bi bi-pencil-square me-1"></i> Edit Hadiah
                </a>
                <button type="button" 
                        class="btn btn-outline-danger fw-bold btn-delete" 
                        data-id="{{ $reward->id }}" 
                        data-name="{{ $reward->name }}">
                    <i class="bi bi-trash me-1"></i> Hapus Hadiah
                </button>
            </div>
        </div>

        {{-- KOLOM KANAN: Informasi Detail --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Informasi Lengkap Hadiah
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="35%" class="text-muted">Tipe Hadiah</th>
                            <td>
                                @if($reward->reward_type == 'product')
                                    <span class="badge bg-light-primary text-primary">
                                        <i class="bi bi-box-seam me-1"></i> Produk Fisik
                                    </span>
                                @elseif($reward->reward_type == 'voucher')
                                    <span class="badge bg-light-success text-success">
                                        <i class="bi bi-ticket me-1"></i> Voucher
                                    </span>
                                @else
                                    <span class="badge bg-light-secondary text-secondary">
                                        <i class="bi bi-gift me-1"></i> Lainnya
                                    </span>
                                @endif
                            </td>
                        </tr>

                        @if($reward->reward_type == 'product')
                        <tr>
                            <th class="text-muted">Stok Tersedia</th>
                            <td>
                                <span class="fw-bold {{ $reward->stock <= 5 ? 'text-danger' : 'text-success' }}">
                                    {{ $reward->stock }} Unit
                                </span>
                                @if($reward->stock <= 5 && $reward->stock > 0)
                                    <small class="text-warning d-block">Stok menipis!</small>
                                @elseif($reward->stock == 0)
                                    <small class="text-danger d-block">Stok habis!</small>
                                @endif
                            </td>
                        </tr>
                        @endif

                        @if($reward->reward_type == 'voucher')
                        <tr>
                            <th class="text-muted">Nilai Voucher</th>
                            <td>
                                <span class="fw-bold text-success fs-5">
                                    Rp {{ number_format($reward->value_amount, 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        @endif

                        <tr>
                            <th class="text-muted">Minimal Stok Alert</th>
                            <td>{{ $reward->stock_threshold ?? 'Tidak ditentukan' }}</td>
                        </tr>

                        <tr>
                            <th class="text-muted">Deskripsi Hadiah</th>
                            <td>
                                <p class="mb-0 text-secondary">
                                    {{ $reward->description ?? 'Tidak ada deskripsi untuk hadiah ini.' }}
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Total Penukaran</th>
                            <td>
                                <span class="fw-bold text-primary">
                                    {{ $reward->redemptions_count ?? 0 }} kali ditukar
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Terakhir Diperbarui</th>
                            <td>
                                <small class="text-muted">
                                    {{ $reward->updated_at->format('d F Y H:i:s') }}
                                    <br>({{ $reward->updated_at->diffForHumans() }})
                                </small>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Dibuat Pada</th>
                            <td>
                                <small class="text-muted">
                                    {{ $reward->created_at->format('d F Y H:i:s') }}
                                </small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Tips Card --}}
            <div class="alert alert-light-info border-0 shadow-sm mt-3">
                <h6 class="fw-bold">
                    <i class="bi bi-lightbulb me-2"></i>
                    Tips Pengelolaan Hadiah
                </h6>
                <ul class="small mb-0">
                    <li>Pastikan stok hadiah selalu mencukupi untuk menghindari penukaran gagal.</li>
                    <li>Non-aktifkan hadiah yang sedang habis stok untuk sementara waktu.</li>
                    <li>Update informasi hadiah secara berkala agar tetap menarik bagi pelanggan.</li>
                    <li>Gunakan gambar yang menarik untuk meningkatkan minat penukaran.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI HAPUS --}}
<div class="modal fade" id="deleteModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus Hadiah</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                </div>
                <p class="text-center">
                    Apakah Anda yakin ingin menghapus hadiah 
                    <strong class="text-danger" id="deleteRewardName"></strong>?
                </p>
                <p class="text-danger small text-center">
                    <i class="bi bi-info-circle me-1"></i>
                    Tindakan ini tidak dapat dibatalkan. Semoga data riwayat penukaran akan terpengaruh.
                </p>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Ya, Hapus Hadiah
                    </button>
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
    const deleteButtons = document.querySelectorAll('.btn-delete');
    
    deleteButtons.forEach(btn => {
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

    // Notifikasi Sukses
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: "{{ session('success') }}",
        timer: 2500,
        showConfirmButton: false,
        backdrop: true
    });
    @endif

    // Notifikasi Error
    @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: "{{ session('error') }}",
        timer: 3000,
        showConfirmButton: true
    });
    @endif

    // Notifikasi Warning
    @if(session('warning'))
    Swal.fire({
        icon: 'warning',
        title: 'Perhatian!',
        text: "{{ session('warning') }}",
        timer: 3000,
        showConfirmButton: true
    });
    @endif
});
</script>
@endpush