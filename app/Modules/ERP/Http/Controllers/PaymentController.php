<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\ApplyPaymentRequest;
use App\Modules\ERP\Http\Requests\StorePaymentRequest;
use App\Modules\ERP\Http\Resources\PaymentResource;
use App\Modules\ERP\Models\Payment;
use App\Modules\ERP\Services\AccountingService;
use App\Modules\ERP\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected AccountingService $accountingService;

    public function __construct(
        PaymentService $paymentService,
        AccountingService $accountingService
    ) {
        $this->paymentService = $paymentService;
        $this->accountingService = $accountingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with(['fiscalYear', 'fiscalPeriod', 'currency', 'creator', 'allocations.invoice'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('payment_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('payment_date', '<=', $request->input('date_to'));
        }

        $payments = $query->latest('payment_date')->paginate();

        return PaymentResource::collection($payments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\StorePaymentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['fiscal_year_id'] = $this->accountingService->getActiveFiscalYear($validated['payment_date'])->id;

        $payment = Payment::create($validated);

        // Dispatch entity created event
        event(new EntityCreated($payment, $request->user()->id));

        return response()->json([
            'message' => 'Payment created successfully.',
            'data' => new PaymentResource($payment->load(['fiscalYear', 'fiscalPeriod', 'currency', 'creator'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['fiscalYear', 'fiscalPeriod', 'currency', 'creator', 'allocations.invoice', 'reference']);

        return response()->json([
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        // Only allow updating if no allocations exist
        if ($payment->allocations()->exists()) {
            return response()->json([
                'message' => 'Cannot update payment with allocations. Reverse allocations first.',
            ], 422);
        }

        $validated = $request->validate([
            'payment_date' => ['sometimes', 'required', 'date'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment->update($validated);

        // Dispatch entity updated event
        event(new EntityUpdated($payment->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Payment updated successfully.',
            'data' => new PaymentResource($payment->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);

        if ($payment->allocations()->exists()) {
            return response()->json([
                'message' => 'Cannot delete payment with allocations. Reverse allocations first.',
            ], 422);
        }

        // Dispatch entity deleted event before deletion
        event(new EntityDeleted($payment, request()->user()->id));

        $payment->delete();

        return response()->json([
            'message' => 'Payment deleted successfully.',
        ]);
    }

    /**
     * Apply payment to invoices.
     *
     * @param  \App\Modules\ERP\Http\Requests\ApplyPaymentRequest  $request
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function apply(ApplyPaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        try {
            $this->paymentService->applyPayment($payment, $request->input('allocations'), $request->user()->id);

            return response()->json([
                'message' => 'Payment applied successfully.',
                'data' => new PaymentResource($payment->fresh()->load(['allocations.invoice'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reverse a payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function reverse(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        try {
            $this->paymentService->reversePayment($payment, $request->user()->id);

            return response()->json([
                'message' => 'Payment reversed successfully.',
                'data' => new PaymentResource($payment->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

