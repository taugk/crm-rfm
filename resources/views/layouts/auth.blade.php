<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Customer Analytics System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">

    <style>
        body {
            font-family: 'Inter', sans-serif !important;
            background-color: #f8f9fa;
        }

        /* Re-branding ID dan Class agar tidak terdeteksi Mazer */
        #app-auth-wrapper {
            height: 100vh;
            overflow-x: hidden;
        }

        .auth-container-left {
            padding: 5rem 8%;
            background: #ffffff;
        }

        .brand-logo-container {
            margin-bottom: 3rem;
        }

        .brand-logo-container img {
            height: 45px; /* Sedikit modifikasi ukuran logo */
        }

        #auth-panel-visual {
            background: radial-gradient(circle at top left, #2c3e50, #000000);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Menghilangkan sisa-sisa style padding bawaan auth.css Mazer */
        #auth-left {
            padding: 0 !important;
        }
    </style>
</head>

<body>
    <div id="app-auth-wrapper">
        <div class="row h-100 g-0"> <div class="col-lg-5 col-12 auth-container-left d-flex flex-column justify-content-center">
                {{-- <div class="brand-logo-container text-center text-lg-start">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('assets/images/logo/logo.png') }}" alt="Project Logo">
                    </a>
                </div>
                 --}}
                <div class="auth-content-area">
                    @yield('content')
                </div>
            </div>

            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-panel-visual">
                    @yield('auth-right-content')
                </div>
            </div>

        </div>
    </div>
</body>

</html>