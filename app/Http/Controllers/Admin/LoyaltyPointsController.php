<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use Illuminate\Http\Request;

class LoyaltyPointsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Mengambil riwayat mutasi poin terbaru
        $logs = LoyaltyPoints::with('customer')->latest()->get();
        return view('pages.admin.loyalty.logs.index', compact('logs'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(LoyaltyPoints $loyaltyPoints)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LoyaltyPoints $loyaltyPoints)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LoyaltyPoints $loyaltyPoints)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LoyaltyPoints $loyaltyPoints)
    {
        //
    }
}
