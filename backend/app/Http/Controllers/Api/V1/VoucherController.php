<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StoreVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vouchers = Voucher::query()
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => VoucherResource::collection($vouchers->items()),
            'meta' => [
                'current_page' => $vouchers->currentPage(),
                'last_page' => $vouchers->lastPage(),
                'per_page' => $vouchers->perPage(),
                'total' => $vouchers->total(),
            ],
        ]);
    }

    public function store(StoreVoucherRequest $request): JsonResponse
    {
        $voucher = Voucher::query()->create($request->validated());

        return response()->json(['data' => new VoucherResource($voucher)], 201);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string'], 'subtotal' => ['required', 'numeric', 'min:0']]);

        $voucher = Voucher::query()->where('code', $request->string('code'))->first();

        abort_if(! $voucher || ! $voucher->isUsable(), 422, 'Kode voucher tidak valid atau sudah tidak berlaku.');

        return response()->json(['data' => [
            'code' => $voucher->code,
            'discount' => $voucher->calculateDiscount((float) $request->input('subtotal')),
        ]]);
    }
}
