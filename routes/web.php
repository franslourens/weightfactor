<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FoodEntryController;
use App\Http\Controllers\FoodSearchController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WeightEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/weights', [WeightEntryController::class, 'store'])->name('weights.store');
    Route::delete('/weights/{weightEntry}', [WeightEntryController::class, 'destroy'])->name('weights.destroy');

    Route::get('/foods/search', FoodSearchController::class)->name('foods.search');
    Route::post('/foods', [FoodEntryController::class, 'store'])->name('foods.store');
    Route::patch('/foods/{foodEntry}', [FoodEntryController::class, 'update'])->name('foods.update');
    Route::delete('/foods/{foodEntry}', [FoodEntryController::class, 'destroy'])->name('foods.destroy');

    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/body-fat', [ProfileController::class, 'bodyFat'])->name('body-fat.update');
});
