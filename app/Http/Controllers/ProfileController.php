<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'department' => 'nullable'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
        ]);

        return back()->with('success','Profil berhasil diperbarui.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'password'=>'required|min:8|confirmed'
        ]);

        Auth::user()->update([
            'password'=>Hash::make($request->password)
        ]);

        return back()->with('success','Password berhasil diubah.');
    }
}