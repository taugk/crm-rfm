<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Member - Alunea Cafe</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f7fe;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        .register-card {
            border: none;
            border-radius: 28px;
            box-shadow: 0 15px 35px rgba(67, 94, 190, 0.1);
            background: #fff;
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #435ebe 0%, #6c5ce7 100%);
            padding: 35px 20px;
            color: white;
            text-align: center;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #4b4b4b;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #dce7f1;
            background-color: #fcfdfe;
            font-size: 0.95rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(67, 94, 190, 0.1);
            border-color: #435ebe;
        }
        .btn-primary {
            background-color: #435ebe;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #364b9a;
            transform: translateY(-1px);
        }
        .divider {
            height: 1px;
            background: #eee;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card register-card">
                <div class="register-header">
                    <i class="bi bi-person-plus-fill fs-1"></i>
                    <h3 class="fw-bold mt-2 mb-1">Gabung Member</h3>
                    <p class="mb-0 opacity-75 small">Nikmati promo eksklusif & kumpulkan poin!</p>
                </div>
                
                <div class="card-body p-4 p-sm-5">
                    <form action="{{ route('register.customers.post') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                       placeholder="Melvin Alvian" value="{{ old('name') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. WhatsApp</label>
                                <input type="number" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       placeholder="0895xxxx" value="{{ old('phone') }}" required>
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email (Opsional)</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                       placeholder="name@example.com" value="{{ old('email') }}">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="male">Laki-laki</option>
                                    <option value="female">Perempuan</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="birthdate" class="form-control">
                            </div>

                            <div class="divider"></div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Buat Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control border-end-0" placeholder="Min. 6 Karakter" required>
                                    <span class="input-group-text bg-white border-start-0" onclick="togglePassword()" style="cursor: pointer;">
                                        <i class="bi bi-eye text-muted" id="eyeIcon"></i>
                                    </span>
                                </div>
                                @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label small text-muted" for="terms">
                                Saya setuju dengan syarat dan ketentuan member Alunea Cafe.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 shadow-sm">
                            Daftar Sekarang
                        </button>
                    </form>
                </div>
            </div>
            
            <p class="text-center mt-4 text-muted small">
                Sudah punya akun? <a href="{{ route('login.customers') }}" class="text-primary fw-bold text-decoration-none">Masuk di sini</a>
            </p>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }
</script>

</body>
</html>