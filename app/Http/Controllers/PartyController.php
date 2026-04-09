<?php

namespace App\Http\Controllers;

use App\Http\Requests\Party\StorePartyRequest;
use App\Http\Requests\Party\UpdatePartyRequest;
use App\Models\Party;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartyController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/parties
     * Query params: type (customer|supplier|both), status, search
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Party::where('user_id', $user->id);

        if ($type = $request->query('type')) {
            if ($type === 'both') {
                $query->where('type', 'both');
            } elseif (in_array($type, ['customer', 'supplier'], true)) {
                $query->where(function ($q) use ($type) {
                    $q->where('type', $type)->orWhere('type', 'both');
                });
            }
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('status', $status);
            }
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $parties = $query->orderBy('name')->get();

        return $this->successResponse($parties, 'Parties retrieved successfully');
    }

    /**
     * POST /api/parties
     */
    public function store(StorePartyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $party = Party::create([
            'user_id'         => $request->user()->id,
            'name'            => $data['name'],
            'mobile'          => $data['mobile']          ?? null,
            'email'           => $data['email']           ?? null,
            'address'         => $data['address']         ?? null,
            'gst_number'      => $data['gst_number']      ?? null,
            'type'            => $data['type'],
            'opening_balance' => $data['opening_balance'] ?? 0,
            'status'          => $data['status']          ?? 'active',
        ]);

        return $this->createdResponse($party, 'Party created successfully');
    }

    /**
     * GET /api/parties/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $party = Party::where('user_id', $request->user()->id)
            ->withCount(['transactions', 'invoices', 'payments'])
            ->with(['transactions' => fn ($q) => $q->latest('transaction_date')->limit(10)])
            ->findOrFail($id);

        // Append computed balance
        $party->append('balance');

        return $this->successResponse($party, 'Party retrieved successfully');
    }

    /**
     * PUT /api/parties/{id}
     */
    public function update(UpdatePartyRequest $request, string $id): JsonResponse
    {
        $party = Party::where('user_id', $request->user()->id)->findOrFail($id);

        $party->update($request->validated());

        return $this->successResponse($party->fresh(), 'Party updated successfully');
    }

    /**
     * DELETE /api/parties/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $party = Party::where('user_id', $request->user()->id)
            ->withCount(['transactions', 'invoices', 'payments'])
            ->findOrFail($id);

        DB::transaction(function () use ($party) {
            $party->payments()->delete();
            $party->transactions()->delete();
            // Invoices: cascade delete items via DB constraint; soft-delete invoice
            $party->invoices()->each(function ($invoice) {
                $invoice->items()->delete();
                $invoice->delete();
            });
            $party->delete();
        });

        return $this->successResponse([
            'deleted_invoices'     => $party->invoices_count,
            'deleted_transactions' => $party->transactions_count,
            'deleted_payments'     => $party->payments_count,
        ], 'Party and all associated data deleted successfully');
    }
}
