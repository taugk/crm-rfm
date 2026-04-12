<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori
     */
    public function index()
    {
        $categories = Category::latest()->paginate(10);
        return view('pages.admin.category.index', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kd_category' => 'required|string|max:255|unique:categories,kd_category',
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ], [
            'kd_category.unique' => 'Kode kategori sudah terdaftar!',
            'name.unique'        => 'Nama kategori sudah ada!',
        ]);

        try {
            Category::create([
                'kd_category' => strtoupper($request->kd_category),
                'name'        => $request->name,
                'description' => $request->description,
            ]);

            return redirect()->route('admin.categories')
                             ->with('success', 'Kategori baru berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'kd_category' => 'required|string|max:255|unique:categories,kd_category,' . $id,
            'name'        => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ], [
            'kd_category.unique' => 'Kode kategori ini sudah digunakan oleh data lain!',
            'name.unique'        => 'Nama kategori ini sudah digunakan!',
        ]);

        try {
            $category->update([
                'kd_category' => strtoupper($request->kd_category),
                'name'        => $request->name,
                'description' => $request->description,
            ]);

            return redirect()->route('admin.categories')
                             ->with('success', 'Data kategori berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            
            // Opsional: Cek apakah kategori sedang digunakan di tabel produk
            // if ($category->products()->count() > 0) {
            //     return back()->with('error', 'Kategori tidak bisa dihapus karena masih memiliki produk.');
            // }

            $category->delete();

            return redirect()->route('admin.categories')
                             ->with('success', 'Kategori berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}