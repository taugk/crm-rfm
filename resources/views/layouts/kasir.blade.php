<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kasir') — Alunea Café</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --accent-color: #10b981;
            --accent-hover: #059669;
            --sidebar-bg: #0f172a;
            --sidebar-width: 280px;
            --body-bg: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--body-bg);
            color: #1e293b;
            overflow-x: hidden;
        }

        /* --- SIDEBAR STYLING --- */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }

        #sidebar.collapsed { left: calc(var(--sidebar-width) * -1); }

        .sidebar-header { padding: 25px 20px; flex-shrink: 0; }

        .brand-logo {
            background: linear-gradient(135deg, var(--accent-color), #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        /* Scrollable Area */
        .sidebar-content {
            flex-grow: 1;
            overflow-y: auto;
            padding-bottom: 20px;
        }

        /* Hide scrollbar for Chrome/Safari */
        .sidebar-content::-webkit-scrollbar { width: 4px; }
        .sidebar-content::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        .nav-link {
            color: #94a3b8;
            padding: 12px 20px;
            margin: 4px 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: 0.2s;
            text-decoration: none;
            border: none;
            background: transparent;
            width: auto;
        }

        .nav-link i:first-child { margin-right: 14px; font-size: 1.2rem; }
        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .nav-link.active {
            color: #fff;
            background: var(--accent-color);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        /* --- SUBMENU STYLING --- */
        .nav-sub { list-style: none; padding: 0; margin: 0 0 0 45px; }
        .nav-sub .nav-link {
            padding: 8px 15px;
            margin: 2px 15px 2px 0;
            font-size: 0.85rem;
            color: #64748b;
        }
        .nav-sub .nav-link.active { background: rgba(16, 185, 129, 0.1); color: var(--accent-color); box-shadow: none; }
        .nav-sub .nav-link i { margin-right: 10px; font-size: 0.9rem; }

        .arrow-icon { transition: transform 0.3s; margin-left: auto; font-size: 0.8rem; }
        .nav-link[aria-expanded="true"] .arrow-icon { transform: rotate(180deg); }

        .sidebar-title {
            color: #475569;
            font-size: 0.7rem;
            text-transform: uppercase;
            padding: 20px 30px 10px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* --- MAIN CONTENT --- */
        #main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        #main-content.expanded { margin-left: 0; }

        .topbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            padding: 12px 30px;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .btn-toggle {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid #e2e8f0; background: #fff; color: #64748b;
        }

        .clock-display { font-weight: 700; font-size: 1.2rem; color: #1e293b; }
        .date-display { font-size: 0.75rem; color: #64748b; font-weight: 500; }

        .hot-badge {
            background: #ef4444; font-size: 0.65rem; padding: 2px 8px;
            border-radius: 20px; color: #fff; margin-left: 8px; font-weight: 700;
        }

        .user-profile-section {
            background: rgba(255,255,255,0.03);
            border-radius: 16px; margin: 10px 15px 20px; padding: 15px;
            border: 1px solid rgba(255,255,255,0.05);
            flex-shrink: 0;
        }

        @media (max-width: 991.98px) {
            #sidebar { left: calc(var(--sidebar-width) * -1); }
            #sidebar.active { left: 0; }
            #main-content { margin-left: 0; }
        }

        .card { border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-radius: 16px; }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="text-decoration-none fs-3 brand-logo">Alunea Cafe</a>
        </div>
        
        <div class="user-profile-section d-flex align-items-center">
            <div class="flex-shrink-0">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                     style="width: 42px; height: 42px; background: linear-gradient(45deg, #10b981, #3b82f6);">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
            <div class="flex-grow-1 ms-3 overflow-hidden">
                <h6 class="text-white mb-0 text-truncate" style="font-size: 0.85rem;">{{ auth()->user()->name }}</h6>
                <small style="font-size: 0.7rem; color: #94a3b8;"><i class="bi bi-circle-fill text-success me-1" style="font-size: 0.5rem;"></i> Kasir Active</small>
            </div>
        </div>

        <div class="sidebar-content">
            <ul class="nav flex-column">
                <li class="sidebar-title">Utama</li>
                <a href="{{ route('kasir.dashboard') }}" class="nav-link {{ request()->routeIs('kasir.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i> Dashboard
                </a>

                <li class="sidebar-title">Operasional</li>
                <a href="{{ route('kasir.pos.index') }}" class="nav-link {{ request()->routeIs('kasir.pos*') ? 'active' : '' }}">
                    <i class="bi bi-shop-window"></i> POS Kasir 
                </a>
                <a href="{{ route('kasir.transactions.history') }}" class="nav-link {{ request()->routeIs('kasir.transactions*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i> Riwayat Transaksi
                </a>

                <li class="sidebar-title">CRM</li>
                <a href="{{ route('kasir.members.create') }}" class="nav-link {{ request()->routeIs('kasir.members.create') ? 'active' : '' }}">
                    <i class="bi bi-person-plus"></i> Member Baru
                </a>
                <a href="{{ route('kasir.members.check') }}" class="nav-link {{ request()->routeIs('kasir.members.check') ? 'active' : '' }}">
                    <i class="bi bi-shield-check"></i> Status Member
                </a>

                <!-- ==================== MENU PENUKARAN POIN DENGAN SUB MENU LENGKAP ==================== -->
                <li class="sidebar-title">Loyalitas</li>
                @php
                    $pendingCount = \App\Models\PointRedemption::where('status', 'pending')->count();
                    $isLoyaltyActive = request()->routeIs('kasir.point-rewards*');
                @endphp
                
                <a class="nav-link {{ $isLoyaltyActive ? 'active' : '' }}" 
                   data-bs-toggle="collapse" href="#loyaltySubmenu" role="button" 
                   aria-expanded="{{ $isLoyaltyActive ? 'true' : 'false' }}">
                    <i class="bi bi-gift"></i> 
                    <span>Penukaran Poin</span>
                    @if($pendingCount > 0)
                        <span class="hot-badge">{{ $pendingCount }}</span>
                    @endif
                    <i class="bi bi-chevron-down arrow-icon"></i>
                </a>

                <div class="collapse {{ $isLoyaltyActive ? 'show' : '' }}" id="loyaltySubmenu">
                    <ul class="nav-sub">
                        <!-- Sub Menu: Tukar Poin -->
                        <li>
                            <a href="{{ route('kasir.point-rewards.index') }}" class="nav-link {{ request()->routeIs('kasir.point-rewards.index') ? 'active' : '' }}">
                                <i class="bi bi-gift-fill"></i> Tukar Poin
                            </a>
                        </li>
                        
                        <!-- Sub Menu: Pending Request (Konfirmasi) -->
                        <li>
                            <a href="{{ route('kasir.point-rewards.pending') }}" class="nav-link {{ request()->routeIs('kasir.point-rewards.pending') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i> Konfirmasi Penukaran
                                @if($pendingCount > 0)
                                    <span class="hot-badge">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        </li>
                        
                        <!-- Sub Menu: Riwayat Penukaran -->
                        <li>
                            <a href="{{ route('kasir.point-rewards.redeem-history') }}" class="nav-link {{ request()->routeIs('kasir.point-rewards.redeem-history') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i> Riwayat Penukaran
                            </a>
                        </li>
                        
                        <!-- Sub Menu: Daftar Hadiah -->
                        <li>
                            <a href="{{ route('kasir.point-rewards.rewards') }}" class="nav-link {{ request()->routeIs('kasir.point-rewards.rewards') ? 'active' : '' }}">
                                <i class="bi bi-trophy"></i> Daftar Hadiah
                            </a>
                        </li>
                    </ul>
                </div>

                <li class="sidebar-title">Akun</li>
                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                    @csrf
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="nav-link text-danger">
                        <i class="bi bi-power"></i> Keluar
                    </a>
                </form>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div id="main-content">
        <header class="topbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <button class="btn btn-toggle me-3" id="sidebarToggle">
                    <i class="bi bi-text-indent-left"></i>
                </button>
                <h5 class="mb-0 fw-bold d-none d-md-block">@yield('title')</h5>
            </div>

            <div class="d-flex align-items-center">
                <div class="text-end me-4 d-none d-sm-block">
                    <div class="clock-display" id="liveClock">00:00</div>
                    <div class="date-display" id="liveDate">Selasa, 05 Mei</div>
                </div>
                
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center fw-bold me-2" style="width: 38px; height: 38px;">
                             {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="d-none d-md-inline fw-semibold">{{ explode(' ', auth()->user()->name)[0] }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-3">
                        <li><a class="dropdown-item py-2" href="{{ route('kasir.profile.index') }}"><i class="bi bi-person-circle me-2"></i> Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item py-2 text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i> Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="p-4">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
                    <i class="bi bi-check2-circle fs-4 me-3"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="container-fluid p-0">
                @yield('content')
            </div>
        </main>

        <footer class="mt-auto py-4 px-4 border-top bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
                <div>2026 &copy; <span class="fw-bold text-dark">Alunea Café</span></div>
                <div>Sistem Kasir Pintar</div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleIcon = sidebarToggle.querySelector('i');

        sidebarToggle.addEventListener('click', () => {
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            } else {
                sidebar.classList.toggle('active');
            }
        });

        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', day: 'numeric', month: 'long' };
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
            document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();

        @if(session('swal_success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session("swal_success") }}',
                background: '#fff',
                confirmButtonColor: '#10b981',
                borderRadius: '16px'
            });
        @endif
    </script>
    @stack('scripts')
</body>
</html>