<?php

namespace App\Http\Controllers;

use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Models\Unit;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/units
     */
    public function index(Request $request): JsonResponse
    {
        $units = Unit::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return $this->successResponse($units, 'Units retrieved successfully');
    }

    /**
     * POST /api/units
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $data = $request->validated();

        $unit = Unit::create([
            'user_id'    => $request->user()->id,
            'name'       => $data['name'],
            'short_name' => $data['short_name'],
        ]);

        return $this->createdResponse($unit, 'Unit created successfully');
    }

    /**
     * GET /api/units/{unit}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $unit = Unit::where('user_id', $request->user()->id)->findOrFail($id);

        return $this->successResponse($unit, 'Unit retrieved successfully');
    }

    /**
     * PUT /api/units/{unit}
     */
    public function update(UpdateUnitRequest $request, string $id): JsonResponse
    {
        $unit = Unit::where('user_id', $request->user()->id)->findOrFail($id);
        $unit->update($request->validated());

        return $this->successResponse($unit->fresh(), 'Unit updated successfully');
    }

    /**
     * DELETE /api/units/{unit}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $unit = Unit::where('user_id', $request->user()->id)->findOrFail($id);

        // Prevent deletion if products are using this unit
        if ($unit->products()->exists()) {
            return $this->errorResponse(
                'Cannot delete unit because it is assigned to one or more products.',
                409
            );
        }

        $unit->delete();

        return $this->successResponse(null, 'Unit deleted successfully');
    }
}
