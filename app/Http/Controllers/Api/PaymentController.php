<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    // Все платежи (админ)
    public function index(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $query = Payment::with('booking.user', 'booking.room');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        return response()->json(['success' => true, 'data' => $query->latest()->get()]);
    }

    // Мои платежи (гость)
    public function my()
    {
        $payments = Payment::whereHas('booking', function ($q) {
            $q->where('user_id', auth()->id());
        })->with('booking.room')->latest()->get();

        return response()->json(['success' => true, 'data' => $payments]);
    }

    // Один платёж
    public function show($id)
    {
        $payment = Payment::with('booking.user', 'booking.room.hotel')->find($id);
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Платёж не найден'], 404);
        }

        if (auth()->user()->role === 'guest' && $payment->booking->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        return response()->json(['success' => true, 'data' => $payment]);
    }

    // Создать платёж (гость оплачивает свою бронь)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:booking,id',
            'payment_method' => 'required|in:cash,card,online,bank_transfer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $booking = Booking::find($request->booking_id);

        // Гость может оплатить только свою бронь
        if (auth()->user()->role === 'guest' && $booking->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        // Проверить, не оплачена ли уже
        if (Payment::where('booking_id', $request->booking_id)->where('status', 'completed')->exists()) {
            return response()->json(['success' => false, 'message' => 'Бронь уже оплачена'], 400);
        }

        $payment = Payment::create([
            'booking_id' => $request->booking_id,
            'amount' => $booking->total_price,
            'payment_method' => $request->payment_method,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Подтвердить бронь после оплаты
        if ($booking->status === 'new') {
            $booking->update(['status' => 'confirmed']);
        }

        return response()->json(['success' => true, 'data' => $payment], 201);
    }

    // Вернуть платёж (админ)
    public function refund($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Платёж не найден'], 404);
        }

        if ($payment->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Можно вернуть только завершённый платёж'], 400);
        }

        $payment->update(['status' => 'refunded']);

        return response()->json(['success' => true, 'message' => 'Платёж возвращён']);
    }

    // Статистика платежей (админ)
    public function stats()
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_completed' => Payment::where('status', 'completed')->sum('amount'),
                'total_pending' => Payment::where('status', 'pending')->sum('amount'),
                'total_refunded' => Payment::where('status', 'refunded')->sum('amount'),
                'count_by_method' => Payment::selectRaw('payment_method, count(*) as count')
                    ->groupBy('payment_method')
                    ->get(),
            ]
        ]);
    }
}
