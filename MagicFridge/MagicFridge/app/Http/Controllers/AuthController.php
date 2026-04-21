<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\VerifyEmail;

class AuthController extends Controller
{
    // ─── Register ────────────────────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:4', 'max:40', 'confirmed'],
        ]);

        // A verifikáció token alapú: a véletlen token bekerül az adatbázisba,
        // majd az e-mailben kapott link alapján ezzel az értékkel azonosítjuk a felhasználót.
        $token = Str::random(64);

        // Nyers INSERT-et használunk, mert a users táblában nincs minden Laravel
        // által elvárt timestamp oszlop, például updated_at.
        DB::insert(
            "INSERT INTO users (full_name, email, password, email_verify_token, created_at)
             VALUES (?, ?, ?, ?, NOW())",
            [
                trim($validated['full_name']),
                trim($validated['email']),
                Hash::make($validated['password']),
                $token,
            ]
        );

        // A regisztráció csak akkor lesz használható, ha az e-mail kiküldése sikerül,
        // mert belépéskor az email_verified_at mező alapján tiltjuk a nem verifikált fiókokat.
        try {
            Mail::to(trim($validated['email']))->send(new VerifyEmail($token, trim($validated['full_name'])));
        } catch (\Throwable $e) {
            // Fejlesztés közben hasznos, ha a levélküldési hiba a logban és a felületen is látszik.
            \Log::error('Verification mail failed: ' . $e->getMessage());

            return back()->withInput()->withErrors([
                'email' => 'Could not send verification email: ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('login.form')
            ->with('info', 'Registration successful! Please check your e-mail and verify your address before logging in.');
    }

    // ─── Email verification ──────────────────────────────────────────────────

    /**
     * Called when the user clicks the link in the e-mail.
     * Route: GET /verify-email/{token}
     */
    public function verifyEmail(string $token)
    {
        $user = DB::selectOne(
            "SELECT id, email_verified_at FROM users WHERE email_verify_token = ?",
            [$token]
        );

        if (!$user) {
            return view('auth.verify', ['status' => 'invalid']);
        }

        if ($user->email_verified_at !== null) {
            return view('auth.verify', ['status' => 'already']);
        }

        DB::update(
            "UPDATE users SET email_verified_at = NOW(), email_verify_token = NULL WHERE id = ?",
            [$user->id]
        );

        return view('auth.verify', ['status' => 'success']);
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::selectOne(
            "SELECT * FROM users WHERE email = ?",
            [trim($validated['email'])]
        );

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['email' => 'Invalid e-mail or password.'])->withInput();
        }

        // A nem verifikált fiókot meghagyjuk az adatbázisban, de nem engedjük be
        // a védett oldalakra, amíg nincs email_verified_at értéke.
        if ($user->email_verified_at === null) {
            return back()->withErrors([
                'email' => 'Please verify your e-mail address before logging in. Check your inbox.',
            ])->withInput();
        }

        // Sikeres belépés után új session azonosítót kérünk, hogy ne lehessen
        // korábban ismert session ID-val visszaélni.
        session()->regenerate();
        session(['user_id' => $user->id, 'user_name' => $user->full_name]);

        return redirect()->intended(route('dashboard'));
    }

    // ─── Logout ──────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }

    public function logoutViaGet(Request $request)
    {
        return $this->logout($request);
    }
}
