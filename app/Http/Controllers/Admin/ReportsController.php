<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ReportsController extends Controller
{
    // Di ReportController.php
    public function index(Request $request)
    {
        $start_date = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end_date = $request->end_date ?? now()->toDateString();

        $transactions = Transaction::with(['customer'])
            ->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
            ->where('status', 'completed')
            ->orderBy('transaction_date', 'desc')
            ->get();

        $total_revenue = $transactions->sum('total_price');
        $total_tax = $transactions->sum('tax_total');
        $total_discount = $transactions->sum('discount_amount');

        return view('pages.admin.reports.index', compact('transactions', 'total_revenue', 'total_tax', 'total_discount'));
    }

public function show($id)
    {
        // Mengambil transaksi beserta detail produk dan nama produknya
        $transaction = Transaction::with([
            'customer', 
            'details.product_detail.product'
        ])->findOrFail($id);

        return view('pages.admin.reports.show', compact('transaction'));
    }
}