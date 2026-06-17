<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Session::get('admin_id')) {
            return redirect('/admin/dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $email    = trim($request->input('email'));
        $password = $request->input('password');

        $admin = DB::table('admin')->where('email', $email)->first();

        if (!$admin) {
            return back()->with('error', 'Invalid admin email or password.')->withInput();
        }

        // Support both bcrypt and plain text (for demo)
        $valid = $password === $admin->password 
    || (strlen($admin->password) > 20 && Hash::check($password, $admin->password));

        if (!$valid) {
            return back()->with('error', 'Invalid admin email or password.')->withInput();
        }

        Session::put('admin_id',   $admin->id);
        Session::put('admin_name', $admin->full_name);
        Session::put('admin_email',$admin->email);

        return redirect('/admin/dashboard');
    }

    public function logout()
    {
        Session::forget(['admin_id', 'admin_name', 'admin_email']);
        return redirect('/admin/login');
    }
}