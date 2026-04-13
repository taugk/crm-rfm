<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function index(){
        return view('pages.auth.login');
    }

    public function login(Request $request){

    $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $credentials = $request->only('email', 'password');

        if(Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            return match ($user->role) {
                'admin' => redirect()->route('admin.dashboard')->with('success', 'Welcome Admin!'),
                'manager' => redirect()->route('manager.dashboard')->with('success', 'Welcome Manager!'),
                'kasir' => redirect()->route('kasir.dashboard')->with('success', 'Welcome Kasir!'),
                default => redirect()->route('/dashboard'),
            };

            
        }
        return back()->with('error', 'Login failed, please check your credentials.');
    }

   // ===============================
    // VIEW
    // ===============================
    public function loginCustomer()
    {
        return view('pages.auth.login-customers');
    }

    public function registerCustomer()
    {
        return view('pages.auth.register-customers');
    }

    // ===============================
    // REGISTER
    // ===============================
    public function registerCustomerPost(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|unique:customers,phone',
            'email'    => 'nullable|email|unique:customers,email',
            'password' => 'required|min:6',
        ]);

        $customer = Customers::create([
            'name'          => $request->name,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'gender'        => $request->gender,
            'date_of_birth' => $request->birthdate,
            'password'      => bcrypt($request->password),
            'role'          => 'customer',
            'status'        => 'active',
            'total_points'  => 0,
        ]);

        Auth::guard('customers')->login($customer);

        return redirect()->route('customers.dashboard')
            ->with('success', 'Pendaftaran Berhasil!');
    }

    // ===============================
    // LOGIN (PASSWORD ONLY - AMAN)
    // ===============================
    public function loginCustomerPost(Request $request)
{
    $request->validate([
        'login'    => 'required',
        'password' => 'required'
    ]);

    $fieldType = filter_var($request->login, FILTER_VALIDATE_EMAIL)
        ? 'email'
        : 'phone';

    $user = Customers::where($fieldType, $request->login)->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Akun tidak ditemukan'
        ], 404);
    }

    if ($user->status !== 'active') {
        return response()->json([
            'success' => false,
            'message' => 'Akun diblokir atau tidak aktif'
        ], 403);
    }

    if ($user->role !== 'customer') {
        return response()->json([
            'success' => false,
            'message' => 'Akses ditolak'
        ], 403);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Password salah'
        ], 401);
    }

    Auth::guard('customers')->login($user);
    $request->session()->regenerate();

    return response()->json([
        'success' => true,
        'message' => 'Selamat datang kembali!',
        'redirect' => route('customers.dashboard') // ✅ FIX
    ]);
}

    // ===============================
    // OPTIONAL OTP (SUDAH DIAMANKAN)
    // ===============================
    public function sendOtp(Request $request)
    {
        $customer = Customers::where('phone', $request->login)
            ->orWhere('email', $request->login)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Akun belum terdaftar.'
            ], 404);
        }

        // ❗ CEK STATUS
        if ($customer->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Akun diblokir atau tidak aktif.'
            ], 403);
        }

        $otp = rand(111111, 999999);

        Cache::put('otp_customer_' . $customer->id, $otp, now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'message' => 'OTP terkirim!',
            'id'      => $customer->id
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $customer = Customers::find($request->customer_id);

        if (!$customer || $customer->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif atau diblokir.'
            ], 403);
        }

        $cachedOtp = Cache::get('otp_customer_' . $customer->id);

        if ($cachedOtp && $cachedOtp == $request->otp_code) {
            Cache::forget('otp_customer_' . $customer->id);

            Auth::guard('customers')->login($customer);

            return response()->json([
                'success' => true,
                'redirect' => route('customers.dashboard')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'OTP salah atau kadaluarsa.'
        ], 422);
    }

    public function logout(Request $request)
{
    // Cek apakah yang sedang login adalah Customer
    $isCustomer = Auth::guard('customers')->check();

    // Proses Logout (Urutan: Guard spesifik dulu, baru sesi umum)
    if ($isCustomer) {
        Auth::guard('customers')->logout();
    } else {
        Auth::logout(); // Default logout untuk Admin/Staff (Guard Web)
    }

    // Bersihkan Sesi
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // Tentukan Tujuan Redirect
    if ($isCustomer) {
        // Jika yang logout adalah customer, lempar ke /customer
        return redirect('/customer')->with('success', 'Logout berhasil');
    }

    // Jika Admin/Staff, lempar ke login utama (admin)
    return redirect()->route('login')->with('success', 'Logout berhasil');
}


}