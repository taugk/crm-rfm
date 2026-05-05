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
    public function index(Request $request)
    {
        $query = Customers::query();
        
        // Filter pencarian (search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Urutkan dari yang terbaru
        $data = $query->latest()->paginate(10);
        
        // Jika request AJAX
        if ($request->ajax() || $request->has('ajax')) {
            // Generate HTML untuk tbody
            $html = $this->generateTableRows($data);
            
            // Generate pagination info
            $paginationInfo = "Menampilkan " . ($data->firstItem() ?? 0) . " - " . ($data->lastItem() ?? 0) . " dari " . $data->total() . " pelanggan";
            
            // Generate pagination links dengan mempertahankan query parameters
            $paginationLinks = $data->appends($request->only(['search', 'status']))->links('pagination::bootstrap-5')->render();
            
            return response()->json([
                'success' => true,
                'html' => $html,
                'pagination_info' => $paginationInfo,
                'pagination_links' => (string) $paginationLinks
            ]);
        }
        
        return view('pages.admin.customers.index', compact('data'));
    }

    /**
     * Generate HTML untuk tbody tabel
     */
    private function generateTableRows($data)
    {
        $html = '';
        
        if ($data->count() > 0) {
            foreach ($data as $index => $p) {
                $number = ($data->currentPage() - 1) * $data->perPage() + $index + 1;
                $customerId = str_pad($p->id, 5, '0', STR_PAD_LEFT);
                $statusBadgeClass = $p->status == 'active' ? 'bg-light-success text-success' : 'bg-light-danger text-danger';
                
                $html .= '
                <tr class="pelanggan-row">
                    <td class="text-center small">' . $number . '</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3">
                                <img src="' . ($p->profile_photo ? asset($p->profile_photo) : 'https://www.w3schools.com/howto/img_avatar.png') . '" 
                                     class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            </div>
                            <div>
                                <span class="fw-bold pelanggan-name text-dark d-block">' . e($p->name) . '</span>
                                <small class="text-muted">#PLG-' . $customerId . '</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small">
                            <div class="pelanggan-email mb-1 text-truncate" style="max-width: 150px;">
                                <i class="bi bi-envelope me-1"></i> ' . e($p->email ?? '-') . '
                            </div>
                            <div class="pelanggan-phone text-muted">
                                <i class="bi bi-telephone me-1"></i> ' . e($p->phone) . '
                            </div>
                        </div>
                    </td>
                    <td>
                        <p class="small text-truncate mb-0" style="max-width: 150px;" title="' . e($p->full_address ?? '-') . '">
                            ' . e($p->full_address ?? '-') . '
                        </p>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light-primary text-primary fw-bold">' . number_format($p->total_points) . '</span>
                    </td>
                    <td class="text-center">
                        <span class="badge ' . $statusBadgeClass . ' pelanggan-status px-3">
                            ' . ucfirst($p->status) . '
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group gap-1">
                            <a href="' . route('admin.customers.show', $p->id) . '" class="btn btn-sm btn-info text-white rounded-2"> Detail</a>
                            <a href="' . route('admin.customers.edit', $p->id) . '" class="btn btn-sm btn-warning text-dark rounded-2"> Edit</a>
                            <form action="' . route('admin.customers.destroy', $p->id) . '" method="POST" class="d-inline">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit" class="btn btn-sm btn-danger rounded-2 delete-confirm"> Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>';
            }
        } else {
            $html = '
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    Data pelanggan tidak ditemukan.
                </td>
            </tr>';
        }
        
        return $html;
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
        // Validasi password
        'password'      => 'nullable|string|min:6|confirmed',
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

        // Update password jika diisi
        if ($request->filled('password')) {
            $customer->password = bcrypt($request->password);
            Log::info('Password updated for customer ID: ' . $id);
        }

        if ($request->hasFile('profile_photo')) {
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
        return back()->withInput()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
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

    /**
     * Export data pelanggan ke Excel
     */
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