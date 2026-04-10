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
     * Indian standard + traditional measurement units.
     *
     * Formula: 1 [this unit] = [conversion_factor] [base_unit]
     *
     * base_unit is the standard reference short_name for that type:
     *   mass   → kg
     *   volume → l
     *   count  → pcs
     *   length → m
     */
    private const DEFAULT_UNITS = [
        // ── Mass (base: kg) ───────────────────────────────────────────────────
        ['name' => 'Kilogram',   'short_name' => 'kg',    'type' => 'mass',   'base_unit' => 'kg',  'conversion_factor' => 1],
        ['name' => 'Gram',       'short_name' => 'g',     'type' => 'mass',   'base_unit' => 'kg',  'conversion_factor' => 0.001],
        ['name' => 'Quintal',    'short_name' => 'q',     'type' => 'mass',   'base_unit' => 'kg',  'conversion_factor' => 100],
        ['name' => 'Mann',       'short_name' => 'maan',  'type' => 'mass',   'base_unit' => 'kg',  'conversion_factor' => 40],    // 1 maan = 40 kg
        ['name' => 'Pav',        'short_name' => 'pav',   'type' => 'mass',   'base_unit' => 'kg',  'conversion_factor' => 0.25],  // 250g
        ['name' => 'Adhha',      'short_name' => 'adhha', 'type' => 'mass',   'base_unit' => 'kg',  'conversion_factor' => 0.5],   // 500g
        ['name' => 'Tola',       'short_name' => 'tola',  'type' => 'mass',   'base_unit' => 'kg',  'conversion_factor' => 0.01166], // 11.66g

        // ── Volume (base: l) ──────────────────────────────────────────────────
        ['name' => 'Litre',      'short_name' => 'l',     'type' => 'volume', 'base_unit' => 'l',   'conversion_factor' => 1],
        ['name' => 'Millilitre', 'short_name' => 'ml',    'type' => 'volume', 'base_unit' => 'l',   'conversion_factor' => 0.001],
        ['name' => 'Pav (250ml)','short_name' => 'pav-l', 'type' => 'volume', 'base_unit' => 'l',   'conversion_factor' => 0.25],  // quarter litre
        ['name' => 'Adhha (500ml)','short_name'=>'adh-l', 'type' => 'volume', 'base_unit' => 'l',   'conversion_factor' => 0.5],   // half litre

        // ── Count (base: pcs) ─────────────────────────────────────────────────
        ['name' => 'Piece',      'short_name' => 'pcs',   'type' => 'count',  'base_unit' => 'pcs', 'conversion_factor' => 1],
        ['name' => 'Dozen',      'short_name' => 'doz',   'type' => 'count',  'base_unit' => 'pcs', 'conversion_factor' => 12],
        ['name' => 'Gross',      'short_name' => 'grs',   'type' => 'count',  'base_unit' => 'pcs', 'conversion_factor' => 144],   // 12 dozen
        ['name' => 'Box',        'short_name' => 'box',   'type' => 'count',  'base_unit' => 'pcs', 'conversion_factor' => 1],     // user-defined qty
        ['name' => 'Packet',     'short_name' => 'pkt',   'type' => 'count',  'base_unit' => 'pcs', 'conversion_factor' => 1],

        // ── Length (base: m) ──────────────────────────────────────────────────
        ['name' => 'Meter',      'short_name' => 'm',     'type' => 'length', 'base_unit' => 'm',   'conversion_factor' => 1],
        ['name' => 'Centimeter', 'short_name' => 'cm',    'type' => 'length', 'base_unit' => 'm',   'conversion_factor' => 0.01],
        ['name' => 'Foot',       'short_name' => 'ft',    'type' => 'length', 'base_unit' => 'm',   'conversion_factor' => 0.3048],
        ['name' => 'Inch',       'short_name' => 'in',    'type' => 'length', 'base_unit' => 'm',   'conversion_factor' => 0.0254],
        ['name' => 'Gaj',        'short_name' => 'gaj',   'type' => 'length', 'base_unit' => 'm',   'conversion_factor' => 0.9144], // 1 gaj = 1 yard
        ['name' => 'Hath',       'short_name' => 'hath',  'type' => 'length', 'base_unit' => 'm',   'conversion_factor' => 0.4572], // 18 inches
    ];

    /**
     * GET /api/units
     */
    public function index(Request $request): JsonResponse
    {
        $units = Unit::where('user_id', $request->user()->id)
            ->orderByRaw("FIELD(type, 'mass','volume','count','length','other') ASC")
            ->orderBy('conversion_factor')
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
            'user_id'           => $request->user()->id,
            'name'              => $data['name'],
            'short_name'        => $data['short_name'],
            'type'              => $data['type']              ?? null,
            'base_unit'         => $data['base_unit']         ?? null,
            'conversion_factor' => $data['conversion_factor'] ?? null,
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

        if ($unit->products()->exists()) {
            return $this->errorResponse(
                'Cannot delete unit because it is assigned to one or more products.',
                409
            );
        }

        $unit->delete();

        return $this->successResponse(null, 'Unit deleted successfully');
    }

    /**
     * POST /api/units/seed-defaults
     * Creates all standard Indian units for the user (skips existing names).
     */
    public function seedDefaults(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $created = [];
        $skipped = [];

        foreach (self::DEFAULT_UNITS as $unitData) {
            $exists = Unit::where('user_id', $userId)
                ->where('name', $unitData['name'])
                ->exists();

            if ($exists) {
                $skipped[] = $unitData['name'];
                continue;
            }

            $created[] = Unit::create([...$unitData, 'user_id' => $userId]);
        }

        $message = count($created) > 0
            ? count($created) . ' default units added successfully.'
            : 'All default units already exist.';

        return $this->successResponse(
            ['created' => $created, 'skipped_count' => count($skipped)],
            $message
        );
    }
}
