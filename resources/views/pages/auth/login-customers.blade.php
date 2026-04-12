<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Member - Alunea Cafe</title>

    <!-- ✅ CSRF -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f7fe;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 28px;
            box-shadow: 0 15px 35px rgba(67, 94, 190, 0.1);
            overflow: hidden;
            background: #fff;
        }
        .login-header {
            background: linear-gradient(135deg, #435ebe 0%, #6c5ce7 100%);
            padding: 45px 20px;
            color: white;
            text-align: center;
        }
        .nav-pills {
            background: #f8f9fa;
            padding: 5px;
            border-radius: 14px;
        }
        .nav-pills .nav-link {
            border-radius: 10px;
            color: #6c757d;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .nav-pills .nav-link.active {
            background-color: #435ebe;
            color: white;
        }
        .form-control {
            padding: 12px 16px;
            border-radius: 12px;
        }
        .btn-primary {
            background-color: #435ebe;
            border: none;
            padding: 13px;
            border-radius: 12px;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card login-card">
                <div class="login-header text-center">
                    <i class="bi bi-cup-hot-fill fs-1"></i>
                    <h3 class="fw-bold mt-2 mb-1">Alunea Cafe</h3>
                    <p class="mb-0 opacity-75 small">Portal Member</p>
                </div>
                
                <div class="card-body p-4">

                    <ul class="nav nav-pills nav-justified mb-4">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pwd">
                                Password
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#otp">
                                OTP
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- PASSWORD -->
                        <div class="tab-pane fade show active" id="pwd">
                            <form>
                                <input type="text" id="id_pwd" class="form-control mb-3" placeholder="Email / No HP" required>
                                <input type="password" id="password" class="form-control mb-3" placeholder="Password" required>

                                <!-- ✅ BUTTON FIX -->
                                <button type="button" onclick="handlePasswordLogin()" class="btn btn-primary w-100" id="btnPwd">
                                    Masuk
                                </button>
                            </form>
                        </div>

                        <!-- OTP -->
                        <div class="tab-pane fade" id="otp">
                            <div id="otpInputStep">
                                <input type="text" id="id_otp" class="form-control mb-3" placeholder="Email / No HP">
                                <button type="button" onclick="handleSendOTP()" class="btn btn-primary w-100" id="btnSend">
                                    Kirim OTP
                                </button>
                            </div>

                            <div id="otpVerifyStep" style="display:none;">
                                <input type="number" id="otpValue" class="form-control mb-3 text-center" placeholder="Kode OTP">
                                <button type="button" onclick="handleVerifyOTP()" class="btn btn-primary w-100" id="btnVerify">
                                    Verifikasi
                                </button>
                            </div>
                        </div>

                    </div>

                    <p class="text-center mt-3">
                        Belum punya akun?
                        <a href="{{ route('register.customers') }}">Daftar</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ================= PASSWORD LOGIN =================
function handlePasswordLogin() {
    const btn = document.getElementById('btnPwd');

    btn.disabled = true;
    btn.innerHTML = 'Memverifikasi...';

    fetch("{{ route('login.customers.post') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken
        },
        body: JSON.stringify({
            login: document.getElementById('id_pwd').value,
            password: document.getElementById('password').value
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = res.redirect;
            });
        } else {
            Swal.fire('Gagal', res.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'Masuk';
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Terjadi kesalahan', 'error');
        btn.disabled = false;
        btn.innerHTML = 'Masuk';
    });
}

// ================= OTP =================
let customerId = null;

function handleSendOTP() {
    fetch("{{ route('otp.send') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken
        },
        body: JSON.stringify({
            login: document.getElementById('id_otp').value
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            customerId = res.id;
            document.getElementById('otpInputStep').style.display = 'none';
            document.getElementById('otpVerifyStep').style.display = 'block';

            Swal.fire('OTP Terkirim', res.message, 'success');
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });
}

function handleVerifyOTP() {
    fetch("{{ route('otp.verify') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken
        },
        body: JSON.stringify({
            customer_id: customerId,
            otp_code: document.getElementById('otpValue').value
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Login Berhasil',
                timer: 1200,
                showConfirmButton: false
            }).then(() => {
                window.location.href = res.redirect;
            });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });
}
</script>

</body>
</html>