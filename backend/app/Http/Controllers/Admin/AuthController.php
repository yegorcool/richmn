<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin_authenticated')) {
            return redirect('/admin');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        if ($request->login === env('ADMIN_LOGIN') && $request->password === env('ADMIN_PASSWORD')) {
            session(['admin_authenticated' => true]);
            return redirect('/admin');
        }

        return back()->withErrors(['login' => 'Неверные учётные данные']);
    }

    public function logout()
    {
        session()->forget('admin_authenticated');
        return redirect('/admin/login');
    }
}
