<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Plan
Route::group(['prefix' => 'users'], function () {
    Route::get('change-password', [UserController::class, 'changePassword']);
    Route::post('update-password', [UserController::class, 'updatePassword']);
});

// Plan
Route::group(['prefix' => 'plan'], function () {
    Route::get('/', [PlanController::class, 'index']);
    Route::get('create', [PlanController::class, 'create']);
    Route::post('store', [PlanController::class, 'store']);
    Route::get('edit/{id}', [PlanController::class, 'edit']);
    Route::get('status/{id}', [PlanController::class, 'status']);
    Route::get('destroy/{id}', [PlanController::class, 'destroy']);
});

// Customer
Route::group(['prefix' => 'customer'], function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::get('create', [CustomerController::class, 'create']);
    Route::post('store', [CustomerController::class, 'store']);
    Route::get('edit/{id}', [CustomerController::class, 'edit']);
    Route::get('status/{id}', [CustomerController::class, 'status']);
    Route::get('destroy/{id}', [CustomerController::class, 'destroy']);
});
