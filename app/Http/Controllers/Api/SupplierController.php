<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index()
    {
        if (!in_array(auth()->user()->role, ['admin', 'cook'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }
        return response()->json(['success' => true, 'data' => Supplier::all()]);
    }

    public function show($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'cook'])) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }
        $supplier = Supplier::find($id);
        return response()->json(['success' => true, 'data' => $supplier]);
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'inn' => 'nullable|string|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::create($validator->validated());
        return response()->json(['success' => true, 'data' => $supplier], 201);
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Поставщик не найден'], 404);
        }

        $supplier->update($request->only([
            'company_name', 'contact_person', 'phone', 'email', 'address', 'inn', 'status'
        ]));

        return response()->json(['success' => true, 'data' => $supplier]);
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
        }

        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Поставщик не найден'], 404);
        }

        $supplier->delete();
        return response()->json(['success' => true, 'message' => 'Поставщик удалён']);
    }
}
