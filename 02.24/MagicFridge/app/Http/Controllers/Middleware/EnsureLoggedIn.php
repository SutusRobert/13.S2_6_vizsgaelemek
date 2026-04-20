<?php

namespace App\Http\Controllers\Middleware;


use Closure;
use Illuminate\Http\Request;

class EnsureLoggedIn
{
    public function handle(Request $request, Closure $next)
    {
        // Saját, egyszerű beléptetés: ha nincs user_id a sessionben,
        // a védett útvonalak helyett a login oldalra küldjük a felhasználót.
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login.form');
        }

        return $next($request);
    }
}
