<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // Все заказы (админ, повар)
    public function index(Request $request)
    {
        $user = auth()->user();

        // Гость видит только свои
        if ($user->role === 'guest') {
            return response()->json([
                'success' => true,
                'data' => Order::where('user_id', $user->id)
                    ->with(['items.dish', 'room', 'hotel'])
                    ->latest()
                    ->get()
            ]);
        }

        // Админ и повар — все, с фильтром по статусу
        $query = Order::with(['items.dish', 'room', 'hotel', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }

    // Один заказ
    public function show($id)
    {
        $order = Order::with(['items.dish', 'room', 'hotel', 'user'])->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Заказ не найден'], 404);
        }

        // Гость видит только свои
        if (auth()->user()->role === 'guest' && $order->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        return response()->json(['success' => true, 'data' => $order]);
    }

    // Создать заказ (гость)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:room,id',
            'hotel_id' => 'required|exists:hotels,id',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Проверить, что номер существует и занят (или свободен — зависит от логики)
        $room = Room::find($request->room_id);
        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Номер не найден'], 404);
        }

        // Посчитать сумму
        $total = 0;
        foreach ($request->items as $item) {
            $dish = Dish::find($item['dish_id']);
            $total += $dish->price * $item['quantity'];
        }

        // Создать заказ
        $order = Order::create([
            'user_id' => auth()->id(),
            'room_id' => $request->room_id,
            'hotel_id' => $request->hotel_id,
            'status' => 'new',
            'total_price' => $total,
            'note' => $request->note,
        ]);

        // Создать позиции заказа
        foreach ($request->items as $item) {
            $dish = Dish::find($item['dish_id']);
            OrderItem::create([
                'order_id' => $order->id,
                'dish_id' => $item['dish_id'],
                'quantity' => $item['quantity'],
                'price' => $dish->price,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $order->load('items.dish')
        ], 201);
    }

    // Сменить статус (повар, админ)
    public function updateStatus(Request $request, $id)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['cook', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:cooking,ready,delivered',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Заказ не найден'], 404);
        }

        // Проверка перехода статусов
        $allowedTransitions = [
            'new' => ['cooking'],
            'cooking' => ['ready'],
            'ready' => ['delivered'],
        ];

        $currentStatus = $order->status;
        $newStatus = $request->status;

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return response()->json([
                'success' => false,
                'message' => "Нельзя перевести заказ из '$currentStatus' в '$newStatus'"
            ], 400);
        }

        $order->update(['status' => $newStatus]);

        return response()->json(['success' => true, 'data' => $order]);
    }

    // Отменить заказ (гость — только свой, админ — любой)
    public function cancel($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Заказ не найден'], 404);
        }

        if (auth()->user()->role === 'guest' && $order->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        if (!in_array($order->status, ['new'])) {
            return response()->json(['success' => false, 'message' => 'Можно отменить только новый заказ'], 400);
        }

        $order->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Заказ отменён']);
    }

    // Заказы для кухни (новые + готовятся)
    public function kitchen(Request $request)
    {
        if (!in_array(auth()->user()->role, ['cook', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $query = Order::whereIn('status', ['new', 'cooking'])
            ->with(['items.dish', 'room', 'hotel'])
            ->latest();

        if ($request->has('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }
}
