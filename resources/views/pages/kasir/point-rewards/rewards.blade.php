@extends('layouts.kasir')

@section('title', 'Daftar Hadiah')

@section('content')
<div class="page-heading d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-dark">🎁 Katalog Hadiah</h3>
        <p class="text-muted mb-0">Cari dan filter hadiah untuk member Alunea Cafe</p>
    </div>
</div>

<!-- Filter Box -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">CARI NAMA</label>
                <div class="input-group border rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="inputSearch" class="form-control border-0 ps-0 shadow-none" placeholder="Ketik nama hadiah...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">TIPE</label>
                <select id="selectType" class="form-select border rounded-3 shadow-none">
                    <option value="all">Semua Tipe</option>
                    <option value="product">Produk</option>
                    <option value="voucher">Voucher</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-light w-100 rounded-3 border fw-bold" onclick="resetFilters()">
                    <i class="bi bi-arrow-repeat me-1"></i> Reset Filter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Grid Hadiah -->
<div class="row g-4" id="rewardGrid">
    @forelse($rewards as $reward)
    <div class="col-md-6 col-lg-4 reward-card-item" 
         data-name="{{ strtolower($reward->name) }}" 
         data-type="{{ $reward->reward_type }}">
        <div class="card border-0 shadow-sm rounded-4 h-100 reward-card {{ $reward->stock <= 0 ? 'opacity-50' : '' }}">
            <div class="card-body p-4 text-center">
                <div class="mb-3">
                    @if($reward->reward_type == 'product')
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-inline-block"><i class="bi bi-cup-hot fs-3"></i></div>
                    @else
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-inline-block"><i class="bi bi-ticket-perforated fs-3"></i></div>
                    @endif
                </div>
                <h5 class="fw-bold mb-1">{{ $reward->name }}</h5>
                <p class="text-muted small mb-3">{{ Str::limit($reward->description, 50) }}</p>
                <div class="badge bg-light text-primary rounded-pill px-3 py-2 fw-bold">
                    <i class="bi bi-star-fill text-warning me-1"></i> {{ number_format($reward->points_required) }} Pts
                </div>
                <div class="mt-2 small text-muted">Stok: {{ $reward->stock }}</div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <p class="text-muted">Data hadiah belum tersedia.</p>
    </div>
    @endforelse
</div>

<!-- Not Found State -->
<div id="notFound" class="text-center py-5" style="display: none;">
    <i class="bi bi-search fs-1 text-muted opacity-25"></i>
    <p class="mt-3 text-muted">Hadiah tidak ditemukan dengan filter tersebut.</p>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        // Fungsi Filter Utama
        function applyFilter() {
            const searchValue = $('#inputSearch').val().toLowerCase().trim();
            const typeValue = $('#selectType').val();
            let visibleCount = 0;

            $('.reward-card-item').each(function() {
                const name = $(this).data('name');
                const type = $(this).data('type');

                const matchSearch = name.includes(searchValue);
                const matchType = (typeValue === 'all' || type === typeValue);

                if (matchSearch && matchType) {
                    $(this).fadeIn(200);
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            // Tampilkan pesan jika tidak ada hasil
            if (visibleCount === 0) {
                $('#notFound').show();
            } else {
                $('#notFound').hide();
            }
        }

        // Listener Real-time
        $('#inputSearch').on('keyup', applyFilter);
        $('#selectType').on('change', applyFilter);

        // Reset Filter
        window.resetFilters = function() {
            $('#inputSearch').val('');
            $('#selectType').val('all');
            $('.reward-card-item').fadeIn(200);
            $('#notFound').hide();
        };
    });
</script>

<style>
    .reward-card { transition: all 0.3s; border: 1px solid #f0f0f0 !important; }
    .reward-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
</style>
@endpush