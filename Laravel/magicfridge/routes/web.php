<?php

use Illuminate\Support\Facades\Route;
<<<<<<< HEAD

Route::get('/', function () {
    return view('welcome');
});

=======
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Middleware\EnsureLoggedIn;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
<<<<<<< HEAD
use App\Http\Controllers\HouseholdController;



Route::middleware('logged')->group(function () {

    Route::get('/inventory', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory/store', [InventoryController::class, 'store'])->name('inventory.store');

    Route::get('/inventory/list', [InventoryController::class, 'list'])->name('inventory.list');
    Route::post('/inventory/list', [InventoryController::class, 'listPost'])->name('inventory.list.post');
    
});

Route::middleware('logged')->group(function () {
    Route::get('/households', [HouseholdController::class, 'index'])->name('households.index');
    Route::post('/households/invite', [HouseholdController::class, 'invite'])->name('households.invite');
    Route::post('/households/toggle-role', [HouseholdController::class, 'toggleRole'])->name('households.toggleRole');
});


Route::get('/inventory', [InventoryController::class, 'create'])->name('inventory.create');
Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
=======

// Raktár
Route::get('/inventory', [InventoryController::class, 'create'])->name('inventory.create');
Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');

>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
Route::get('/inventory/list', [InventoryController::class, 'list'])->name('inventory.list');
Route::post('/inventory/list', [InventoryController::class, 'listPost'])->name('inventory.list.post');



Route::get('/', fn() => redirect()->route('login.form'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login.do');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ide jön majd a register is (következő lépésben) */
Route::get('/register', function () {
    return 'Register oldal még nincs kész';
})->name('register.form');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register.do');


/* védett oldalak */
Route::middleware('logged')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard'); // később: DashboardController
    })->name('dashboard');
});

Route::middleware(EnsureLoggedIn::class)->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
Route::middleware(EnsureLoggedIn::class)->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
<<<<<<< HEAD
});

=======
});
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
