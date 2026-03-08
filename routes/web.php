<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

/*
 |--------------------------------------------------------------------------
 | Web Routes
 |--------------------------------------------------------------------------
 |
 | Here is where you can register web routes for your application. These
 | routes are loaded by the RouteServiceProvider and all of them will
 | be assigned to the "web" middleware group. Make something great!
 |
 */

// --- Public / Auth Routes ---
Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class , 'create'])->name('login');
    Route::post('login', [LoginController::class , 'store']);
});

Route::post('logout', [LoginController::class , 'destroy'])
    ->name('logout')
    ->middleware('auth');


// --- Admin Modular Bounds ---
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
            return 'Admin Dashboard Build Phase in Progress';
        }
        )->name('dashboard');
    });


// --- Faculty Modular Bounds ---
Route::middleware(['auth', 'role:faculty'])->prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/dashboard', function () {
            return 'Faculty Dashboard Build Phase in Progress';
        }
        )->name('dashboard');
    });


// --- Student Modular Bounds ---
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', function () {
            return 'Student Dashboard Build Phase in Progress';
        }
        )->name('dashboard');
    });