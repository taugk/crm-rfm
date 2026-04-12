@extends('layouts.admin')

@section('title', 'Log Mutasi Poin')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Log Mutasi Poin</h3>
                <p class="text-subtitle text-muted">Riwayat lengkap penambahan dan penggunaan poin pelanggan.</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 py-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <h5 class="mb-0">Riwayat Mutasi</h5>
                    </div>
                    {{-- Real-time Search --}}
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="logSearch" class="form-control bg-light border-0" 
                                placeholder="Cari nama pelanggan atau deskripsi...">
                        </div>
                    </div>
                    {{-- Type Filter --}}
                    <div class="col-md-3 text-end">
                        <select id="typeFilter" class="form-select border-0 bg-light">
                            <option value="all">Semua Tipe</option>
                            <option value="earn">Penambahan (+)</option>
                            <option value="redeem">Pengurangan (-)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="logTable">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Tipe</th>
                                <th>Jumlah Poin</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr class="log-row">
                                <td>
                                    <span class="d-block fw-bold">{{ $log->created_at->format('d M Y') }}</span>
                                    <small class="text-muted">{{ $log->created_at->format('H:i') }} WIB</small>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $log->customer->name }}</span>
                                    <br><small class="text-muted">ID: #{{ $log->customer_id }}</small>
                                </td>
                                <td>
                                    @if($log->type == 'earn')
                                        <span class="badge bg-light-success text-success">EARN</span>
                                    @else
                                        <span class="badge bg-light-danger text-danger">REDEEM</span>
                                    @endif
                                </td>
                                <td class="fw-bold {{ $log->type == 'earn' ? 'text-success' : 'text-danger' }}">
                                    {{ $log->type == 'earn' ? '+' : '-' }}{{ number_format($log->amount) }}
                                </td>
                                <td>
                                    <span class="text-small">{{ $log->description }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Belum ada aktivitas poin.</td>
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
    const searchInput = document.getElementById('logSearch');
    const typeFilter = document.getElementById('typeFilter');
    const tableRows = document.querySelectorAll('.log-row');

    function filterLogs() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterValue = typeFilter.value.toLowerCase();

        tableRows.forEach(row => {
            const customerName = row.cells[1].textContent.toLowerCase();
            const description = row.cells[4].textContent.toLowerCase();
            const type = row.cells[2].textContent.toLowerCase();

            const matchesSearch = customerName.includes(searchTerm) || description.includes(searchTerm);
            const matchesType = (filterValue === 'all' || type.includes(filterValue));

            if (matchesSearch && matchesType) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterLogs);
    typeFilter.addEventListener('change', filterLogs);
});
</script>
@endpush