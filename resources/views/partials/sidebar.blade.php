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
                        
                        // Profile route berdasarkan role
                        $profileRoute = match($role) {
                            'admin' => 'admin.profile.index',
                            'manager' => 'manager.profile.index',
                            'kasir' => 'kasir.profile.index',
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
                
                {{-- SECTION 1: DASHBOARD (Semua Role) --}}
                <li class="sidebar-title">Menu Utama</li>
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

                {{-- SECTION 3: TRANSAKSI (Admin & Kasir) --}}
                @if(in_array($role, ['admin', 'kasir']))
                    <li class="sidebar-title">Transaksi</li>
                    @php 
                        $trxRoutes = $role == 'admin' 
                            ? ['admin.transactions*'] 
                            : ['kasir.create.transaction', 'kasir.transactions*']; 
                    @endphp
                    <li class="sidebar-item has-sub {{ is_open($trxRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>Manajemen Penjualan</span>
                        </a>
                        <ul class="submenu {{ is_open($trxRoutes) }}" style="{{ is_show($trxRoutes) }}">
                            @if($role == 'admin')
                                <li class="submenu-item {{ is_active('admin.transactions.create') }}">
                                    <a href="{{ route('admin.transactions.create') }}">Input Penjualan (POS)</a>
                                </li>
                                <li class="submenu-item {{ is_active('admin.transactions') }}">
                                    <a href="{{ route('admin.transactions') }}">Data Transaksi</a>
                                </li>
                            @elseif($role == 'kasir')
                                <li class="submenu-item {{ is_active('kasir.create.transaction') }}">
                                    <a href="{{ route('kasir.create.transaction') }}">Input Penjualan (POS)</a>
                                </li>
                                <li class="submenu-item {{ is_active('kasir.history.transactions') }}">
                                    <a href="{{ route('kasir.history.transactions') }}">Riwayat Transaksi</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- SECTION 4: STRATEGY & ANALISIS (Admin & Manager) --}}
                @if(in_array($role, ['admin', 'manager']))
                    <li class="sidebar-title">Marketing Strategy</li>

                    {{-- Analisis Pelanggan --}}
                    @php $analysisRoutes = ['rfm.*']; @endphp
                    <li class="sidebar-item has-sub {{ is_open($analysisRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-person-check"></i>
                            <span>Analisis Pelanggan</span>
                        </a>
                        <ul class="submenu {{ is_open($analysisRoutes) }}" style="{{ is_show($analysisRoutes) }}">
                            <li class="submenu-item {{ is_active('rfm.index*') }}">
                                <a href="{{ route('rfm.index') }}">Analisis RFM</a>
                            </li>
                            @if($role == 'admin')
                            <li class="submenu-item {{ is_active('rfm.calculate*') }}">
                                <a href="{{ route('rfm.calculate') }}">Kalkulasi RFM</a>
                            </li>
                            @endif
                        </ul>
                    </li>

                    {{-- Promosi & Loyalty --}}
                    @php 
                        $promoRoutes = $role == 'admin' 
                            ? ['admin.promo*', 'admin.loyalty.rule*', 'admin.loyalty.rewards*', 'admin.loyalty.redemptions*', 'admin.loyalty.points*']
                            : ['manager.loyalty.rules*', 'manager.loyalty.rewards*', 'manager.loyalty.redemptions*', 'manager.loyalty.points*']; 
                    @endphp
                    <li class="sidebar-item has-sub {{ is_open($promoRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-megaphone"></i>
                            <span>Promosi & Loyalty</span>
                        </a>
                        <ul class="submenu {{ is_open($promoRoutes) }}" style="{{ is_show($promoRoutes) }}">
                            @if($role == 'admin')
                                <li class="submenu-item {{ is_active('admin.promo*') }}"><a href="{{ route('admin.promo') }}">Kupon Promo</a></li>
                                <hr class="mx-3 my-2" style="opacity: 0.1; border-color: #fff;">
                                <li class="submenu-item {{ is_active('admin.loyalty.rule*') }}"><a href="{{ route('admin.loyalty.rule') }}">Aturan Poin</a></li>
                                <li class="submenu-item {{ is_active('admin.loyalty.rewards*') }}"><a href="{{ route('admin.loyalty.rewards') }}">Katalog Hadiah</a></li>
                                <li class="submenu-item {{ is_active('admin.loyalty.redemptions*') }}"><a href="{{ route('admin.loyalty.redemptions.index') }}">Riwayat Penukaran</a></li>
                                <li class="submenu-item {{ is_active('admin.loyalty.points*') }}"><a href="{{ route('admin.loyalty.points.index') }}">Log Mutasi Poin</a></li>
                            @elseif($role == 'manager')
                                <li class="submenu-item {{ is_active('manager.promo*') }}"><a href="{{ route('manager.promo') }}">Kupon Promo</a></li>
                                <hr class="mx-3 my-2" style="opacity: 0.1; border-color: #fff;">
                                <li class="submenu-item {{ is_active('manager.loyalty.rules*') }}"><a href="{{ route('manager.loyalty.rules') }}">Aturan Poin</a></li>
                                <li class="submenu-item {{ is_active('manager.loyalty.rewards*') }}"><a href="{{ route('manager.loyalty.rewards') }}">Katalog Hadiah</a></li>
                                <li class="submenu-item {{ is_active('manager.loyalty.redemptions*') }}"><a href="{{ route('manager.loyalty.redemptions.index') }}">Riwayat Penukaran</a></li>
                                <li class="submenu-item {{ is_active('manager.loyalty.points*') }}"><a href="{{ route('manager.loyalty.points.index') }}">Log Mutasi Poin</a></li>
                            @endif
                        </ul>
                    </li>

                    {{-- Laporan --}}
                    @php 
                        $reportRoutes = $role == 'admin' 
                            ? ['admin.reports.transactions*', 'admin.product.reports*']
                            : ['manager.reports.transactions*', 'manager.product.reports*']; 
                    @endphp
                    <li class="sidebar-item has-sub {{ is_open($reportRoutes) }}">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Laporan Penjualan</span>
                        </a>
                        <ul class="submenu {{ is_open($reportRoutes) }}" style="{{ is_show($reportRoutes) }}">
                            @if($role == 'admin')
                                <li class="submenu-item {{ is_active('admin.reports.transactions*') }}">
                                    <a href="{{ route('admin.reports.transactions') }}">Rekap Transaksi</a>
                                </li>
                                <li class="submenu-item {{ is_active('admin.product.reports*') }}">
                                    <a href="{{ route('admin.product.reports') }}">Produk Terlaris</a>
                                </li>
                            @elseif($role == 'manager')
                                <li class="submenu-item {{ is_active('manager.reports.transactions*') }}">
                                    <a href="{{ route('manager.reports.transactions') }}">Rekap Transaksi</a>
                                </li>
                                <li class="submenu-item {{ is_active('manager.product.reports*') }}">
                                    <a href="{{ route('manager.product.reports') }}">Produk Terlaris</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- SECTION 5: MANAJEMEN USER (Hanya Admin) --}}
                @if($role == 'admin')
                    <li class="sidebar-title">Manajemen User</li>
                    <li class="sidebar-item {{ is_active('admin.users*') }}">
                        <a href="{{ route('admin.users') }}" class="sidebar-link">
                            <i class="bi bi-people"></i>
                            <span>Kelola User</span>
                        </a>
                    </li>
                @endif

                {{-- SECTION 6: PENGATURAN (Semua Role) --}}
                <li class="sidebar-title">Pengaturan</li>
                <li class="sidebar-item {{ is_active($profileRoute) }}">
                    <a href="{{ route($profileRoute) }}" class="sidebar-link">
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