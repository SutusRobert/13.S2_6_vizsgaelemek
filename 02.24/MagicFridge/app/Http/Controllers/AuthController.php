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

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Incorrect email or password..'])->onlyInput('email');
        }

        // ✅ EMAIL HITLESÍTÉS ELLENŐRZÉS
        // Ha nincs hitelesítve, ne engedjük be
       

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
        ], [
            'full_name.required' => 'All fields are required.',
            'email.required'     => 'All fields are required.',
            'password.required'  => 'All fields are required.',
            'email.unique'       => 'This email address is already in use.',
            'password.confirmed' => 'The passwords do not match.',
        ]);

        $token = Str::random(64);

        // ✅ User mentése + token
        DB::table('users')->insert([
            'full_name' => trim($validated['full_name']),
            'email'     => trim($validated['email']),
            'password'  => Hash::make($validated['password']),

            // email hitelesítés mezők
            'email_verify_token' => $token,
            'email_verified_at'  => null,

            'created_at' => now(),
        ]);

        // ✅ Email küldés hitelesítő linkkel
        $verifyUrl = route('verify.email', ['token' => $token]);

        try {
            Mail::raw(
                "Szia!\n\nKattints a fiókod hitelesítéséhez:\n$verifyUrl\n\nHa nem te regisztráltál, hagyd figyelmen kívül.",
                function ($message) use ($validated) {
                    $message->to($validated['email'])
                        ->subject('MagicFridge – Email hitelesítés');
                }
            );
        } catch (\Throwable $e) {
            // Ha nincs beállítva a mail, akkor se haljon el a regisztráció,
            // csak jelezzük, hogy nem ment ki.
            return redirect()
                ->route('login.form')
                ->with('status', 'Sikeres regisztráció, de nem sikerült emailt küldeni. Állítsd be a MAIL-t az .env-ben.');
        }

        return redirect()
            ->route('login.form')
            ->with('status', 'Sikeres regisztráció! Nézd meg az emailed és hitelesítsd a fiókot a belépéshez.');
    }

    // ✅ VERIFY LINK KEZELÉS
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

        // ha már hitelesítve van
        if (!empty($u->email_verified_at)) {
            return redirect()->route('login.form')->with('status', 'Az email már hitelesítve van, be tudsz lépni.');
        }

        DB::table('users')->where('id', $u->id)->update([
            'email_verified_at' => now(),
            'email_verify_token' => null,
        ]);

        return redirect()->route('login.form')->with('status', 'Email hitelesítve! Most már be tudsz lépni.');
    }
}