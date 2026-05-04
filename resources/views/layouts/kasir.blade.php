<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kasir') — BrewCRM Café</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-orange: #E8531A;
            --sidebar-bg: #1e1e2d;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f5f7fb;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            transition: all 0.3s;
            z-index: 1000;
        }

        #sidebar.collapsed {
            left: calc(var(--sidebar-width) * -1);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }

        .nav-link i { margin-right: 12px; font-size: 1.1rem; }

        .nav-link:hover, .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }

        .nav-link.active {
            border-left: 4px solid var(--primary-orange);
            background: rgba(232, 83, 26, 0.15);
        }

        .sidebar-title {
            color: rgba(255,255,255,0.3);
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 20px 20px 10px;
            font-weight: 700;
        }

        /* Main Content Styling */
        #main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        #main-content.expanded {
            margin-left: 0;
        }

        .topbar {
            background: #fff;
            padding: 15px 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Clock & Info */
        .clock-display {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--primary-orange);
            line-height: 1;
        }
        .date-display {
            font-size: 0.7rem;
            color: #6c757d;
            text-transform: uppercase;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        .footer {
            margin-top: auto;
            background: #fff;
            padding: 15px 25px;
            border-top: 1px solid #eee;
        }

        @media (max-width: 991.98px) {
            #sidebar { left: calc(var(--sidebar-width) * -1); }
            #sidebar.active { left: 0; }
            #main-content { margin-left: 0; }
        }

        .hot-badge {
            background: var(--primary-orange);
            font-size: 0.6rem;
            padding: 2px 5px;
            border-radius: 4px;
            color: #fff;
            margin-left: auto;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="text-white text-decoration-none fs-4 fw-bold">☕ BrewCRM</a>
        </div>
        
        <div class="p-3">
            <div class="d-flex align-items-center p-2 bg-dark rounded-3 mb-3">
                <div class="flex-shrink-0">
                    <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 35px; height: 35px;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
                <div class="flex-grow-1 ms-3 overflow-hidden">
                    <h6 class="text-white mb-0 text-truncate" style="font-size: 0.85rem;">{{ auth()->user()->name }}</h6>
                    <small class="text-muted" style="font-size: 0.7rem;">Kasir On Duty</small>
                </div>
            </div>
        </div>

        <ul class="nav flex-column">
            <li class="sidebar-title">Menu</li>
            <a href="{{ route('kasir.dashboard') }}" class="nav-link {{ request()->routeIs('kasir.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <li class="sidebar-title">Transaksi</li>
            <a href="{{ route('kasir.pos.index') }}" class="nav-link {{ request()->routeIs('kasir.pos*') ? 'active' : '' }}">
                <i class="bi bi-calculator"></i> POS Kasir <span class="hot-badge">HOT</span>
            </a>
            <a href="{{ route('kasir.transactions.history') }}" class="nav-link {{ request()->routeIs('kasir.transactions*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Riwayat
            </a>

            <li class="sidebar-title">Pelanggan</li>
            <a href="{{ route('kasir.members.create') }}" class="nav-link {{ request()->routeIs('kasir.members.create') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i> Daftar Member
            </a>
            <a href="{{ route('kasir.members.check') }}" class="nav-link {{ request()->routeIs('kasir.members.check') ? 'active' : '' }}">
                <i class="bi bi-search"></i> Cek Member
            </a>

            <li class="sidebar-title">Sistem</li>
            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                @csrf
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </a>
            </form>
        </ul>
    </nav>

    <!-- Main Content -->
    <div id="main-content">
        <!-- Topbar -->
        <header class="topbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <button class="btn btn-light border me-3" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="d-none d-md-block ms-2">
                    <h5 class="mb-0 fw-bold">@yield('title')</h5>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <div class="text-end me-4 d-none d-sm-block">
                    <div class="clock-display" id="liveClock">00:00</div>
                    <div class="date-display" id="liveDate">Senin, 01 Jan</div>
                </div>
                
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" data-bs-toggle="dropdown">
                        <div class="user-avatar bg-secondary d-flex align-items-center justify-content-center text-white me-2">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="d-none d-md-inline">{{ explode(' ', auth()->user()->name)[0] }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="{{ route('kasir.profile.index') }}"><i class="bi bi-person me-2"></i> Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-left me-2"></i> Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4">
            <!-- Breadcrumb (Mobile Only) -->
            <nav class="d-md-none mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Kasir</a></li>
                    <li class="breadcrumb-item active">@yield('title')</li>
                </ol>
            </nav>

            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="container-fluid p-0">
                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start small text-muted">
                    2026 &copy; <strong>BrewCRM</strong>. All rights reserved.
                </div>
                <div class="col-md-6 text-center text-md-end d-none d-md-block small text-muted">
                    Crafted for ☕ Coffee Lovers
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@8.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');

        sidebarToggle.addEventListener('click', () => {
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            } else {
                sidebar.classList.toggle('active');
            }
        });

        // Realtime Clock
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', day: 'numeric', month: 'short' };
            
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', minute: '2-digit', hour12: false 
            });
            document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();

        // SweetAlert Handler
        @if(session('swal_success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session("swal_success") }}',
                timer: 2000,
                showConfirmButton: false
            });
        @endif
    </script>
    
    @stack('scripts')
</body>
</html>