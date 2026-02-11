<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ShoppingListController;

use App\Http\Controllers\RecipeController;


// Recipes
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{id}', [RecipeController::class, 'show'])->name('recipes.show');
Route::post('/recipes/{id}/missing-to-shopping', [RecipeController::class, 'addMissingToShopping'])->name('recipes.missingToShopping');
Route::post('/recipes/{id}/consume', [RecipeController::class, 'consume'])->name('recipes.consume');


Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{id}', [RecipeController::class, 'show'])->name('recipes.show');

// HIÁNYZÓK -> bevásárlólista (API receptből)
Route::post('/recipes/{id}/missing-to-shopping', [RecipeController::class, 'addMissingToShopping'])
    ->name('recipes.missingToShopping');


// Recipes (API + főzés)
Route::get('/recipes', [\App\Http\Controllers\RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{id}', [\App\Http\Controllers\RecipeController::class, 'show'])->whereNumber('id')->name('recipes.show');
Route::post('/recipes/{id}/consume', [\App\Http\Controllers\RecipeController::class, 'consume'])->whereNumber('id')->name('recipes.consume');

// Own recipes (egyszerű verzió a DB dump alapján)
Route::get('/recipes/own/create', [\App\Http\Controllers\RecipeController::class, 'createOwn'])->name('recipes.own.create');
Route::post('/recipes/own', [\App\Http\Controllers\RecipeController::class, 'storeOwn'])->name('recipes.own.store');
Route::get('/recipes/own/{id}', [\App\Http\Controllers\RecipeController::class, 'showOwn'])->whereNumber('id')->name('recipes.own.show');
Route::post('/recipes/own/{id}/delete', [\App\Http\Controllers\RecipeController::class, 'deleteOwn'])->whereNumber('id')->name('recipes.own.delete');


// Shopping list
Route::get('/shopping', [ShoppingListController::class, 'index'])->name('shopping.index');
Route::post('/shopping', [ShoppingListController::class, 'post'])->name('shopping.post');

// (opcionális alias, ha máshonnan /shopping/list-re linkelsz)
Route::get('/shopping/list', [ShoppingListController::class, 'index'])->name('shopping.list');
Route::post('/shopping/list', [ShoppingListController::class, 'post'])->name('shopping.list.post');


Route::get('/', function () {
    if (session('user_id')) return redirect()->route('dashboard');
    return redirect()->route('login.form');
})->name('home');

// debug: megmutatja, melyik mappából fut a Laravel
Route::get('/__where', function () {
    return base_path();
});

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login.do');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register.do');

// Protected (logged)
Route::middleware('logged')->group(function () {
    Route::post('/inventory/list', [InventoryController::class, 'listPost'])->name('inventory.list.post');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages/read', [MessageController::class, 'markRead'])->name('messages.read');

    // Respond route (mindkettő ugyanoda mutat, hogy a blade-ből is működjön)
    Route::post('/messages/invite/respond', [MessageController::class, 'respondInvite'])->name('messages.invite.respond');
    Route::post('/messages/respond', [MessageController::class, 'respondInvite'])->name('messages.respond');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');

    Route::get('/inventory/list', [InventoryController::class, 'list'])->name('inventory.list');
    Route::post('/inventory/list', [InventoryController::class, 'listPost'])->name('inventory.listPost');
    Route::post('/inventory/list', [InventoryController::class, 'listPost'])->name('inventory.list.post');
    // Households
    Route::get('/households', [HouseholdController::class, 'index'])->name('households.index');
    Route::post('/households/invite', [HouseholdController::class, 'invite'])->name('households.invite');
    Route::post('/households/toggle-role', [HouseholdController::class, 'toggleRole'])->name('households.toggleRole');
});
