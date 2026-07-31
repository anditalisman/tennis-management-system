<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ScopesToBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use App\Services\InvoiceIssuer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ScopesToBranch;

    public function __construct(private readonly InvoiceIssuer $invoiceIssuer) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->visibleInvoices($request->user())
            ->with(['participant', 'items'])
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->string('participant_id')->isNotEmpty(), function ($query) use ($request) {
                $query->whereHas('participant', fn ($q) => $q->where('uuid', $request->string('participant_id')));
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => InvoiceResource::collection($invoices->items()),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $participant = Participant::query()->where('uuid', $request->validated('participant_id'))->firstOrFail();

        $invoice = $this->invoiceIssuer->issue(
            $participant,
            $request->validated('items'),
            $request->validated('voucher_code'),
            $request->validated('due_date'),
            $request->user()->id,
            (float) ($request->validated('discount_amount') ?? 0),
        );

        return response()->json(['data' => new InvoiceResource($invoice->load('participant', 'items'))], 201);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeView($request->user(), $invoice);

        return response()->json(['data' => new InvoiceResource($invoice->load('participant', 'items'))]);
    }

    private function visibleInvoices(User $user): Builder
    {
        if ($user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT) || $user->hasRole(Role::ADMINISTRATOR) || $user->hasRole(Role::FINANCE)) {
            return $this->scopeToStaffBranch(Invoice::query(), $user);
        }

        if ($user->hasRole(Role::PARTICIPANT) && $user->participant) {
            return Invoice::query()->where('participant_id', $user->participant->id);
        }

        if ($user->hasRole(Role::GUARDIAN) && $user->guardian) {
            $participantIds = $user->guardian->participants()->pluck('participants.id');

            return Invoice::query()->whereIn('participant_id', $participantIds);
        }

        abort(403, 'Anda tidak memiliki izin untuk mengakses sumber daya ini.');
    }

    public function authorizeView(User $user, Invoice $invoice): void
    {
        $allowed = $this->visibleInvoices($user)->whereKey($invoice->id)->exists();

        abort_unless($allowed, 403, 'Anda tidak memiliki izin untuk mengakses sumber daya ini.');
    }
}
