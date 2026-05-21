<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Menampilkan halaman form login
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Memproses data saat tombol login ditekan
     */
    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $credentials = $request->validate([
            'nik' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // 2. Coba login (Auth::attempt akan otomatis mengecek hash password)
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Jika sukses, arahkan ke halaman dashboard
            return redirect()->intended('dashboard');
        }

        // 3. Jika gagal, kembalikan ke halaman login dengan pesan error
        return back()->withErrors([
            'nik' => 'NIK atau Password yang Anda masukkan salah.',
        ])->onlyInput('nik');
    }

    /**
     * Memproses Logout
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
