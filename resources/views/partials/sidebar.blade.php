<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
        {{-- HEADER SIDEBAR --}}
        <div class="sidebar-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="logo">
                    <a href="{{ url('/dashboard') }}">
                        <img src="{{ asset('assets/images/logo/logo.png') }}" alt="Logo">
                    </a>
                </div>
                {{-- Tombol Tutup (X) - Muncul di mobile & bisa diaktifkan di desktop --}}
                <div class="sidebar-toggler x">
                    <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                </div>
            </div>
        </div>

        @php
            if (!function_exists('is_active')) {
                function is_active($route) {
                    return request()->routeIs($route) ? 'active' : '';
                }
            }
            if (!function_exists('is_open')) {
                function is_open($routes = []) {
                    foreach ($routes as $r) {
                        if (request()->routeIs($r)) return 'active';
                    }
                    return '';
                }
            }
            if (!function_exists('is_show')) {
                function is_show($routes = []) {
                    foreach ($routes as $r) {
                        if (request()->routeIs($r)) return 'display: block;';
                    }
                    return 'display: none;';
                }
            }
        @endphp

        <div class="sidebar-menu">
            <ul class="menu">
                @if(auth()->user()->role == 'admin')
                    <li class="sidebar-title">Menu Utama</li>

                    <li class="sidebar-item {{ is_active('admin.dashboard') }}">
                        <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    {{-- MASTER DATA --}}
                    <li class="sidebar-title">Master Data</li>
                    @php $masterRoutes = ['admin.users*', 'admin.products*', 'admin.categories*', 'admin.customers*']; @endphp
                    <li class="sidebar-item has-sub {{ is_open($masterRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-folder2-open"></i>
                            <span>Master Data</span>
                        </a>
                        <ul class="submenu {{ is_open($masterRoutes) }}" style="{{ is_show($masterRoutes) }}">
                            <li class="submenu-item {{ is_active('admin.users*') }}">
                                <a href="{{ route('admin.users') }}">Data User</a>
                            </li>
                            <li class="submenu-item {{ is_active('admin.products*') }}">
                                <a href="{{ route('admin.products') }}">Data Produk</a>
                            </li>
                            <li class="submenu-item {{ is_active('admin.categories*') }}">
                                <a href="{{ route('admin.categories') }}">Kategori Produk</a>
                            </li>
                            <li class="submenu-item {{ is_active('admin.customers*') }}">
                                <a href="{{ route('admin.customers') }}">Data Pelanggan</a>
                            </li>
                        </ul>
                    </li>

                    {{-- TRANSAKSI --}}
                    <li class="sidebar-title">Transaksi</li>
                    @php $trxRoutes = ['admin.transactions*']; @endphp
                    <li class="sidebar-item has-sub {{ is_open($trxRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>Manajemen Transaksi</span>
                        </a>
                        <ul class="submenu {{ is_open($trxRoutes) }}" style="{{ is_show($trxRoutes) }}">
                            <li class="submenu-item {{ is_active('admin.transactions.create') }}">
                                <a href="{{ route('admin.transactions.create') }}">Input Penjualan (POS)</a>
                            </li>
                            <li class="submenu-item {{ is_active('admin.transactions') }}">
                                <a href="{{ route('admin.transactions') }}">Riwayat Transaksi</a>
                            </li>
                        </ul>
                    </li>

                    {{-- ANALISIS & LAPORAN --}}
                    <li class="sidebar-title">Analisis & Laporan</li>
                    @php 
                        $analisisRoutes = ['rfm*', 'admin.reports.transactions*', 'admin.reports.products*']; 
                    @endphp
                    <li class="sidebar-item has-sub {{ is_open($analisisRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Analisis & Laporan</span>
                        </a>
                        <ul class="submenu {{ is_open($analisisRoutes) }}" style="{{ is_show($analisisRoutes) }}">
                            <li class="submenu-item {{ is_active('rfm*') }}">
                                <a href="{{ url('/rfm') }}">Analisis RFM</a>
                            </li>
                            <hr class="mx-3 my-2" style="opacity: 0.1; border-color: #fff;">
                            <li class="submenu-item {{ is_active('admin.reports.transactions*') }}">
                                <a href="{{ route('admin.reports.transactions') }}">Laporan Transaksi</a>
                            </li>
                            <li class="submenu-item {{ is_active('admin.reports.products*') }}">
                                <a href="{{ route('admin.reports.products') }}">Laporan Produk</a>
                            </li>
                        </ul>
                    </li>

                    {{-- PROMOSI --}}
                    @php $promoRoutes = ['admin.promo*']; @endphp
                    <li class="sidebar-item has-sub {{ is_open($promoRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-megaphone"></i>
                            <span>Manajemen Promosi</span>
                        </a>
                        <ul class="submenu {{ is_open($promoRoutes) }}" style="{{ is_show($promoRoutes) }}">
                            <li class="submenu-item {{ is_active('admin.promo') }}">
                                <a href="{{ route('admin.promo') }}">Data Promosi</a>
                            </li>
                            <li class="submenu-item {{ is_active('admin.promo.create') }}">
                                <a href="{{ route('admin.promo.create') }}">Tambah Promosi</a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- AKUN --}}
                <li class="sidebar-title">Akun</li>
                <li class="sidebar-item {{ is_active('profil*') }}">
                    <a href="{{ url('/profil') }}" class="sidebar-link">
                        <i class="bi bi-person-circle"></i>
                        <span>Profil Saya</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a href="#" class="sidebar-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>