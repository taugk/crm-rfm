<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction; // Pastikan Model Transaction sudah ada

class CustomersController extends Controller
{
    /**
     * Menampilkan Dashboard Pelanggan
     */
    public function index()
    {
        // 1. Ambil data customer yang sedang login menggunakan guard 'customer'
        $customer = Auth::guard('customers')->user();

        // 2. Ambil riwayat transaksi terakhir milik customer ini
        // Kita ambil 5 transaksi terbaru untuk ditampilkan di tabel dashboard
        $transactions = Transaction::where('customer_id', $customer->id)
                        ->latest()
                        ->take(5)
                        ->get();

        // 3. Kirim data ke view
        return view('pages.customers.index', compact('customer', 'transactions'));
    }

    /**
     * Menampilkan Profil Lengkap Pelanggan
     */
    public function profile()
    {
        $customer = Auth::guard('customers')->user();
        return view('pages.customers.profile', compact('customer'));
    }

    /**
     * Menampilkan Seluruh Riwayat Transaksi Pelanggan
     */
    public function transactions()
    {
        $customer = Auth::guard('customers')->user();
        
        // Menggunakan pagination agar halaman tidak berat jika transaksi sudah banyak
        $transactions = Transaction::where('customer_id', $customer->id)
                            ->latest()
                            ->paginate(10);

        return view('pages.customers.transactions', compact('transactions'));
    }

    public function redeem(){
        return view('pages.customers.redeem');
    }
}