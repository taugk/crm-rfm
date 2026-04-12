@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <h1 class="auth-title">Log in.</h1>
    <p class="auth-subtitle mb-5">Log in with your data that you entered during registration.</p>

    {{-- Pesan Status (misal setelah reset password) --}}
    @if (session('status'))
        <div class="alert alert-success mb-4" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('login.post') }}" method="POST">
        @csrf {{-- Token keamanan wajib Laravel --}}

        {{-- Email / Username --}}
        <div class="form-group position-relative has-icon-left mb-4">
            <input type="email" name="email" 
                   class="form-control form-control-xl @error('email') is-invalid @enderror" 
                   placeholder="Email" value="{{ old('email') }}" required autofocus>
            <div class="form-control-icon">
                <i class="bi bi-person"></i>
            </div>
            @error('email')
                <div class="invalid-feedback">
                    <i class="bx bx-radio-circle"></i>
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="form-group position-relative has-icon-left mb-4">
            <input type="password" name="password" 
                   class="form-control form-control-xl @error('password') is-invalid @enderror" 
                   placeholder="Password" required>
            <div class="form-control-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Remember Me --}}
        <div class="form-check form-check-lg d-flex align-items-end">
            <input class="form-check-input me-2" type="checkbox" name="remember" id="flexCheckDefault">
            <label class="form-check-label text-gray-600" for="flexCheckDefault">
                Keep me logged in
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-5">Log in</button>
    </form>

    <div class="text-center mt-5 text-lg fs-4">
        <p class="text-gray-600">Don't have an account? 
            <a href="#" class="font-bold">Sign up</a>.
        </p>
        <p>
            <a class="font-bold" href="#">Forgot password?</a>.
        </p>
    </div>
@endsection

@section('auth-right-content')
    <div class="p-5 d-flex justify-content-center align-items-center h-100">
        <div class="text-center text-white">
            <h2 class="text-white fw-bold">Selamat Datang</h2>
            <p class="opacity-75">Sistem Analisis Pelanggan menggunakan Algoritma K-Means & RFM.</p>
        </div>
    </div>
@endsection