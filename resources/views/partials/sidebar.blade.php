<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
        {{-- HEADER SIDEBAR --}}
        <div class="sidebar-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="logo">
                    @php
                        $role = auth()->user()->role ?? null;

                        $dashboardRoute = match($role) {
                            'admin' => 'admin.dashboard',
                            'manager' => 'manager.dashboard',
                            'kasir' => 'kasir.dashboard',
                            default => 'login'
                        };
                    @endphp

                    <a href="{{ route($dashboardRoute) }}">
                        <h4 class="fw-bold">CRM <span class="text-primary">SYSTEM</span></h4>
                    </a>
                </div>
                <div class="sidebar-toggler x">
                    <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                </div>
            </div>
        </div>

        @php
            $role = auth()->user()->role;
            
            // Helpers (Cek Route Aktif)
            if (!function_exists('is_active')) {
                function is_active($route) { return request()->routeIs($route) ? 'active' : ''; }
            }
            if (!function_exists('is_open')) {
                function is_open($routes = []) {
                    foreach ($routes as $r) { if (request()->routeIs($r)) return 'active'; }
                    return '';
                }
            }
            if (!function_exists('is_show')) {
                function is_show($routes = []) {
                    foreach ($routes as $r) { if (request()->routeIs($r)) return 'display: block;'; }
                    return 'display: none;';
                }
            }
        @endphp

        <div class="sidebar-menu">
            <ul class="menu">
                
                {{-- SECTION 1: DASHBOARD (Semua Role) --}}
                <li class="sidebar-title">Menu Utama</li>
                @php
                    $dashboardRoute = match($role) {
                        'admin' => 'admin.dashboard',
                        'manager' => 'manager.dashboard',
                        'kasir' => 'kasir.dashboard',
                        default => 'login'
                    };
                @endphp

                <li class="sidebar-item {{ is_active($dashboardRoute) }}">
                    <a href="{{ route($dashboardRoute) }}" class="sidebar-link">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                {{-- SECTION 2: MASTER DATA (Hanya Admin) --}}
                @if($role == 'admin')
                    <li class="sidebar-title">Manajemen Data</li>
                    @php $masterRoutes = ['admin.users*', 'admin.products*', 'admin.categories*', 'admin.customers*']; @endphp
                    <li class="sidebar-item has-sub {{ is_open($masterRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-folder2-open"></i>
                            <span>Master Data</span>
                        </a>
                        <ul class="submenu {{ is_open($masterRoutes) }}" style="{{ is_show($masterRoutes) }}">
                            <li class="submenu-item {{ is_active('admin.users*') }}"><a href="{{ route('admin.users') }}">Data User</a></li>
                            <li class="submenu-item {{ is_active('admin.products*') }}"><a href="{{ route('admin.products') }}">Data Produk</a></li>
                            <li class="submenu-item {{ is_active('admin.categories*') }}"><a href="{{ route('admin.categories') }}">Kategori Produk</a></li>
                            <li class="submenu-item {{ is_active('admin.customers*') }}"><a href="{{ route('admin.customers') }}">Data Pelanggan</a></li>
                        </ul>
                    </li>
                @endif

                {{-- SECTION 3: TRANSAKSI (Semua Role: Admin, Manager, Kasir) --}}
                <li class="sidebar-title">Transaksi</li>
                @php $trxRoutes = ['admin.transactions*']; @endphp
                <li class="sidebar-item has-sub {{ is_open($trxRoutes) }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-receipt-cutoff"></i>
                        <span>Manajemen Penjualan</span>
                    </a>
                    <ul class="submenu {{ is_open($trxRoutes) }}" style="{{ is_show($trxRoutes) }}">
                        <li class="submenu-item {{ is_active('admin.transactions.create') }}">
                            <a href="{{ route('admin.transactions.create') }}">Input Penjualan (POS)</a>
                        </li>
                        <li class="submenu-item {{ is_active('admin.transactions') }}">
                            <a href="{{ route('admin.transactions') }}">Data Transaksi</a>
                        </li>
                    </ul>
                </li>

                {{-- SECTION 4: STRATEGY & ANALISIS (Hanya Admin & Manager) --}}
                @if(in_array($role, ['admin', 'manager']))
                    <li class="sidebar-title">Marketing Strategy</li>

                    {{-- Promosi & Loyalty --}}
                    @php 
                        $promoRoutes = ['admin.promo*', 'admin.loyalty.rule*', 'admin.loyalty.rewards*', 'admin.loyalty.redemptions*', 'admin.loyalty.points*']; 
                    @endphp
                    <li class="sidebar-item has-sub {{ is_open($promoRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-megaphone"></i>
                            <span>Promosi & Loyalty</span>
                        </a>
                        <ul class="submenu {{ is_open($promoRoutes) }}" style="{{ is_show($promoRoutes) }}">
                            <li class="submenu-item {{ is_active('admin.promo*') }}"><a href="{{ route('admin.promo') }}">Data Kupon Promo</a></li>
                            
                            <hr class="mx-3 my-2" style="opacity: 0.1; border-color: #fff;">
                            
                            <li class="submenu-item {{ is_active('admin.loyalty.rule*') }}"><a href="{{route('admin.loyalty.rule')}}">Aturan Poin</a></li>
                            <li class="submenu-item {{ is_active('admin.loyalty.rewards*') }}"><a href="{{ route('admin.loyalty.rewards') }}">Katalog Hadiah</a></li>
                            <li class="submenu-item {{ is_active('admin.loyalty.redemptions*') }}"><a href="{{ route('admin.loyalty.redemptions.index') }}">Riwayat Penukaran</a></li>
                            
                            {{-- LOG MUTASI POIN TETAP DI SINI --}}
                            <li class="submenu-item {{ is_active('admin.loyalty.points*') }}"><a href="{{route('admin.loyalty.points.index')}}">Log Mutasi Poin</a></li>
                        </ul>
                    </li>

                    {{-- Analisis & Laporan --}}
                    @php $analisisRoutes = ['rfm.index*', 'admin.reports.transactions*', 'admin.reports.products*']; @endphp
                    <li class="sidebar-item has-sub {{ is_open($analisisRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Analisis & Laporan</span>
                        </a>
                        <ul class="submenu {{ is_open($analisisRoutes) }}" style="{{ is_show($analisisRoutes) }}">
                            <li class="submenu-item {{ is_active('rfm.index*') }}"><a href="{{ route('rfm.index') }}">Analisis RFM</a></li>
                            <hr class="mx-3 my-2" style="opacity: 0.1; border-color: #fff;">
                            <li class="submenu-item {{ is_active('admin.reports.transactions*') }}"><a href="{{ route('admin.reports.transactions') }}">Laporan Penjualan</a></li>
                            <li class="submenu-item {{ is_active('admin.product.reports*') }}"><a href="{{ route('admin.product.reports') }}">Laporan Produk Terlaris</a></li>
                        </ul>
                    </li>
                @endif

                {{-- SECTION 5: PENGATURAN (Semua Role) --}}
                <li class="sidebar-title">Pengaturan</li>
                <li class="sidebar-item {{ is_active('admin.profile*') }}">
                    <a href="{{ route('admin.profile') }}" class="sidebar-link">
                        <i class="bi bi-person-circle"></i>
                        <span>Profil Saya</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                    <a href="#" class="sidebar-link text-danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right text-danger"></i>
                        <span>Keluar</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>