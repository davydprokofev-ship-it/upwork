<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    // Все одобренные отзывы (публично)
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Review::where('is_approved', 1)
                ->with('user', 'room')
                ->latest()
                ->get()
        ]);
    }

    // Отзывы для админа (все, включая неодобренные)
    public function all()
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => Review::with('user', 'room')
                ->latest()
                ->get()
        ]);
    }

    // Один отзыв
    public function show($id)
    {
        $review = Review::with('user', 'room')->find($id);
        if (!$review) {
            return response()->json(['success' => false, 'message' => 'Отзыв не найден'], 404);
        }

        // Гость видит только одобренные
        if (auth()->user()?->role === 'guest' && !$review->is_approved) {
            return response()->json(['success' => false, 'message' => 'Отзыв не найден'], 404);
        }

        return response()->json(['success' => true, 'data' => $review]);
    }

    // Создать отзыв (гость)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'nullable|exists:room,id',
            'text' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $review = Review::create([
            'user_id' => auth()->id(),
            'room_id' => $request->room_id,
            'text' => $request->text,
            'rating' => $request->rating,
        ]);

        return response()->json(['success' => true, 'data' => $review], 201);
    }

    // Одобрить отзыв (админ)
    public function approve($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $review = Review::find($id);
        if (!$review) {
            return response()->json(['success' => false, 'message' => 'Отзыв не найден'], 404);
        }

        $review->update(['is_approved' => true]);

        return response()->json(['success' => true, 'message' => 'Отзыв одобрен']);
    }

    // Удалить отзыв (админ или автор)
    public function destroy($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['success' => false, 'message' => 'Отзыв не найден'], 404);
        }

        // Гость может удалить только свой
        if (auth()->user()->role === 'guest' && $review->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $review->delete();
        return response()->json(['success' => true, 'message' => 'Отзыв удалён']);
    }

    // Рейтинг отеля
    public function hotelRating($hotelId)
    {
        $rating = Review::where('is_approved', 1)
            ->whereHas('room', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            })
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        return response()->json(['success' => true, 'data' => $rating]);
    }
}
