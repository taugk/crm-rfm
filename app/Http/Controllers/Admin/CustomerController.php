<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use App\Models\Customers; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    /**
     * Menampilkan daftar pelanggan.
     */
    public function index()
    {
        // Tidak perlu with('address') lagi karena sudah jadi satu tabel
        $data = Customers::latest()->paginate(10);
        return view('pages.admin.customers.index', compact('data'));
    }

    public function create()
    {
        return view('pages.admin.customers.create');
    }

    /**
     * Menyimpan data pelanggan baru.
     */
    public function store(Request $request)
{
    Log::info('Customer Store Request: ', $request->all());
    
    $request->validate([
        'name'          => 'required|string|max:255',
        'email'         => 'nullable|email|unique:customers,email',
        'phone'         => 'required|string|max:20|unique:customers,phone',
        'password'      => 'nullable|string|min:6', 
        'gender'        => 'nullable|in:male,female,other',
        'birthdate'     => 'nullable|date',
        'status'        => 'required|in:active,inactive,block',
        'full_address'  => 'nullable|string',
        'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    try {
        DB::beginTransaction();

        $customer = new Customers(); 
        
        // Mapping data dari request ke model
        $customer->name          = $request->name;
        $customer->email         = $request->email;
        $customer->phone         = $request->phone;
        $customer->gender        = $request->gender;
        $customer->date_of_birth = $request->birthdate;
        $customer->status        = $request->status;
        $customer->full_address  = $request->full_address;
        $customer->role          = 'customer';
        $customer->total_points  = 0; 

    
        if ($request->filled('password')) {
            $customer->password = bcrypt($request->password);
        }

        // Handle Upload Foto
        if ($request->hasFile('profile_photo')) {
            
            $customer->profile_photo = $this->uploadImage($request->file('profile_photo'), $request->name);
        }

        Log::info('Customer Data Before Save: ', $customer->toArray());

        $customer->save();

        DB::commit();

        Log::info('Customer Store Success: ID ' . $customer->id);
        

        return redirect()->route('admin.customers')->with('success', 'Pelanggan berhasil ditambahkan!');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Customer Store Failed: ' . $e->getMessage());
        return back()->withInput()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
}

    /**
     * Menampilkan detail pelanggan.
     */
    public function show($id)
    {
        // Hapus with('address', 'rfmAnalysis') karena belum digunakan/sudah dihapus
        $customer = Customers::with('transactions')->findOrFail($id);
        return view('pages.admin.customers.show', compact('customer'));
    }

    public function edit($id)
    {
        $customer = Customers::findOrFail($id);
        return view('pages.admin.customers.edit', compact('customer'));
    }

    /**
     * Memperbarui data pelanggan.
     */
    public function update(Request $request, $id)
    {
        $customer = Customers::findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'nullable|email|unique:customers,email,' . $id,
            'phone'         => 'required|string|max:20|unique:customers,phone,' . $id,
            'gender'        => 'nullable|in:male,female,other',
            'birthdate'     => 'nullable|date',
            'status'        => 'required|in:active,inactive,block',
            'full_address'  => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $customer->fill([
                'name'          => $request->name,
                'email'         => $request->email,
                'phone'         => $request->phone,
                'gender'        => $request->gender,
                'date_of_birth' => $request->birthdate,
                'status'        => $request->status,
                'full_address'  => $request->full_address,
            ]);

            if ($request->hasFile('profile_photo')) {
                // Hapus foto lama jika ada
                if ($customer->profile_photo) {
                    $oldPath = str_replace('storage/', '', $customer->profile_photo);
                    Storage::disk('public')->delete($oldPath);
                }
                $customer->profile_photo = $this->uploadImage($request->file('profile_photo'), $request->name);
            }

            $customer->save();

            DB::commit();
            return redirect()->route('admin.customers')->with('success', 'Data pelanggan berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer Update Failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal memperbarui data.');
        }
    }

    /**
     * Menghapus pelanggan (Soft Delete).
     */
    public function destroy($id)
    {
        $customer = Customers::findOrFail($id);
        try {
           
            
            $customer->delete();
            return redirect()->route('admin.customers')->with('success', 'Pelanggan berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus pelanggan.');
        }
    }


    public function export(Request $request) 
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ];

        $fileName = 'customers_' . date('Ymd_His') . '.xlsx';
        
        return Excel::download(new CustomersExport($filters), $fileName);
    }

    /**
     * Helper untuk upload gambar.
     */
    private function uploadImage($image, $name)
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
        $filename = time() . '_' . $cleanName . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('customers', $filename, 'public');
        return 'storage/' . $path;
    }
}