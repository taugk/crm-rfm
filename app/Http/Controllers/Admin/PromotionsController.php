<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PromotionsController extends Controller
{
    /**
     * Menampilkan daftar semua promosi.
     * Menggunakan latest() agar promo terbaru muncul di atas.
     */
    public function index()
    {
        $promos = Promotions::latest()->get();
        return view('pages.admin.promo.index', compact('promos'));
    }

    /**
     * Menampilkan form untuk membuat promosi baru.
     */
    public function create()
    {
        return view('pages.admin.promo.create');
    }

    /**
     * Menyimpan promosi baru ke database.
     */
    public function store(Request $request)
    {
        Log::debug('Promotions Store: Memulai proses simpan', ['payload' => $request->all()]);

        $request->validate([
            'promo_name'     => 'required|string|max:255',
            'promo_code'     => 'nullable|string|unique:promotions,promo_code|max:50',
            'discount_type'  => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'min_spend'      => 'nullable|numeric|min:0',
            'usage_limit'    => 'nullable|integer|min:1',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'description'    => 'nullable|string',
            'target_segment' => 'nullable|in:all,Needs Attention,Champions,Potential Loyalists,At Risk,Loyal Customers',
        ]);

        try {
            $data = $request->all();
            
            // Konversi Checkbox (Switch) ke Boolean
            $data['is_active'] = $request->has('is_active');
            
            // Konversi input datetime-local ke format Carbon (WIB 24 Jam)
            $data['start_date'] = Carbon::parse($request->start_date);
            $data['end_date'] = Carbon::parse($request->end_date);
            
            // Default nilai jika kosong
            $data['min_spend'] = $request->min_spend ?? 0;
            $data['used_count'] = 0; // Data baru selalu mulai dari 0

            Log::info('Promotions Store: Data diproses', ['data' => $data]);

            Promotions::create($data);

            return redirect()->route('admin.promo')
                ->with('success', 'Promosi berhasil diterbitkan!');

        } catch (\Exception $e) {
            Log::error('Promotions Store: Gagal simpan', ['error' => $e->getMessage()]);
            
            return back()->withInput()
                ->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan detail lengkap satu promosi.
     */
    public function show($id)
    {
        $promo = Promotions::findOrFail($id);
        return view('pages.admin.promo.show', compact('promo'));
    }

    /**
     * Menampilkan form edit promosi.
     */
    public function edit($id)
    {
        $promo = Promotions::findOrFail($id);
        return view('pages.admin.promo.edit', compact('promo'));
    }

    /**
     * Memperbarui data promosi yang sudah ada.
     */
    public function update(Request $request, $id)
    {
        $promo = Promotions::findOrFail($id);

        Log::debug('Promotions Update: Memulai proses update', ['id' => $id, 'payload' => $request->all()]);

        $request->validate([
            'promo_name'     => 'required|string|max:255',
            'promo_code'     => 'nullable|string|max:50|unique:promotions,promo_code,' . $id,
            'discount_type'  => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'min_spend'      => 'nullable|numeric|min:0',
            'usage_limit'    => 'nullable|integer|min:1',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'description'    => 'nullable|string',
            'target_segment' => 'nullable|in:all,Needs Attention,Champions,Potential Loyalists,At Risk,Loyal Customers',
        ]);

        try {
            $data = $request->all();
            
            // Update status is_active
            $data['is_active'] = $request->has('is_active');
            
            // Sinkronisasi Waktu
            $data['start_date'] = Carbon::parse($request->start_date);
            $data['end_date'] = Carbon::parse($request->end_date);
            
            $data['min_spend'] = $request->min_spend ?? 0;

            $promo->update($data);

            Log::info('Promotions Update: Berhasil update', ['id' => $id]);

            return redirect()->route('admin.promo')
                ->with('success', 'Promosi berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('Promotions Update: Gagal update', ['id' => $id, 'error' => $e->getMessage()]);
            
            return back()->withInput()
                ->with('error', 'Gagal memperbarui data.');
        }
    }

    /**
     * Menghapus promosi secara permanen.
     */
    public function destroy($id)
    {
        try {
            $promo = Promotions::findOrFail($id);
            $promo->delete();

            Log::info('Promotions Destroy: Berhasil hapus', ['id' => $id]);

            return redirect()->route('admin.promo')
                ->with('success', 'Promosi telah dihapus.');
        } catch (\Exception $e) {
            Log::error('Promotions Destroy: Gagal hapus', ['id' => $id, 'error' => $e->getMessage()]);
            
            return back()->with('error', 'Gagal menghapus data.');
        }
    }
}