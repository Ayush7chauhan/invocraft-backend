<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/categories
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return $this->successResponse($categories, 'Categories retrieved successfully');
    }

    /**
     * POST /api/categories
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $name = trim($request->validated()['name']);

        // Case-insensitive duplicate guard (Rule::unique doesn't handle COLLATE)
        $exists = Category::where('user_id', $request->user()->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            return $this->validationErrorResponse(
                ['name' => ['This category already exists.']],
                'Validation failed'
            );
        }

        $category = Category::create([
            'user_id' => $request->user()->id,
            'name'    => $name,
        ]);

        return $this->createdResponse($category, 'Category created successfully');
    }

    /**
     * DELETE /api/categories/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $category = Category::where('user_id', $request->user()->id)->findOrFail($id);
        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully');
    }
}
