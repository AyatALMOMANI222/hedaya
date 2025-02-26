<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthOtpController;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::post('/generate-otp', [AuthOtpController::class, 'generateOtpForLoginRegister'])->name('otp.generate');
Route::post('/verify-otp', [AuthOtpController::class, 'verifyOtp'])->name('otp.verify');


Route::post('/user/{id}', [UserController::class, 'update']);
Route::get('/user', [UserController::class, 'getAllUsers'])->middleware(['auth:sanctum','admin']);

Route::get('/user/{id}', [UserController::class, 'getUserById']);

Route::put('/user/{id}', [UserController::class, 'updateKycStatus'])->middleware(['auth:sanctum','admin']);