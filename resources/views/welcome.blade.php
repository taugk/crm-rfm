<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
      /* Memastikan tampilan halus saat loading */
      body { visibility: hidden; }
      .antialiased { visibility: visible; }
    </style>
  </head>
  <body class="antialiased d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4">
        
        <div class="text-center mb-4">
          <a href="/" class="navbar-brand navbar-brand-autodark">
             <h1 class="text-primary" style="font-weight: 800;">LARAVEL <span class="text-muted">13</span></h1>
          </a>
        </div>

        <div class="card card-md shadow-sm">
          <div class="card-body text-center">
            <h2 class="card-title">Selamat Datang di Proyek Anda</h2>
            <p class="text-secondary mb-4">Aplikasi ini telah terintegrasi dengan <strong>Tabler UI</strong> dan siap dikembangkan secara offline.</p>
            
            <div class="hr-text">Akses Cepat</div>
            
            <div class="row g-2">
              @if (Route::has('login'))
                @auth
                  <div class="col-12">
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary w-100">
                      Masuk ke Dashboard
                    </a>
                  </div>
                @else
                  <div class="col-6">
                    <a href="{{ route('login') }}" class="btn btn-outline-primary w-100">Log in</a>
                  </div>
                  @if (Route::has('register'))
                    <div class="col-6">
                      <a href="{{ route('register') }}" class="btn btn-primary w-100">Register</a>
                    </div>
                  @endif
                @endauth
              @endif
            </div>
          </div>
          
          <div class="card-footer bg-light border-top">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">v{{ app()->version() }}</span>
                <a href="https://laravel.com/docs" class="small text-decoration-none">Dokumentasi</a>
            </div>
          </div>
        </div>

        <div class="text-center text-secondary mt-3">
          Build with ❤️ using Laravel 13 & Tabler
        </div>
      </div>
    </div>
  </body>
</html>