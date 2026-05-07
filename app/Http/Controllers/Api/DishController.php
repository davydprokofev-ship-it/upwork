<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DishController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Dish::where('available', 1)->get()
        ]);
    }

    public function show($id)
    {
        $dish = Dish::find($id);
        if (!$dish) {
            return response()->json(['success' => false, 'message' => 'Блюдо не найдено'], 404);
        }
        return response()->json(['success' => true, 'data' => $dish]);
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'calories' => 'nullable|integer',
            'weight' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $dish = Dish::create($validator->validated());
        return response()->json(['success' => true, 'data' => $dish], 201);
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }
        $dish = Dish::find($id);
        if (!$dish) {
            return response()->json(['success' => false, 'message' => 'Блюдо не найдено'], 404);
        }

        $dish->update($request->only([
            'name', 'price', 'category', 'calories', 'weight', 'description', 'image', 'available'
        ]));

        return response()->json(['success' => true, 'data' => $dish]);
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }
        $dish = Dish::find($id);
        if (!$dish) {
            return response()->json(['success' => false, 'message' => 'Блюдо не найдено'], 404);
        }

        $dish->delete();
        return response()->json(['success' => true, 'message' => 'Блюдо удалено']);
    }
}
