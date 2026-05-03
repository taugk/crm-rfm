<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        $data = User::latest()->paginate(10);
        return view('pages.admin.user.index', compact('data'));
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('pages.admin.user.show', compact('user'));
    }

    public function create()
    {
        return view('pages.admin.user.create');
    }

    public function store(Request $request)
    {
        // 1. Validasi sesuai dengan input di Form
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'phone'         => 'nullable|string|max:20', 
            'role'          => 'required|in:admin,manager,kasir',
            'password'      => 'required|string|min:6|confirmed', 
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // 2. Siapkan data untuk disimpan
        $data = [
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'role'     => $request->role,
            'password' => bcrypt($request->password),
        ];

        // 3. Handle Upload Foto jika ada
        if ($request->hasFile('profile_photo')) {
            // Menggunakan method helper uploadImage yang sudah kita buat sebelumnya
            $data['profile_photo'] = $this->uploadImage($request->file('profile_photo'), $request->name);
        }

        // 4. Eksekusi Create User
        User::create($data);

        // 5. Redirect ke halaman index dengan pesan sukses
        return redirect()->route('admin.users')->with('success', 'User berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $u = User::findOrFail($id);
        return view('pages.admin.user.edit', compact('u'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'role' => 'required|in:admin,manager,kasir',
            'password' => 'nullable|string|min:6|confirmed',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
        ];

        // Update password hanya jika diisi
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        // Handle Update Foto
        if ($request->hasFile('profile_photo')) {
            // Hapus foto lama jika ada
            if ($user->profile_photo) {
                // Hapus string 'storage/' agar sesuai dengan path di folder public
                $oldPath = str_replace('storage/', 'public/', $user->profile_photo);
                Storage::delete($oldPath);
            }
            
            $updateData['profile_photo'] = $this->uploadImage($request->file('profile_photo'), $request->name);
        }

        $user->update($updateData);

        return redirect()->route('admin.users')->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Hapus foto dari storage sebelum hapus data user
        if ($user->profile_photo) {
            $path = str_replace('storage/', 'public/', $user->profile_photo);
            Storage::delete($path);
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }

    public function myProfile()
    {
        $user = Auth::user();
        $transactionCount = 0;

        return view('pages.admin.profile.index', compact('user', 'transactionCount'));
    }

    /**
     * Helper untuk upload gambar
     */
    private function uploadImage($file, $name)
    {
       
        $cleanName = str_replace(' ', '_', strtolower($name));
        $cleanName = preg_replace('/[^A-Za-z0-9\_]/', '', $cleanName);
        
       
        $filename = time() . '_' . $cleanName . '.' . $file->getClientOriginalExtension();
        
       
        $path = $file->storeAs('profile_photos', $filename, 'public');
        
       
        return 'storage/' . $path;
    }
}