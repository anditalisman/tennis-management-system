<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\CheckoutPackageRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\InvoiceItem;
use App\Models\Package;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use App\Services\InvoiceIssuer;
use Illuminate\Http\JsonResponse;

class PackageCheckoutController extends Controller
{
    public function __construct(private readonly InvoiceIssuer $invoiceIssuer) {}

    public function store(CheckoutPackageRequest $request, Package $package): JsonResponse
    {
        abort_unless($package->status === Package::STATUS_ACTIVE, 422, 'Paket ini sedang tidak tersedia untuk didaftarkan.');

        $participant = $this->resolveParticipant($request->user(), $request->validated('participant_id'));

        $invoice = $this->invoiceIssuer->issue(
            $participant,
            [['item_type' => InvoiceItem::TYPE_PACKAGE, 'package_id' => $package->id, 'qty' => 1]],
            $request->validated('voucher_code'),
            now()->addDays(3)->toDateString(),
            $request->user()->id,
        );

        return response()->json(['data' => new InvoiceResource($invoice->load('participant', 'items'))], 201);
    }

    private function resolveParticipant(User $user, ?string $participantUuid): Participant
    {
        if ($user->hasRole(Role::PARTICIPANT) && $user->participant) {
            return $user->participant;
        }

        if ($user->hasRole(Role::GUARDIAN) && $user->guardian) {
            abort_unless($participantUuid, 422, 'Pilih anak yang akan didaftarkan paketnya.');

            $participant = $user->guardian->participants()->where('participants.uuid', $participantUuid)->first();
            abort_unless($participant, 403, 'Peserta ini bukan anak yang terhubung ke akun Anda.');

            return $participant;
        }

        abort(403, 'Hanya peserta atau wali yang dapat mendaftar paket.');
    }
}
