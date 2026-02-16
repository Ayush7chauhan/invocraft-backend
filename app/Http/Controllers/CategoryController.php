<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $categories = Category::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $name = trim($request->name);
        if ($name === '') {
            return response()->json([
                'success' => false,
                'message' => 'Category name is required',
            ], 422);
        }

        $exists = Category::where('user_id', $user->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This category already exists',
            ], 422);
        }

        $category = Category::create([
            'user_id' => $user->id,
            'name' => $name,
        ]);

        return response()->json([
            'success' => true,
            'data' => $category,
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $category = Category::where('user_id', $user->id)->find($id);
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }
        $category->delete();
        return response()->json(['success' => true]);
    }
}
