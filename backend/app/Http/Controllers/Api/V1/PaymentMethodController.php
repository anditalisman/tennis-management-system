<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\ImageCompressor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly ImageCompressor $compressor) {}

    public function index(Request $request): JsonResponse
    {
        $canManage = $request->user()->hasPermission('payment-methods.manage');

        $methods = PaymentMethod::query()
            ->when(! $canManage, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return response()->json(['data' => PaymentMethodResource::collection($methods)]);
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $method = PaymentMethod::query()->create($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            $method->update(['image_path' => $this->storeImage($request, $method->id)]);
        }

        return response()->json(['data' => new PaymentMethodResource($method)], 201);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->fill($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            if ($paymentMethod->image_path) {
                Storage::disk('s3')->delete($paymentMethod->image_path);
            }
            $paymentMethod->image_path = $this->storeImage($request, $paymentMethod->id);
        }

        $paymentMethod->save();

        return response()->json(['data' => new PaymentMethodResource($paymentMethod)]);
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();

        return response()->json(['data' => ['message' => 'Metode pembayaran berhasil dihapus.']]);
    }

    private function storeImage(Request $request, int $paymentMethodId): string
    {
        $image = $request->file('image');
        $directory = 'payment-methods/'.$paymentMethodId;

        if ($this->compressor->isCompressible($image)) {
            $path = $directory.'/'.Str::random(40).'.'.strtolower((string) $image->getClientOriginalExtension());
            Storage::disk('s3')->put($path, $this->compressor->compress($image));

            return $path;
        }

        return $image->store($directory, 's3');
    }
}
