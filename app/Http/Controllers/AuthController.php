<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Validation\Rule;


class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[\pL\s]+$/u' // Solo letras y espacios
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed', // Debe coincidir con password_confirmation
                'regex:/[A-Z]/', // Al menos 1 mayúscula
                'regex:/[a-z]/', // Al menos 1 minúscula
                'regex:/[0-9]/', // Al menos 1 número
                'regex:/[\W]/' // Al menos 1 carácter especial
            ],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe contener solo letras.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede tener más de 50 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',

            'email.required' => 'El correo es obligatorio.',
            'email.string' => 'El correo debe ser un texto válido.',
            'email.email' => 'El correo debe tener un formato válido.',
            'email.max' => 'El correo no puede tener más de 255 caracteres.',
            'email.unique' => 'Este correo ya está registrado.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe incluir al menos: 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.',
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
        // Validaciones mínimas: solo requeridos
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ], [
            'email.required' => 'El correo es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        // Intento de autenticación
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return redirect()->route('home');
        }

        // Mensaje genérico de error
        return back()->withErrors(['login_error' => 'Las credenciales no coinciden.']);
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
