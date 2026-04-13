<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(){
        $data = Product::with('category', 'details')->latest()->paginate(10);
        $categories = Category::all();
        $lowStockCount = ProductDetail::where('stock', '<=', 10)->count();
        return view('pages.admin.product.index', compact('data', 'categories', 'lowStockCount'));
    }

    public function create(){
        $categories = Category::all();

        return view('pages.admin.product.create', compact('categories'));
    }

    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'sku'           => 'required|unique:products,sku',
            'name'          => 'required',
            'description'   => 'nullable',
            'price'         => 'required|numeric',
            'status'        => 'required|in:active,inactive',
            'category_id'   => 'required|exists:categories,id',
            'image'         => 'nullable|image|max:2048',
            'variant'       => 'nullable|string',
            'stock'         => 'required|integer',
            'cost_price'    => 'nullable|numeric',
            'date_in'       => 'nullable|date',
            'expired_date'  => 'nullable|date|after_or_equal:date_in',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            
            if ($request->hasFile('image')) {
                $validated['image'] = $this->uploadImage($request->file('image'), $validated['name']);
            }

            $product = Product::create([
                'sku'         => $validated['sku'],
                'name'        => $validated['name'],
                'description' => $validated['description'],
                'price'       => $validated['price'],
                'status'      => $validated['status'],
                'category_id' => $validated['category_id'],
                'image'       => $validated['image'] ?? null,
            ]);

            $product->details()->create([
                'variant'      => $validated['variant'],
                'stock'        => $validated['stock'],
                'cost_price'   => $validated['cost_price'],
                'date_in'      => $validated['date_in'],
                'expired_date' => $validated['expired_date'],
            ]);

            return redirect()->route('admin.products')->with('success', 'Produk berhasil ditambahkan.');
        });

    } catch (\Illuminate\Validation\ValidationException $e) {
        throw $e;
    } catch (\Exception $e) {
        // Tetap simpan log error untuk keamanan jika terjadi kegagalan database
        Log::error('Gagal menyimpan produk: ' . $e->getMessage());

        return back()->withInput()->with('error', 'Terjadi kesalahan sistem saat menyimpan data.');
    }
}

    public function edit($id){
        $product = Product::with('details')->findOrFail($id);
        $categories = Category::all();

        return view('pages.admin.product.edit', compact('product', 'categories'));
    }

   public function update(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'sku'           => 'required|unique:products,sku,' . $id,
            'name'          => 'required',
            'description'   => 'nullable',
            'price'         => 'required|numeric',
            'status'        => 'required|in:active,inactive',
            'category_id'   => 'required|exists:categories,id',
            'image'         => 'nullable|image|max:2048',
            'variant'       => 'nullable|string',
            'stock'         => 'required|integer',
            'cost_price'    => 'nullable|numeric',
            'date_in'       => 'nullable|date',
            'expired_date'  => 'nullable|date|after_or_equal:date_in',
        ]);

        return DB::transaction(function () use ($request, $validated, $id) {
            $product = Product::findOrFail($id);

            // 1. Persiapkan data untuk update
            $updateData = [
                'sku'         => $validated['sku'],
                'name'        => $validated['name'],
                'description' => $validated['description'],
                'price'       => $validated['price'],
                'status'      => $validated['status'],
                'category_id' => $validated['category_id'],
            ];

            // 2. Handle Image (Hanya jika upload file baru)
            if ($request->hasFile('image')) {
                // Opsional: Tambahkan logika hapus foto lama di sini jika perlu
                $updateData['image'] = $this->uploadImage($request->file('image'), $validated['name']);
            }

            // 3. Jalankan Update Produk
            $product->update($updateData);

            // 4. Update atau Create Detail (Menggunakan updateOrCreate lebih efisien)
            $product->details()->updateOrCreate(
                ['product_id' => $product->id], // Kunci pencarian
                [
                    'variant'      => $validated['variant'],
                    'stock'        => $validated['stock'],
                    'cost_price'   => $validated['cost_price'],
                    'date_in'      => $validated['date_in'],
                    'expired_date' => $validated['expired_date'],
                ]
            );

            return redirect()->route('admin.products')->with('success', 'Produk berhasil diperbarui.');
        });

    } catch (\Illuminate\Validation\ValidationException $e) {
        throw $e;
    } catch (\Exception $e) {
        Log::error('Gagal update produk ID ' . $id . ': ' . $e->getMessage());
        return back()->withInput()->with('error', 'Terjadi kesalahan sistem saat memperbarui data.');
    }
}

    public function show($id){
        $product = Product::with('category', 'details')->findOrFail($id);
        return view('pages.admin.product.show', compact('product'));
    }

    public function destroy($id){
        try {
            Product::findOrFail($id)->delete();
            return redirect()->route('admin.products')->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    public function export(Request $request) 
    {
        //format nama file Data Produk+timestamp.xlsx
        return Excel::download(
            new ProductsExport($request), 
            'Data_Produk_' . date('Ymd_His') . '.xlsx'
        );
    }

    public function productReports(Request $request)
{
    // 1. Ambil Parameter Filter dari Request
    $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->input('end_date', now()->format('Y-m-d'));
    $search = $request->input('search');

    // 2. Query Produk Terlaris
    $report = DB::table('transactions_details')
        ->join('product_details', 'transactions_details.product_detail_id', '=', 'product_details.id')
        ->join('products', 'product_details.product_id', '=', 'products.id')
        ->join('transactions', 'transactions_details.transaction_id', '=', 'transactions.id')
        // Filter transaksi yang sukses saja
        ->where('transactions.status', 'completed')
        // Filter Tanggal
        ->whereDate('transactions.transaction_date', '>=', $startDate)
        ->whereDate('transactions.transaction_date', '<=', $endDate)
        // Filter Pencarian (Jika ada)
        ->when($search, function ($query) use ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.sku', 'like', "%{$search}%");
            });
        })
        ->select(
            'products.id',
            'products.name',
            'products.sku',
            'product_details.variant',
            DB::raw('SUM(transactions_details.quantity) as total_sold'),
            DB::raw('SUM(transactions_details.subtotal) as total_revenue')
        )
        ->groupBy('products.id', 'products.name', 'products.sku', 'product_details.variant')
        ->orderBy('total_sold', 'desc')
        ->get();

    // 3. Kirim data ke view
    return view('pages.admin.reports.product-reports', compact('report', 'startDate', 'endDate'));
}

    

    private function uploadImage($image, $productName)
    {
        // 1. Bersihkan nama produk dari spasi dan karakter aneh
        $cleanProductName = str_replace(' ', '_', strtolower($productName));
        $cleanProductName = preg_replace('/[^A-Za-z0-9\_]/', '', $cleanProductName); // Hapus simbol
        
        // 2. Ambil ekstensi asli file (misal: .jpg, .png)
        $extension = $image->getClientOriginalExtension();
        
        // 3. Gabungkan: waktu + nama_produk + ekstensi
        $filename = time() . '_' . $cleanProductName . '.' . $extension;
        
        // 4. Simpan file
        $path = $image->storeAs('products', $filename, 'public');
        
        // 5. Kembalikan path storage/products/nama_file.png
        return str_replace('public/', 'storage/', $path);
    }
}
