<?php

namespace App\Http\Controllers\Middleware;


use Closure;
use Illuminate\Http\Request;

class EnsureLoggedIn
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login.form');
        }

        return $next($request);
    }
}
