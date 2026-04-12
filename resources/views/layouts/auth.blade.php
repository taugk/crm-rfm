<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Mazer Admin Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    {{-- Memanggil assets dari folder public/assets --}}
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth.css') }}">

    <style>
        /* Custom: Memastikan background kanan terlihat penuh */
        #auth-right {
            background: linear-gradient(45deg, #435ebe, #324cdd);
            height: 100%;
        }
    </style>
</head>

<body>
    <div id="auth">
        <div class="row h-100">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    <div class="auth-logo">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('assets/images/logo/logo.png') }}" alt="Logo">
                        </a>
                    </div>
                    
                    {{-- Area Konten Form --}}
                    @yield('content')

                </div>
            </div>
            <div class="col-lg-7 d-none d-lg-block">
                {{-- Area Sebelah Kanan --}}
                <div id="auth-right">
                    @yield('auth-right-content')
                </div>
            </div>
        </div>
    </div>
</body>

</html>