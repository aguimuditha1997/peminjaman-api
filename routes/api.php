<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/rooms', [RoomController::class, 'index']);
Route::post('/rooms', [RoomController::class, 'store']);
Route::get('/rooms/{slug}', [RoomController::class, 'show']);
// Update ruangan berdasarkan nama (Gunakan POST + _method=PUT di Postman jika upload file)
Route::put('/rooms/{slug}', [RoomController::class, 'update']);

// Menghapus ruangan berdasarkan nama
Route::delete('/rooms/{slug}', [RoomController::class, 'destroy']);

Route::get('/bookings', [BookingController::class, 'index']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/{id}', [BookingController::class, 'show']);

Route::post('/login', [AuthController::class, 'login']);

// Rute Terproteksi (Harus kirim Token di Header)
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Rute booking update Anda sebelumnya juga harusnya ada di sini
    Route::put('/bookings/{code}', [BookingController::class, 'update']);
});
