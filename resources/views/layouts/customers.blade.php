<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Cafe Member Elite</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --bg-body: #f8fafc;
            --glass: rgba(255, 255, 255, 0.8);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            overflow-x: hidden;
        }

        /* --- NAVIGATION --- */
        .navbar {
            background: var(--glass) !important;
            backdrop-filter: blur(15px) saturate(180%);
            -webkit-backdrop-filter: blur(15px) saturate(180%);
            border-bottom: 1px solid rgba(231, 231, 231, 0.5);
            padding: 1.2rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        .nav-link {
            font-weight: 600;
            color: #64748b !important;
            padding: 0.5rem 1.2rem !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .nav-link:hover { color: var(--primary) !important; }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: 0.3s;
            transform: translateX(-50%);
        }

        .nav-link.active { color: var(--primary) !important; }
        .nav-link.active::after { width: 20px; }

        /* --- GLOBAL COMPONENTS --- */
        .custom-card {
            border: none;
            border-radius: 24px;
            background: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01);
        }

        .custom-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.02) !important;
        }

        .avatar-circle {
            width: 42px; height: 42px;
            object-fit: cover;
            border-radius: 14px;
            border: 2px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .dropdown-menu {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 10px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        footer { margin-top: 50px; padding-bottom: 30px; }
    </style>
    @stack('styles')
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand fs-3" href="{{ route('customers.dashboard') }}">COFFEE<span style="opacity: 0.4">HUB.</span></a>
            
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-grid-fill text-primary"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('customers.dashboard') ? 'active' : '' }}" href="{{ route('customers.dashboard') }}">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('customers.transactions') ? 'active' : '' }}" href="{{ route('customers.transactions') }}">Transaksi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('customers.promos') ? 'active' : '' }}" href="{{ route('customers.promos') }}">Promo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('customers.points.redeem') ? 'active' : '' }}" href="{{ route('customers.points.redeem') }}">Tukar Reward</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('customers.points.history') ? 'active' : '' }}" href="{{ route('customers.points.history') }}">Riwayat Poin</a>
                    </li>
                </ul>

                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" data-bs-toggle="dropdown">
                        <div class="text-end me-3 d-none d-sm-block">
                            <p class="fw-bold small mb-0 lh-1">{{ auth()->guard('customers')->user()->name }}</p>
                            <small class="text-muted" style="font-size: 11px">
                                {{ auth()->guard('customers')->user()->rfmScore->segment_name ?? 'New Member' }}
                            </small>
                        </div>
                        <img src="{{ auth()->guard('customers')->user()->profile_photo ? asset('storage/'.auth()->guard('customers')->user()->profile_photo) : 'https://ui-avatars.com/api/?background=6366f1&color=fff&name='.urlencode(auth()->guard('customers')->user()->name) }}" class="avatar-circle">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item py-2 rounded-3" href="{{ route('customers.profile') }}"><i class="bi bi-person me-2"></i> Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item py-2 rounded-3 text-danger fw-bold"><i class="bi bi-box-arrow-right me-2"></i> Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        @yield('content')
    </main>

    <footer class="text-center text-muted small">
        <p>&copy; {{ date('Y') }} CoffeeHub Digital. Made with ☕</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>