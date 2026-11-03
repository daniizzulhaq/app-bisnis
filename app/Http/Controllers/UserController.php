<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPasswordRequest;
use App\Http\Requests\UserProfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** Pengaturan akun admin: profil & password. */
class UserController extends Controller
{
    public function edit(Request $request): View
    {
        return view('user.edit', ['user' => $request->user()]);
    }

    public function update(UserProfilRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(UserPasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => Hash::make($request->password)]);

        // Perbarui ID sesi setelah ganti password
        $request->session()->regenerate();

        return back()->with('success', 'Password berhasil diganti.');
    }
}
