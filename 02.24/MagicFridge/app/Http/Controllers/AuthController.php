<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        $user = DB::table('users')->where('email', trim((string)$request->email))->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Incorrect email or password.'])->onlyInput('email');
        }

        if (empty($user->email_verified_at)) {
            return back()
                ->withErrors(['email' => 'Please verify your email first. Check your inbox (or laravel.log if MAIL_MAILER=log).'])
                ->onlyInput('email');
        }

        session([
            'user_id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login.form');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:40'],
            'email'     => ['required', 'email', 'max:40', Rule::unique('users', 'email')],
            'password'  => ['required', 'string', 'min:4', 'max:40', 'confirmed'],
        ]);

        $token = Str::random(64);

        DB::table('users')->insert([
            'full_name' => trim($validated['full_name']),
            'email' => trim($validated['email']),
            'password' => Hash::make($validated['password']),
            'email_verify_token' => $token,
            'email_verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $verifyUrl = route('verify.email', ['token' => $token]);

        try {
            Mail::raw(
                "Szia!\n\nKattints ide a fiók hitelesítéséhez:\n$verifyUrl\n\nMagicFridge",
                function ($message) use ($validated) {
                    $message->to($validated['email'])->subject('MagicFridge - Email verification');
                }
            );
        } catch (\Throwable $e) {
            return redirect()->route('login.form')
                ->with('status', 'Regisztráció ok, de email küldés hiba. Teszthez állítsd MAIL_MAILER=log-ra.');
        }

        return redirect()->route('login.form')
            ->with('status', 'Regisztráció sikeres! Ellenőrizd az emailed és kattints a verify linkre.');
    }

    public function verifyEmail(Request $request)
    {
        $token = trim((string)$request->query('token', ''));

        if ($token === '') {
            return redirect()->route('login.form')->withErrors(['Hiányzó hitelesítő token.']);
        }

        $u = DB::table('users')->where('email_verify_token', $token)->first();

        if (!$u) {
            return redirect()->route('login.form')->withErrors(['Érvénytelen vagy lejárt hitelesítő link.']);
        }

        if (!empty($u->email_verified_at)) {
            return redirect()->route('login.form')->with('status', 'Az email már hitelesítve van.');
        }

        DB::table('users')->where('id', $u->id)->update([
            'email_verified_at' => now(),
            'email_verify_token' => null,
            'updated_at' => now(),
        ]);

        return redirect()->route('login.form')->with('status', 'Email hitelesítve! Most már be tudsz lépni.');
    }
}
