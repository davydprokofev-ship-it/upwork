<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    // Все брони (админ)
    public function index()
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => Booking::with(['user', 'room.hotel'])->get()
        ]);
    }

    // Мои брони (гость)
    public function my()
    {
        return response()->json([
            'success' => true,
            'data' => Booking::where('user_id', auth()->id())->with('room.hotel')->get()
        ]);
    }

    // Одна бронь
    public function show($id)
    {
        $booking = Booking::with(['user', 'room.hotel', 'payments'])->find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Бронь не найдена'], 404);
        }

        // Гость видит только свои, админ — любые
        if (auth()->user()->role === 'guest' && $booking->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        return response()->json(['success' => true, 'data' => $booking]);
    }

    // Создать бронь (гость)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'guests_count' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Проверить, свободен ли номер
        $room = Room::find($request->room_id);
        if ($room->status !== 'free') {
            return response()->json(['success' => false, 'message' => 'Номер занят'], 400);
        }

        // Посчитать цену
        $checkIn = new \DateTime($request->check_in_date);
        $checkOut = new \DateTime($request->check_out_date);
        $nights = $checkIn->diff($checkOut)->days;
        $totalPrice = $nights * $room->price_per_night;

        $booking = Booking::create([
            'user_id' => auth()->id(),
            'room_id' => $request->room_id,
            'guests_count' => $request->guests_count ?? 1,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'total_price' => $totalPrice,
        ]);

        // Занять номер
        $room->update(['status' => 'occupied']);

        return response()->json(['success' => true, 'data' => $booking], 201);
    }

    // Отменить бронь (гость или админ)
    public function cancel($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Бронь не найдена'], 404);
        }

        // Гость может отменить только свою
        if (auth()->user()->role === 'guest' && $booking->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return response()->json(['success' => false, 'message' => 'Бронь уже отменена или завершена'], 400);
        }

        $booking->update(['status' => 'cancelled']);

        // Освободить номер
        Room::where('id', $booking->room_id)->update(['status' => 'free']);

        return response()->json(['success' => true, 'message' => 'Бронь отменена']);
    }

    // Подтвердить бронь (админ)
    public function confirm($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Бронь не найдена'], 404);
        }

        $booking->update(['status' => 'confirmed']);

        return response()->json(['success' => true, 'message' => 'Бронь подтверждена']);
    }

    // Завершить бронь (админ)
    public function complete($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Бронь не найдена'], 404);
        }

        $booking->update(['status' => 'completed']);

        // Освободить номер
        Room::where('id', $booking->room_id)->update(['status' => 'free']);

        return response()->json(['success' => true, 'message' => 'Бронь завершена']);
    }
}
