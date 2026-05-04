<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Show my profile
     */
    public function myProfile()
    {
        $user = Auth::user();
        return view('kasir.profile.index', compact('user'));
    }
    
    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);
        
        $user->name = $request->name;
        $user->phone = $request->phone;
        
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo = $path;
        }
        
        $user->save();
        
        return redirect()->route('kasir.profile.index')
            ->with('success', 'Profil berhasil diperbarui');
    }
    
    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        $user = Auth::user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah']);
        }
        
        $user->password = Hash::make($request->password);
        $user->save();
        
        return redirect()->route('kasir.profile.index')
            ->with('success', 'Password berhasil diperbarui');
    }
}