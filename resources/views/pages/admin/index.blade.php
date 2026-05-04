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

@section('title', 'Dashboard Admin')

@push('styles')
<style>
    /* =========================================================
       RESET & BASE
    ========================================================= */
    .dash-wrap * { box-sizing: border-box; }

    .dash-wrap {
        font-family: 'DM Sans', sans-serif;
        padding: 1.5rem;
        color: #1a1a2e;
    }

    /* =========================================================
       PAGE TITLE
    ========================================================= */
    .dash-page-title {
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
    }

    .dash-page-title h2 {
        font-size: 22px;
        font-weight: 600;
        margin: 0;
        color: #1a1a2e;
    }

    .dash-page-title p {
        font-size: 13px;
        color: #6b7280;
        margin: 3px 0 0;
    }

    .dash-breadcrumb {
        font-size: 12px;
        color: #9ca3af;
    }

    .dash-breadcrumb a {
        color: #6366f1;
        text-decoration: none;
    }

    .dash-breadcrumb span {
        margin: 0 6px;
    }

    /* =========================================================
       WELCOME CARD
    ========================================================= */
    .welcome-card {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #fff;
    }

    .welcome-card h3 {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 4px;
    }

    .welcome-card h3 span {
        color: #c7d2fe;
    }

    .welcome-card p {
        font-size: 13px;
        margin: 0;
        opacity: .85;
    }

    .welcome-icon {
        font-size: 40px;
        opacity: .6;
        line-height: 1;
    }

    /* =========================================================
       SECTION LABEL
    ========================================================= */
    .section-label {
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: .75rem;
    }

    /* =========================================================
       GRIDS
    ========================================================= */
    .grid-4 {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 1.5rem;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
        gap: 14px;
        margin-bottom: 1.5rem;
    }

    .grid-2b {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 1.5rem;
    }

    .grid-3 {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1.4fr);
        gap: 14px;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1024px) {
        .grid-4  { grid-template-columns: repeat(2, 1fr); }
        .grid-2  { grid-template-columns: 1fr; }
        .grid-2b { grid-template-columns: 1fr; }
        .grid-3  { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
        .grid-4 { grid-template-columns: 1fr 1fr; }
    }

    /* =========================================================
       STAT CARDS (primary 4)
    ========================================================= */
    .stat-card {
        background: #fff;
        border: 1px solid #f0f0f5;
        border-radius: 14px;
        padding: 1.1rem 1.1rem .9rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
        transition: box-shadow .2s, transform .2s;
    }

    .stat-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,.08);
        transform: translateY(-1px);
    }

    .stat-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .stat-label {
        font-size: 11px;
        color: #9ca3af;
        margin-bottom: 2px;
        font-weight: 500;
    }

    .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1;
    }

    .stat-value.sm { font-size: 16px; }

    .stat-sub {
        font-size: 11px;
        font-weight: 500;
    }

    /* icon colors */
    .ic-purple { background: #ede9fe; color: #7c3aed; }
    .ic-blue   { background: #dbeafe; color: #2563eb; }
    .ic-green  { background: #dcfce7; color: #16a34a; }
    .ic-red    { background: #fee2e2; color: #dc2626; }
    .ic-amber  { background: #fef3c7; color: #d97706; }
    .ic-teal   { background: #ccfbf1; color: #0d9488; }
    .ic-pink   { background: #fce7f3; color: #db2777; }
    .ic-gray   { background: #f3f4f6; color: #6b7280; }
    .ic-indigo { background: #e0e7ff; color: #4338ca; }

    .text-success { color: #16a34a; }
    .text-warn    { color: #d97706; }
    .text-info    { color: #2563eb; }
    .text-danger  { color: #dc2626; }
    .text-muted   { color: #9ca3af; }

    /* =========================================================
       MINI STAT CARDS (secondary 4)
    ========================================================= */
    .mini-stat {
        background: #fafafa;
        border: 1px solid #f0f0f5;
        border-radius: 12px;
        padding: .85rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .mini-stat-label {
        font-size: 11px;
        color: #9ca3af;
        font-weight: 500;
        margin-bottom: 3px;
    }

    .mini-stat-value {
        font-size: 18px;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1;
        margin-bottom: 4px;
    }

    .mini-stat-sub {
        font-size: 11px;
        font-weight: 500;
    }

    .mini-stat-icon {
        font-size: 26px;
        opacity: .3;
    }

    /* =========================================================
       CARDS (chart, table, list)
    ========================================================= */
    .dash-card {
        background: #fff;
        border: 1px solid #f0f0f5;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
    }

    .dash-card-header {
        padding: .85rem 1.1rem;
        border-bottom: 1px solid #f4f4f8;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .dash-card-header h4 {
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        color: #1a1a2e;
    }

    .dash-card-header .hdr-right {
        font-size: 12px;
        color: #6366f1;
        text-decoration: none;
        font-weight: 500;
    }

    .dash-card-header .hdr-muted {
        font-size: 12px;
        color: #9ca3af;
    }

    .dash-card-body {
        padding: 1rem 1.1rem;
    }

    .dash-card-body.p0 {
        padding: 0;
    }

    /* =========================================================
       TABLES
    ========================================================= */
    .dash-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }

    .dash-table thead th {
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        color: #9ca3af;
        padding: 10px 12px;
        border-bottom: 1px solid #f4f4f8;
        white-space: nowrap;
    }

    .dash-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #f9f9fb;
        color: #374151;
        vertical-align: middle;
    }

    .dash-table tbody tr:last-child td {
        border-bottom: none;
    }

    .dash-table tbody tr:hover td {
        background: #fafafa;
    }

    .dash-table td code {
        font-family: 'DM Mono', monospace;
        font-size: 11px;
        background: #f3f4f6;
        padding: 2px 6px;
        border-radius: 4px;
        color: #6366f1;
    }

    .dash-table .td-right { text-align: right; }
    .dash-table .td-center { text-align: center; }

    /* =========================================================
       BADGES
    ========================================================= */
    .badge {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 9px;
        border-radius: 20px;
        line-height: 1.6;
    }

    .badge-green  { background: #dcfce7; color: #15803d; }
    .badge-blue   { background: #dbeafe; color: #1d4ed8; }
    .badge-teal   { background: #ccfbf1; color: #0f766e; }
    .badge-red    { background: #fee2e2; color: #b91c1c; }
    .badge-amber  { background: #fef3c7; color: #b45309; }
    .badge-gray   { background: #f3f4f6; color: #4b5563; }
    .badge-purple { background: #ede9fe; color: #6d28d9; }
    .badge-indigo { background: #e0e7ff; color: #3730a3; }

    /* =========================================================
       RFM SEGMENT LIST
    ========================================================= */
    .seg-list { padding: .25rem 0; }

    .seg-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f4f4f8;
        font-size: 13px;
        color: #374151;
    }

    .seg-row:last-child { border-bottom: none; }

    .seg-left {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .seg-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* =========================================================
       TOP CUSTOMERS LIST
    ========================================================= */
    .top-customer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 0;
        border-bottom: 1px solid #f4f4f8;
    }

    .top-customer:last-child { border-bottom: none; }

    .tc-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .tc-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .tc-name {
        font-size: 13px;
        font-weight: 600;
        color: #1a1a2e;
        line-height: 1.2;
    }

    .tc-phone {
        font-size: 11px;
        color: #9ca3af;
    }

    /* =========================================================
       CHARTS
    ========================================================= */
    .chart-wrap {
        position: relative;
        width: 100%;
    }

    .chart-wrap canvas {
        display: block;
        width: 100% !important;
    }

    /* =========================================================
       EMPTY STATE
    ========================================================= */
    .empty-state {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #9ca3af;
    }

    .empty-state i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: .75rem;
    }

    .empty-state p {
        font-size: 13px;
        margin: 0 0 4px;
    }

    .empty-state small {
        font-size: 12px;
        color: #d1d5db;
    }
</style>
@endpush

@section('content')
<div class="dash-wrap">

    {{-- ===================== PAGE TITLE ===================== --}}
    <div class="dash-page-title">
        <div>
            <h2>Dashboard Admin</h2>
            <p>Sistem CRM &amp; Analisis RFM</p>
        </div>
        <div class="dash-breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span>/</span>
            <span>Admin</span>
        </div>
    </div>

    {{-- ===================== WELCOME ===================== --}}
    <div class="welcome-card">
        <div>
            <h3>Selamat datang, <span>{{ auth()->user()->name }}</span> 👋</h3>
            <p>Sistem CRM &amp; Analisis RFM — Kelola pelanggan, produk, transaksi, dan loyalitas dengan mudah.</p>
        </div>
        <div class="welcome-icon">📊</div>
    </div>

    {{-- ===================== STATISTIK UTAMA ===================== --}}
    <div class="section-label">Ringkasan utama</div>
    <div class="grid-4">

        <div class="stat-card">
            <div class="stat-row">
                <div class="stat-icon ic-purple">👥</div>
                <div>
                    <div class="stat-label">Total pelanggan</div>
                    <div class="stat-value">{{ number_format($totalCustomers ?? 0) }}</div>
                </div>
            </div>
            <div class="stat-sub text-success">↑ {{ number_format($totalActiveCustomers ?? 0) }} aktif</div>
        </div>

        <div class="stat-card">
            <div class="stat-row">
                <div class="stat-icon ic-blue">📦</div>
                <div>
                    <div class="stat-label">Total produk</div>
                    <div class="stat-value">{{ number_format($totalProducts ?? 0) }}</div>
                </div>
            </div>
            <div class="stat-sub text-success">✓ {{ number_format($totalActiveProducts ?? 0) }} aktif</div>
        </div>

        <div class="stat-card">
            <div class="stat-row">
                <div class="stat-icon ic-green">🛒</div>
                <div>
                    <div class="stat-label">Total transaksi</div>
                    <div class="stat-value">{{ number_format($totalTransactions ?? 0) }}</div>
                </div>
            </div>
            <div class="stat-sub text-info">📅 {{ number_format($todayTransactions->count() ?? 0) }} hari ini</div>
        </div>

        <div class="stat-card">
            <div class="stat-row">
                <div class="stat-icon ic-red">💰</div>
                <div>
                    <div class="stat-label">Total pendapatan</div>
                    <div class="stat-value sm">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="stat-sub text-success">↑ Rp {{ number_format($todayRevenue ?? 0, 0, ',', '.') }} hari ini</div>
        </div>

    </div>

    {{-- ===================== STATISTIK TAMBAHAN ===================== --}}
    <div class="section-label">Stok &amp; loyalitas</div>
    <div class="grid-4">

        <div class="mini-stat">
            <div>
                <div class="mini-stat-label">Total stok</div>
                <div class="mini-stat-value">{{ number_format($totalStock ?? 0) }}</div>
                <div class="mini-stat-sub text-warn">⚠ {{ $lowStockProducts ?? 0 }} menipis</div>
            </div>
            <div class="mini-stat-icon">📦</div>
        </div>

        <div class="mini-stat">
            <div>
                <div class="mini-stat-label">Promosi aktif</div>
                <div class="mini-stat-value">{{ $activePromotions ?? 0 }}</div>
                <div class="mini-stat-sub text-info">🕐 {{ $upcomingPromotions ?? 0 }} akan datang</div>
            </div>
            <div class="mini-stat-icon">🏷</div>
        </div>

        <div class="mini-stat">
            <div>
                <div class="mini-stat-label">Total poin</div>
                <div class="mini-stat-value">{{ number_format($totalPointsEarned ?? 0) }}</div>
                <div class="mini-stat-sub text-danger">🧾 {{ number_format($totalPointsRedeemed ?? 0) }} ditukar</div>
            </div>
            <div class="mini-stat-icon">⭐</div>
        </div>

        <div class="mini-stat">
            <div>
                <div class="mini-stat-label">Reward tersedia</div>
                <div class="mini-stat-value">{{ $availableRewards ?? 0 }}</div>
                <div class="mini-stat-sub text-warn">⏳ {{ $pendingRedemptions ?? 0 }} pending</div>
            </div>
            <div class="mini-stat-icon">🎁</div>
        </div>

    </div>

    {{-- ===================== GRAFIK + RFM ===================== --}}
    <div class="grid-2">

        <div class="dash-card">
            <div class="dash-card-header">
                <h4>Penjualan 7 hari terakhir</h4>
                <span class="hdr-muted">Rp</span>
            </div>
            <div class="dash-card-body">
                <div class="chart-wrap" style="height:220px">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-header">
                <h4>Segmentasi RFM</h4>
                @if(isset($latestRfmBatch) && $latestRfmBatch)
                    <span class="hdr-muted">{{ $latestRfmBatch->created_at->format('d/m/Y') }}</span>
                @endif
            </div>
            <div class="dash-card-body" style="padding:.5rem 1rem">
                @if(isset($rfmSegmentStats) && count($rfmSegmentStats) > 0)
                    <div class="seg-list">
                        @php
                            $segColors = [
                                'champions'        => ['dot' => '#16a34a', 'badge' => 'badge-green'],
                                'loyal customers'  => ['dot' => '#2563eb', 'badge' => 'badge-blue'],
                                'potential loyalists' => ['dot' => '#0d9488', 'badge' => 'badge-teal'],
                                'at risk'          => ['dot' => '#dc2626', 'badge' => 'badge-red'],
                                'needs attention'  => ['dot' => '#d97706', 'badge' => 'badge-amber'],
                                'about to sleep'   => ['dot' => '#9ca3af', 'badge' => 'badge-gray'],
                                'lost customers'   => ['dot' => '#b91c1c', 'badge' => 'badge-red'],
                                'new customers'    => ['dot' => '#4338ca', 'badge' => 'badge-indigo'],
                                'hibernating'      => ['dot' => '#6b7280', 'badge' => 'badge-gray'],
                                'promising'        => ['dot' => '#7c3aed', 'badge' => 'badge-purple'],
                            ];
                        @endphp
                        @foreach($rfmSegmentStats as $segment => $count)
                            @php
                                $key    = strtolower($segment);
                                $dot    = $segColors[$key]['dot']   ?? '#9ca3af';
                                $bClass = $segColors[$key]['badge'] ?? 'badge-gray';
                            @endphp
                            <div class="seg-row">
                                <div class="seg-left">
                                    <div class="seg-dot" style="background:{{ $dot }}"></div>
                                    {{ ucwords(str_replace('_', ' ', $segment)) }}
                                </div>
                                <span class="badge {{ $bClass }}">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-pie-chart"></i>
                        <p>Belum ada data segmentasi RFM</p>
                        <small>Jalankan kalkulasi RFM terlebih dahulu</small>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ===================== PRODUK & KATEGORI TERLARIS ===================== --}}
    <div class="grid-2b">

        <div class="dash-card">
            <div class="dash-card-header">
                <h4>Produk terlaris</h4>
                <a href="{{ route('admin.products') }}" class="hdr-right">Lihat semua</a>
            </div>
            <div class="dash-card-body p0">
                @if(isset($topProducts) && $topProducts->count() > 0)
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>SKU</th>
                                <th class="td-center">Terjual</th>
                                <th class="td-right">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td><code>{{ $product->sku }}</code></td>
                                <td class="td-center">
                                    <span class="badge badge-green">{{ number_format($product->total_sold) }}</span>
                                </td>
                                <td class="td-right">Rp {{ number_format($product->total_revenue, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <i class="bi bi-box-seam"></i>
                        <p>Belum ada data penjualan</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-header">
                <h4>Kategori terlaris</h4>
                <a href="{{ route('admin.categories') }}" class="hdr-right">Lihat semua</a>
            </div>
            <div class="dash-card-body p0">
                @if(isset($topCategories) && $topCategories->count() > 0)
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="td-center">Transaksi</th>
                                <th class="td-center">Terjual</th>
                                <th class="td-right">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topCategories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td class="td-center">{{ number_format($category->total_transactions) }}</td>
                                <td class="td-center">
                                    <span class="badge badge-teal">{{ number_format($category->total_sold) }}</span>
                                </td>
                                <td class="td-right">Rp {{ number_format($category->total_revenue, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <i class="bi bi-tags"></i>
                        <p>Belum ada data kategori</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ===================== TRANSAKSI TERBARU + TOP POIN ===================== --}}
    <div class="grid-3">

        <div class="dash-card">
            <div class="dash-card-header">
                <h4>Transaksi terbaru</h4>
                <a href="{{ route('admin.transactions') }}" class="hdr-right">Lihat semua</a>
            </div>
            <div class="dash-card-body p0">
                @if(isset($todayTransactions) && $todayTransactions->count() > 0)
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th class="td-right">Total</th>
                                <th>Status</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todayTransactions as $transaction)
                            @php
                                $statusBadge = match($transaction->status) {
                                    'completed' => 'badge-green',
                                    'pending'   => 'badge-amber',
                                    'cancelled' => 'badge-red',
                                    'refunded'  => 'badge-gray',
                                    default     => 'badge-gray',
                                };
                                $statusLabel = match($transaction->status) {
                                    'completed' => 'Completed',
                                    'pending'   => 'Pending',
                                    'cancelled' => 'Cancelled',
                                    'refunded'  => 'Refunded',
                                    default     => ucfirst($transaction->status),
                                };
                            @endphp
                            <tr>
                                <td><code>{{ $transaction->invoice_number }}</code></td>
                                <td>{{ $transaction->customer->name ?? 'Walk In' }}</td>
                                <td class="td-right">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                                <td class="text-muted" style="font-size:11px">
                                    {{ $transaction->transaction_date->format('H:i') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <i class="bi bi-cart-x"></i>
                        <p>Belum ada transaksi hari ini</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-header">
                <h4>Top poin pelanggan</h4>
                <span style="font-size:16px">🏆</span>
            </div>
            <div class="dash-card-body" style="padding:.5rem 1rem">
                @if(isset($topPointCustomers) && $topPointCustomers->count() > 0)
                    @php
                        $avatarColors = [
                            ['bg' => '#ede9fe', 'color' => '#6d28d9'],
                            ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                            ['bg' => '#dcfce7', 'color' => '#15803d'],
                            ['bg' => '#fce7f3', 'color' => '#db2777'],
                            ['bg' => '#fef3c7', 'color' => '#b45309'],
                        ];
                    @endphp
                    @foreach($topPointCustomers as $idx => $customer)
                    @php
                        $av     = $avatarColors[$idx % count($avatarColors)];
                        $initials = collect(explode(' ', $customer->name))
                            ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <div class="top-customer">
                        <div class="tc-left">
                            <div class="tc-avatar" style="background:{{ $av['bg'] }};color:{{ $av['color'] }}">
                                {{ $initials }}
                            </div>
                            <div>
                                <div class="tc-name">{{ $customer->name }}</div>
                                <div class="tc-phone">{{ $customer->phone ?? '-' }}</div>
                            </div>
                        </div>
                        <span class="badge badge-amber">⭐ {{ number_format($customer->total_points) }}</span>
                    </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <i class="bi bi-star"></i>
                        <p>Belum ada data poin pelanggan</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ===================== GRAFIK PENDAPATAN BULANAN ===================== --}}
    <div class="dash-card" style="margin-bottom:1.5rem">
        <div class="dash-card-header">
            <h4>Pendapatan bulanan {{ date('Y') }}</h4>
        </div>
        <div class="dash-card-body">
            <div class="chart-wrap" style="height:240px">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    @php
        $defaultLabels   = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $defaultSales    = [0, 0, 0, 0, 0, 0, 0];
        $defaultMonthLbl = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        $defaultMonthly  = [0,0,0,0,0,0,0,0,0,0,0,0];

        $salesLabels  = $salesChart['labels']      ?? $defaultLabels;
        $salesData    = $salesChart['sales']        ?? $defaultSales;
        $monthLabels  = $monthlyRevenue['labels']   ?? $defaultMonthLbl;
        $monthData    = $monthlyRevenue['revenues'] ?? $defaultMonthly;
    @endphp

    const salesLabels = @json($salesLabels);
    const salesData   = @json($salesData);
    const monthLabels = @json($monthLabels);
    const monthData   = @json($monthData);

    Chart.defaults.font.family = "'DM Sans', sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#9ca3af';

    /* ---- Grafik penjualan 7 hari ---- */
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'Pendapatan',
                    data: salesData,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: .4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a2e',
                        titleColor: '#e5e7eb',
                        bodyColor: '#c7d2fe',
                        padding: 10,
                        callbacks: {
                            label: ctx => 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw)
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        grid: { color: '#f4f4f8' },
                        border: { display: false, dash: [4,4] },
                        beginAtZero: true,
                        ticks: {
                            font: { size: 11 },
                            callback: v => 'Rp ' + Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v)
                        }
                    }
                }
            }
        });
    }

    /* ---- Grafik pendapatan bulanan ---- */
    const monthCtx = document.getElementById('monthlyRevenueChart');
    if (monthCtx) {
        new Chart(monthCtx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Pendapatan',
                    data: monthData,
                    backgroundColor: 'rgba(99,102,241,.15)',
                    borderColor: '#6366f1',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(99,102,241,.3)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a2e',
                        titleColor: '#e5e7eb',
                        bodyColor: '#c7d2fe',
                        padding: 10,
                        callbacks: {
                            label: ctx => 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw)
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        grid: { color: '#f4f4f8' },
                        border: { display: false },
                        beginAtZero: true,
                        ticks: {
                            font: { size: 11 },
                            callback: v => 'Rp ' + Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v)
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush