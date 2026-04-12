<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointRedemption;
use Illuminate\Http\Request;

class PointRedemptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $redemptions = PointRedemption::with(['customer', 'reward'])
                        ->latest()
                        ->get();
                        
        return view('pages.admin.loyalty.redeem.index', compact('redemptions'));
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
    public function show(PointRedemption $pointRedemption)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PointRedemption $pointRedemption)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PointRedemption $pointRedemption)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PointRedemption $pointRedemption)
    {
        //
    }
}
