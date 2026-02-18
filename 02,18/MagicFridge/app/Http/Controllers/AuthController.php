<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;


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
            return back()->withErrors(['email' => 'Helytelen email vagy jelszó.'])->onlyInput('email');
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
    // ugyanaz, mint a régi PHP: mind kötelező + email egyedi + jelszó megerősítés
    $validated = $request->validate([
        'full_name' => ['required', 'string', 'max:40'],
        'email'     => ['required', 'email', 'max:40', Rule::unique('users', 'email')],
        'password'  => ['required', 'string', 'min:4', 'max:40', 'confirmed'],
        // a 'confirmed' miatt kell a password_confirmation mező
    ], [
        'full_name.required' => 'Minden mező kitöltése kötelező.',
        'email.required'     => 'Minden mező kitöltése kötelező.',
        'password.required'  => 'Minden mező kitöltése kötelező.',
        'email.unique'       => 'Ez az email cím már foglalt.',
        'password.confirmed' => 'A jelszavak nem egyeznek.',
    ]);

    DB::table('users')->insert([
        'full_name' => trim($validated['full_name']),
        'email'     => trim($validated['email']),
        'password'  => Hash::make($validated['password']),
    ]);

    return redirect()
        ->route('login.form')
        ->with('status', 'Sikeres regisztráció! Most már be tudsz jelentkezni.');
}


}
