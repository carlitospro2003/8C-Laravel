<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Mail\TwoFactorCodeMail;

class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('login')->with('success', 'Registro exitoso. Ahora puedes iniciar sesión.');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Generar un código 2FA y guardarlo en la base de datos encriptado
            $plainCode = rand(100000, 999999);
            $user->two_factor_code = Hash::make($plainCode);
            $user->two_factor_expires_at = Carbon::now()->addMinute(); // Expira en 1 minuto
            $user->save();

            // Enviar el código por correo
            Mail::to($user->email)->send(new TwoFactorCodeMail($plainCode));

            // Redirigir a la pantalla de verificación
            return redirect()->route('verify.2fa')->with('wait_time', 60);
        }

        return back()->withErrors(['email' => 'Las credenciales no son correctas.']);
    }

    public function show2FAVerificationForm()
    {
        return view('auth.verify_2fa');
    }

    public function verify2FA(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->two_factor_expires_at || now()->gt($user->two_factor_expires_at)) {
            return back()->with('error', 'El código ha expirado.');
        }

        // Verificar el código ingresado comparando con el hash
        if (!Hash::check($request->code, $user->two_factor_code)) {
            return back()->with('error', 'El código es incorrecto.');
        }

        // Limpiar el código 2FA después de verificarlo correctamente
        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        // Crear una cookie segura para indicar autenticación 2FA
        $cookie = cookie('2fa_authenticated', true, 60);

        return redirect()->route('home')->withCookie($cookie);
    }

    public function resend2FACode()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado.'], 401);
        }

        // Generar nuevo código y actualizar en la base de datos
        $plainCode = rand(100000, 999999);
        $user->two_factor_code = Hash::make($plainCode);
        $user->two_factor_expires_at = Carbon::now()->addMinute();
        $user->save();

        // Enviar el nuevo código por correo
        Mail::to($user->email)->send(new TwoFactorCodeMail($plainCode));

        // Incrementar el tiempo de espera
        $newWaitTime = session('wait_time', 60) + 30;
        session(['wait_time' => $newWaitTime]);

        return response()->json(['message' => 'Nuevo código enviado.', 'new_wait_time' => $newWaitTime]);
    }
}
