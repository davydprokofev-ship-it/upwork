<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // Все продукты (админ, повар)
    public function index(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'cook'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $query = Product::with('supplier');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    // Один продукт
    public function show($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'cook'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $product = Product::with('supplier', 'dishes')->find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Продукт не найден'], 404);
        }

        return response()->json(['success' => true, 'data' => $product]);
    }

    // Создать (админ)
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:150',
            'category' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:20',
            'price_per_unit' => 'required|numeric|min:0',
            'quantity_in_stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $product = Product::create($validator->validated());
        return response()->json(['success' => true, 'data' => $product], 201);
    }

    // Обновить (админ)
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Продукт не найден'], 404);
        }

        $product->update($request->only([
            'supplier_id', 'name', 'category', 'unit',
            'price_per_unit', 'quantity_in_stock', 'min_stock', 'status'
        ]));

        // Автоматически обновить статус по остатку
        if ($product->quantity_in_stock <= 0) {
            $product->update(['status' => 'out_of_stock']);
        } elseif ($product->quantity_in_stock <= $product->min_stock) {
            $product->update(['status' => 'low']);
        } else {
            $product->update(['status' => 'in_stock']);
        }

        return response()->json(['success' => true, 'data' => $product->fresh()]);
    }

    // Удалить (админ)
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Продукт не найден'], 404);
        }

        $product->delete();
        return response()->json(['success' => true, 'message' => 'Продукт удалён']);
    }

    // Продукты с низким запасом (для заказа у поставщика)
    public function lowStock()
    {
        if (!in_array(auth()->user()->role, ['admin', 'cook'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $lowStock = Product::where('status', '!=', 'in_stock')
            ->with('supplier')
            ->get();

        return response()->json(['success' => true, 'data' => $lowStock]);
    }

    // Категории продуктов
    public function categories()
    {
        $categories = Product::select('category')->distinct()->pluck('category');
        return response()->json(['success' => true, 'data' => $categories]);
    }
}
