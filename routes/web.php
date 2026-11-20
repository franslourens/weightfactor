<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FoodEntryController;
use App\Http\Controllers\FoodSearchController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\StepEntryController;
use App\Http\Controllers\WeightEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/steps/{year}/{month}', [DashboardController::class, 'index'])
    ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
    ->name('dashboard.steps');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::get('/me', [DashboardController::class, 'mine'])->middleware('auth')->name('profile.mine');
Route::get('/u/{user:slug}/steps/{year}/{month}', [DashboardController::class, 'show'])
    ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
    ->name('profile.steps');
Route::get('/u/{user:slug}', [DashboardController::class, 'show'])->name('profile.show');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');

    Route::post('/steps', [StepEntryController::class, 'store'])->name('steps.store');

    Route::post('/weights', [WeightEntryController::class, 'store'])->name('weights.store');
    Route::delete('/weights/{weightEntry}', [WeightEntryController::class, 'destroy'])->name('weights.destroy');

    Route::get('/foods/search', FoodSearchController::class)->name('foods.search');
    Route::post('/foods', [FoodEntryController::class, 'store'])->name('foods.store');
    Route::patch('/foods/{foodEntry}', [FoodEntryController::class, 'update'])->name('foods.update');
    Route::delete('/foods/{foodEntry}', [FoodEntryController::class, 'destroy'])->name('foods.destroy');

    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/body-fat', [ProfileController::class, 'bodyFat'])->name('body-fat.update');
});
