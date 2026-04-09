<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/products
     * Query params: category_id, status, low_stock, search
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Product::where('user_id', $user->id)
            ->with(['productCategory:id,name', 'unit:id,name,short_name']);

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Legacy category string filter
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->boolean('low_stock')) {
            $query->whereRaw('stock_quantity <= low_stock_threshold');
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->get();

        return $this->successResponse($products, 'Products retrieved successfully');
    }

    /**
     * POST /api/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        $product = Product::create(array_merge($data, [
            'user_id'             => $request->user()->id,
            'stock_quantity'      => $data['stock_quantity']      ?? 0,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 10,
            'tax_rate'            => $data['tax_rate']            ?? 0,
            'status'              => $data['status']              ?? 'active',
        ]));

        return $this->createdResponse(
            $product->load(['productCategory:id,name', 'unit:id,name,short_name']),
            'Product created successfully'
        );
    }

    /**
     * GET /api/products/{product}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $product = Product::where('user_id', $request->user()->id)
            ->with(['productCategory:id,name', 'unit:id,name,short_name', 'stockMovements' => fn ($q) => $q->latest()->limit(20)])
            ->findOrFail($id);

        return $this->successResponse($product, 'Product retrieved successfully');
    }

    /**
     * PUT /api/products/{product}
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::where('user_id', $request->user()->id)->findOrFail($id);
        $product->update($request->validated());

        return $this->successResponse(
            $product->fresh()->load(['productCategory:id,name', 'unit:id,name,short_name']),
            'Product updated successfully'
        );
    }

    /**
     * DELETE /api/products/{product}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $product = Product::where('user_id', $request->user()->id)->findOrFail($id);
        $product->delete(); // soft delete

        return $this->successResponse(null, 'Product deleted successfully');
    }
}
