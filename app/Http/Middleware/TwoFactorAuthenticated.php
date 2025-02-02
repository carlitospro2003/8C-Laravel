<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TwoFactorAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->cookie('2fa_authenticated')) {
            return redirect()->route('verify.2fa')->with('error', 'Debes completar la autenticación de dos factores.');
        }

        return $next($request);
    }
}
