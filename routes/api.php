<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;

// Публичные (без токена)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/dishes', [DishController::class, 'index']);
Route::get('/dishes/{id}', [DishController::class, 'show']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/free', [RoomController::class, 'free']);
Route::get('/rooms/{id}', [RoomController::class, 'show']);
Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/hotels/{id}', [HotelController::class, 'show']);
Route::get('/hotels/{id}/rooms', [HotelController::class, 'rooms']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/hotel/{hotelId}/rating', [ReviewController::class, 'hotelRating']);
Route::get('/reviews/{id}', [ReviewController::class, 'show']);

// С токеном
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Блюда (админ)
    Route::post('/dishes', [DishController::class, 'store']);
    Route::put('/dishes/{id}', [DishController::class, 'update']);
    Route::delete('/dishes/{id}', [DishController::class, 'destroy']);

    // Поставщики (админ + повар)
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
    // Комнаты
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::put('/rooms/{id}', [RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
    // Бронь
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/my', [BookingController::class, 'my']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::put('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::put('/bookings/{id}/complete', [BookingController::class, 'complete']);
    // Отели
    Route::post('/hotels', [HotelController::class, 'store']);
    Route::put('/hotels/{id}', [HotelController::class, 'update']);
    Route::delete('/hotels/{id}', [HotelController::class, 'destroy']);
    // Заказы
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/kitchen', [OrderController::class, 'kitchen']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    // Продукты
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/products/categories', [ProductController::class, 'categories']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    // Платежи
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/my', [PaymentController::class, 'my']);
    Route::get('/payments/stats', [PaymentController::class, 'stats']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::put('/payments/{id}/refund', [PaymentController::class, 'refund']);
    // Отзывы
    Route::get('/reviews/all', [ReviewController::class, 'all']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}/approve', [ReviewController::class, 'approve']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});
