// ── Auth routes ─────────────────────────────────────────────────────────────
// Add these lines to your routes/web.php

use App\Http\Controllers\AuthController;

Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',    [AuthController::class, 'login']);

Route::post('/logout',   [AuthController::class, 'logout'])->name('logout');

// Email verification — the link in the e-mail points here
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])
    ->name('verify.email')
    ->where('token', '[A-Za-z0-9]{64}');
