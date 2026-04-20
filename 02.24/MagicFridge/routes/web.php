<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\RecipeController;

Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify.email');

Route::middleware('logged')->group(function () {
    // API-s receptek listázása, részletezése és főzéskori készletlevonása.
    Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
    Route::get('/recipes/{id}', [RecipeController::class, 'show'])->whereNumber('id')->name('recipes.show');
    Route::post('/recipes/{id}/missing-to-shopping', [RecipeController::class, 'addMissingToShopping'])->whereNumber('id')->name('recipes.missingToShopping');
    Route::post('/recipes/{id}/consume', [RecipeController::class, 'consume'])->whereNumber('id')->name('recipes.consume');

    // Saját receptek kezelése: létrehozás, mentés, megjelenítés és törlés.
    Route::get('/recipes/own/create', [RecipeController::class, 'createOwn'])->name('recipes.own.create');
    Route::post('/recipes/own/store', [RecipeController::class, 'storeOwn'])->name('recipes.own.store');
    Route::get('/recipes/own/{id}', [RecipeController::class, 'showOwn'])->name('recipes.own.show');
    Route::post('/recipes/own/{id}/missing-to-shopping', [RecipeController::class, 'addMissingOwnToShopping'])->whereNumber('id')->name('recipes.own.missingToShopping');
    Route::post('/recipes/own/{id}/consume', [RecipeController::class, 'consumeOwn'])->whereNumber('id')->name('recipes.own.consume');

    Route::get('/recipes/own/create', [RecipeController::class, 'createOwn'])->name('recipes.own.create');
    Route::post('/recipes/own', [RecipeController::class, 'storeOwn'])->name('recipes.own.store');
    Route::get('/recipes/own/{id}', [RecipeController::class, 'showOwn'])->whereNumber('id')->name('recipes.own.show');
    Route::post('/recipes/own/{id}/missing-to-shopping', [RecipeController::class, 'addMissingOwnToShopping'])->whereNumber('id')->name('recipes.own.missingToShopping');
    Route::post('/recipes/own/{id}/consume', [RecipeController::class, 'consumeOwn'])->whereNumber('id')->name('recipes.own.consume');
    Route::post('/recipes/own/{id}/delete', [RecipeController::class, 'deleteOwn'])->whereNumber('id')->name('recipes.own.delete');
});

// Recept útvonalak. A fájlban több régi duplikált definíció is maradt;
// Laravel a későbbi névdefiníciókat fogja használni, ezért működés közben ezek fedik egymást.
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{id}', [RecipeController::class, 'show'])->name('recipes.show');
Route::post('/recipes/{id}/missing-to-shopping', [RecipeController::class, 'addMissingToShopping'])->name('recipes.missingToShopping');
Route::post('/recipes/{id}/consume', [RecipeController::class, 'consume'])->name('recipes.consume');

Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{id}', [RecipeController::class, 'show'])->name('recipes.show');

// Külső API-s recept hiányzó alapanyagait bevásárlólistára küldi.
Route::post('/recipes/{id}/missing-to-shopping', [RecipeController::class, 'addMissingToShopping'])
    ->name('recipes.missingToShopping');

// API-s receptek részletei és főzéskor készletlevonás.
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{id}', [RecipeController::class, 'show'])->whereNumber('id')->name('recipes.show');
Route::post('/recipes/{id}/consume', [RecipeController::class, 'consume'])->whereNumber('id')->name('recipes.consume');

// Saját receptek: egyszerű adatbázisos tárolás, külső API nélkül.
Route::get('/recipes/own/create', [RecipeController::class, 'createOwn'])->name('recipes.own.create');
Route::post('/recipes/own', [RecipeController::class, 'storeOwn'])->name('recipes.own.store');
Route::get('/recipes/own/{id}', [RecipeController::class, 'showOwn'])->whereNumber('id')->name('recipes.own.show');
Route::post('/recipes/own/{id}/missing-to-shopping', [RecipeController::class, 'addMissingOwnToShopping'])->whereNumber('id')->name('recipes.own.missingToShopping');
Route::post('/recipes/own/{id}/consume', [RecipeController::class, 'consumeOwn'])->whereNumber('id')->name('recipes.own.consume');
Route::post('/recipes/own/{id}/delete', [RecipeController::class, 'deleteOwn'])->whereNumber('id')->name('recipes.own.delete');

// Bevásárlólista: egy GET oldal és egy POST action-dispatcher kezeli a műveleteket.
Route::get('/shopping', [ShoppingListController::class, 'index'])->name('shopping.index');
Route::post('/shopping', [ShoppingListController::class, 'post'])->name('shopping.post');

// Opcionális alias régebbi vagy más nézetből érkező /shopping/list linkekhez.
Route::get('/shopping/list', [ShoppingListController::class, 'index'])->name('shopping.list');
Route::post('/shopping/list', [ShoppingListController::class, 'post'])->name('shopping.list.post');

Route::get('/', function () {
    // Kezdőoldali döntés: belépett user dashboardra, vendég loginra megy.
    if (session('user_id')) return redirect()->route('dashboard');
    return redirect()->route('login.form');
})->name('home');

// Debug útvonal: megmutatja, melyik projektmappából fut a Laravel.
Route::get('/__where', function () {
    return base_path();
});

// Bejelentkezés, regisztráció és kiléptetés útvonalai.
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login.do');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logoutViaGet'])->name('logout.get');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register.do');

// Védett útvonalak: csak akkor érhetők el, ha a sessionben van user_id.
Route::middleware('logged')->group(function () {
    Route::post('/inventory/list', [InventoryController::class, 'listPost'])->name('inventory.list.post');

    // Dashboard: kezdő áttekintő oldal a modulokkal és értesítésekkel.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Üzenetek: meghívók, lejárati figyelmeztetések és olvasottság.
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages/read', [MessageController::class, 'markRead'])->name('messages.read');
    Route::post('/messages/delete', [MessageController::class, 'delete'])->name('messages.delete');

    // Két név ugyanarra a meghívó-válasz handlerre mutat, hogy a régi Blade linkek se törjenek el.
    Route::post('/messages/invite/respond', [MessageController::class, 'respondInvite'])->name('messages.invite.respond');
    Route::post('/messages/respond', [MessageController::class, 'respondInvite'])->name('messages.respond');

    // Inventory: készlet felvitele, listázása és soronkénti módosítása.
    Route::get('/inventory', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');

    Route::get('/inventory/list', [InventoryController::class, 'list'])->name('inventory.list');
    Route::post('/inventory/list', [InventoryController::class, 'listPost'])->name('inventory.listPost');
    Route::post('/inventory/list', [InventoryController::class, 'listPost'])->name('inventory.list.post');

    // Háztartások: tagmeghívás és jogosultságváltás.
    Route::get('/households', [HouseholdController::class, 'index'])->name('households.index');
    Route::post('/households/invite', [HouseholdController::class, 'invite'])->name('households.invite');
    Route::post('/households/toggle-role', [HouseholdController::class, 'toggleRole'])->name('households.toggleRole');
});
