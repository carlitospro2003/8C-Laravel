<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

// Registro de usuarios
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Login
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Verificación de 2FA
Route::get('/verify-2fa', [AuthController::class, 'show2FAVerificationForm'])->name('verify.2fa');
Route::post('/verify-2fa', [AuthController::class, 'verify2FA'])->name('verify.2fa.post');
Route::post('/resend-2fa', [AuthController::class, 'resend2FACode'])->name('resend.2fa');

//  Rutas protegidas por autenticación y 2FA
Route::middleware(['auth', '2fa'])->group(function () {

    // Página principal después de iniciar sesión
    Route::get('/home', function () {
        return view('home');
    })->name('home');

    // Otras rutas protegidas
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::get('/settings', function () {
        return view('settings');
    })->name('settings');
});

// Cerrar sesión y eliminar autenticación 2FA
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    
    return redirect('/login')->withCookie(cookie()->forget('2fa_authenticated'));
})->name('logout');

Route::post('/resend-2fa', [AuthController::class, 'resend2FACode'])->middleware('auth')->name('resend.2fa');
