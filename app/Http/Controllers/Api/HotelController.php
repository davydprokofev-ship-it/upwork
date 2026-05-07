<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HotelController extends Controller
{
    // Все отели (публично)
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Hotel::withCount('rooms')->get()
        ]);
    }

    // Один отель
    public function show($id)
    {
        $hotel = Hotel::with(['rooms', 'orders'])->find($id);
        if (!$hotel) {
            return response()->json(['success' => false, 'message' => 'Отель не найден'], 404);
        }
        return response()->json(['success' => true, 'data' => $hotel]);
    }

    // Создать (админ)
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $validator = Validator::make($request->all(), [
            'city' => 'required|string|max:100',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $hotel = Hotel::create($validator->validated());
        return response()->json(['success' => true, 'data' => $hotel], 201);
    }

    // Обновить (админ)
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $hotel = Hotel::find($id);
        if (!$hotel) {
            return response()->json(['success' => false, 'message' => 'Отель не найден'], 404);
        }

        $hotel->update($request->only(['city', 'address', 'phone', 'email']));

        return response()->json(['success' => true, 'data' => $hotel]);
    }

    // Удалить (админ)
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $hotel = Hotel::find($id);
        if (!$hotel) {
            return response()->json(['success' => false, 'message' => 'Отель не найден'], 404);
        }

        $hotel->delete();
        return response()->json(['success' => true, 'message' => 'Отель удалён']);
    }

    // Номера в отеле
    public function rooms($id)
    {
        $hotel = Hotel::find($id);
        if (!$hotel) {
            return response()->json(['success' => false, 'message' => 'Отель не найден'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $hotel->rooms()->get()
        ]);
    }
}
