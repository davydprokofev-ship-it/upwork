<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    // Все номера (гости, админы, повара — все могут смотреть)
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Room::with('hotel')->get()
        ]);
    }

    // Свободные номера (для гостей)
    public function free()
    {
        return response()->json([
            'success' => true,
            'data' => Room::where('status', 'free')->with('hotel')->get()
        ]);
    }

    // Один номер
    public function show($id)
    {
        $room = Room::with('hotel')->find($id);
        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Номер не найден'], 404);
        }
        return response()->json(['success' => true, 'data' => $room]);
    }

    // Создать (админ)
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $validator = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'room_number' => 'required|string|max:10',
            'type' => 'required|string|max:100',
            'floor' => 'required|integer',
            'price_per_night' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $room = Room::create($validator->validated());
        return response()->json(['success' => true, 'data' => $room], 201);
    }

    // Обновить (админ)
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $room = Room::find($id);
        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Номер не найден'], 404);
        }

        $room->update($request->only([
            'hotel_id', 'room_number', 'type', 'floor', 'price_per_night', 'status'
        ]));

        return response()->json(['success' => true, 'data' => $room]);
    }

    // Удалить (админ)
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $room = Room::find($id);
        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Номер не найден'], 404);
        }

        $room->delete();
        return response()->json(['success' => true, 'message' => 'Номер удалён']);
    }
}
