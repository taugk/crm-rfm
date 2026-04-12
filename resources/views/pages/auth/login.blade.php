@extends('layouts.auth')

@section('title', 'Login - Sistem Analisis')

@section('content')
    <div class="mb-5">
        <h2 class="fw-bold text-dark">Masuk ke Akun</h2>
        <p class="text-muted">Silakan masukkan kredensial Anda untuk melanjutkan ke dashboard.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('login.post') }}" method="POST">
        @csrf 

        {{-- Email --}}
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Alamat Email</label>
            <input type="email" name="email" 
                   class="form-control py-2 @error('email') is-invalid @enderror" 
                   placeholder="nama@email.com" value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label class="form-label fw-semibold text-secondary">Password</label>
                
            </div>
            <input type="password" name="password" 
                   class="form-control py-2 @error('password') is-invalid @enderror" 
                   placeholder="••••••••" required>
            @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Remember Me --}}
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
            <label class="form-check-label text-muted" for="rememberMe">
                Ingat saya di perangkat ini
            </label>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
            Masuk Sekarang
        </button>
    </form>
@endsection

@section('auth-right-content')
    <div class="p-5 d-flex flex-column justify-content-center align-items-center h-100 bg-gradient-primary text-white">
        <div class="text-center">
            <div class="mb-4">
                {{-- Anda bisa mengganti ini dengan ilustrasi SVG atau Logo Perusahaan --}}
                <i class="bi bi-graph-up-arrow" style="font-size: 4rem;"></i>
            </div>
            <h1 class="fw-bold mb-3">Customer Analytics</h1>
            <p class="lead opacity-75">Transformasi data pelanggan menjadi wawasan berharga dengan integrasi metode <strong>K-Means Clustering</strong> dan <strong>RFM Analysis</strong>.</p>
            <hr class="w-25 mx-auto opacity-50">
            <small class="opacity-50 mt-4 d-block">© {{ date('Y') }} - Management System</small>
        </div>
    </div>
@endsection