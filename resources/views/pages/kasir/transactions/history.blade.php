@extends('layouts.kasir')

@section('title', 'Riwayat Transaksi')

@section('content')
<style>
    .filter-section {
        background: #fff;
        border-radius: var(--k-radius);
        border: 1px solid #EAECF0;
        padding: 20px;
        margin-bottom: 24px;
    }
    
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }
    
    .filter-group {
        flex: 1;
        min-width: 180px;
    }
    
    .filter-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #344054;
        margin-bottom: 6px;
    }
    
    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #EAECF0;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        transition: all 0.2s;
    }
    
    .filter-group input:focus,
    .filter-group select:focus {
        border-color: var(--k-accent);
        box-shadow: 0 0 0 3px var(--k-accent-soft);
    }
    
    .btn-filter {
        padding: 8px 20px;
        background: var(--k-accent);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-filter:hover {
        background: #C94415;
    }
    
    .btn-reset {
        padding: 8px 20px;
        background: #F4F6FB;
        color: #344054;
        border: 1px solid #EAECF0;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-reset:hover {
        background: #EAECF0;
    }
    
    .table-container {
        background: #fff;
        border-radius: var(--k-radius);
        border: 1px solid #EAECF0;
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table thead {
        background: #F9FAFB;
        border-bottom: 1px solid #EAECF0;
    }
    
    .data-table th {
        text-align: left;
        padding: 14px 16px;
        font-size: 12px;
        font-weight: 600;
        color: #667085;
    }
    
    .data-table td {
        padding: 14px 16px;
        font-size: 13px;
        color: #344054;
        border-bottom: 1px solid #EAECF0;
    }
    
    .data-table tr:hover {
        background: #F9FAFB;
        cursor: pointer;
    }
    
    .data-table tr:last-child td {
        border-bottom: none;
    }
    
    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-status.completed {
        background: #ECFDF3;
        color: #12B76A;
    }
    
    .badge-status.pending {
        background: #FEF6EE;
        color: #F79009;
    }
    
    .badge-status.cancelled {
        background: #FEF3F2;
        color: #F04438;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-icon {
        padding: 6px;
        background: none;
        border: none;
        cursor: pointer;
        color: #667085;
        transition: color 0.2s;
        border-radius: 6px;
    }
    
    .btn-icon:hover {
        background: #F4F6FB;
        color: var(--k-accent);
    }
    
    .pagination-container {
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #EAECF0;
    }
    
    .pagination {
        display: flex;
        gap: 8px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #EAECF0;
        border-radius: 8px;
        color: #344054;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .pagination a:hover {
        background: var(--k-accent);
        border-color: var(--k-accent);
        color: #fff;
    }
    
    .pagination .active span {
        background: var(--k-accent);
        border-color: var(--k-accent);
        color: #fff;
    }
    
    .info-text {
        font-size: 13px;
        color: #667085;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #98A2B3;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 16px;
    }
    
    .empty-state p {
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="k-heading">
    <h2><i class="bi bi-receipt"></i> Riwayat Transaksi</h2>
    <p>Lihat dan kelola semua transaksi penjualan</p>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <form method="GET" action="{{ route('kasir.transactions.history') }}" class="filter-form">
        <div class="filter-group">
            <label><i class="bi bi-calendar"></i> Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}">
        </div>
        
        <div class="filter-group">
            <label><i class="bi bi-calendar"></i> Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}">
        </div>
        
        <div class="filter-group">
            <label><i class="bi bi-person"></i> Pelanggan</label>
            <select name="customer_id">
                <option value="">Semua Pelanggan</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }} - {{ $customer->phone }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div class="filter-group">
            <button type="submit" class="btn-filter">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="{{ route('kasir.transactions.history') }}" class="btn-reset">
                <i class="bi bi-arrow-repeat"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Table Section -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Subtotal</th>
                <th>Diskon</th>
                <th>Total</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr onclick="window.location.href='{{ route('kasir.transactions.show', $transaction->id) }}'" style="cursor: pointer;">
                    <td>
                        <strong>#{{ $transaction->invoice_number }}</strong>
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        {{ $transaction->customer->name ?? 'Guest' }}
                        @if($transaction->customer && $transaction->customer->type == 'member')
                            <span class="badge-status" style="background: #EFF8FF; color: #2E90FA; padding: 2px 6px; font-size: 10px;">
                                <i class="bi bi-star-fill"></i> Member
                            </span>
                        @endif
                    </td>
                    <td>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                    <td>
                        @if($transaction->discount_amount > 0)
                            <span style="color: #12B76A;">-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <strong style="color: var(--k-accent);">
                            Rp {{ number_format($transaction->total_price, 0, ',', '.') }}
                        </strong>
                    </td>
                    <td>
                        @php
                            $paymentIcons = [
                                'cash' => 'cash-stack',
                                'qris' => 'qr-code',
                                'debit' => 'bank2',
                                'credit' => 'credit-card'
                            ];
                            $icon = $paymentIcons[$transaction->payment_method] ?? 'receipt';
                        @endphp
                        <i class="bi bi-{{ $icon }}"></i>
                        {{ ucfirst($transaction->payment_method) }}
                    </td>
                    <td>
                        <span class="badge-status {{ $transaction->status }}">
                            @if($transaction->status == 'completed')
                                <i class="bi bi-check-circle"></i> Selesai
                            @elseif($transaction->status == 'pending')
                                <i class="bi bi-clock"></i> Pending
                            @else
                                <i class="bi bi-x-circle"></i> Batal
                            @endif
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons" onclick="event.stopPropagation()">
                            <a href="{{ route('kasir.transactions.show', $transaction->id) }}" class="btn-icon" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('kasir.transactions.print', $transaction->id) }}" class="btn-icon" title="Cetak Invoice" target="_blank">
                                <i class="bi bi-printer"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Tidak ada data transaksi</p>
                            <small>Belum ada transaksi yang tercatat</small>
                            <div style="margin-top: 16px;">
                                <a href="{{ route('kasir.pos') }}" class="tb-btn primary">
                                    <i class="bi bi-bag-plus-fill"></i> Mulai Transaksi
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <!-- Pagination -->
    @if($transactions->hasPages())
        <div class="pagination-container">
            <div class="info-text">
                Menampilkan {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }} 
                dari {{ $transactions->total() }} transaksi
            </div>
            <div class="pagination">
                {{ $transactions->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
    // Optional: Auto-submit filter when date range is selected
    document.querySelectorAll('input[name="start_date"], input[name="end_date"], select[name="customer_id"]').forEach(element => {
        element.addEventListener('change', function() {
            if (this.value) {
                this.closest('form').submit();
            }
        });
    });
    
    // Format number function (if needed)
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    // Print invoice function
    function printInvoice(url) {
        window.open(url, '_blank');
    }
    
    // Show toast notification
    function showToast(message, type = 'success') {
        if (typeof kToast === 'function') {
            kToast(message, type);
        } else {
            console.log(message);
        }
    }
</script>
@endsection