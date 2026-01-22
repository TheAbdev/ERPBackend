<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\PaymentGateway;
use App\Modules\ERP\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends Controller
{
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Display a listing of payment gateways.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PaymentGateway::class);

        $query = PaymentGateway::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $gateways = $query->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate();

        return \App\Modules\ERP\Http\Resources\PaymentGatewayResource::collection($gateways);
    }

    /**
     * Store a newly created payment gateway.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', PaymentGateway::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['stripe', 'paypal', 'bank_transfer'])],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'credentials' => ['required', 'array'],
            'settings' => ['sometimes', 'array'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = $request->input('is_active', true);
        $validated['is_default'] = $request->input('is_default', false);

        $gateway = PaymentGateway::create($validated);

        return response()->json([
            'message' => 'Payment gateway created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\PaymentGatewayResource($gateway),
        ], 201);
    }

    /**
     * Display the specified payment gateway.
     *
     * @param  \App\Modules\ERP\Models\PaymentGateway  $paymentGateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('view', $paymentGateway);

        return response()->json([
            'data' => new \App\Modules\ERP\Http\Resources\PaymentGatewayResource($paymentGateway),
        ]);
    }

    /**
     * Update the specified payment gateway.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\PaymentGateway  $paymentGateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('update', $paymentGateway);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'credentials' => ['sometimes', 'array'],
            'settings' => ['sometimes', 'array'],
            'description' => ['nullable', 'string'],
        ]);

        $paymentGateway->update($validated);

        return response()->json([
            'message' => 'Payment gateway updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\PaymentGatewayResource($paymentGateway->fresh()),
        ]);
    }

    /**
     * Remove the specified payment gateway.
     *
     * @param  \App\Modules\ERP\Models\PaymentGateway  $paymentGateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('delete', $paymentGateway);

        $paymentGateway->delete();

        return response()->json([
            'message' => 'Payment gateway deleted successfully.',
        ]);
    }

    /**
     * Process payment through gateway.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\PaymentGateway  $paymentGateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayment(Request $request, PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('view', $paymentGateway);

        $validated = $request->validate([
            'payment_id' => ['required', 'exists:payments,id'],
            'options' => ['sometimes', 'array'],
        ]);

        $payment = \App\Modules\ERP\Models\Payment::findOrFail($validated['payment_id']);

        try {
            $result = $this->paymentGatewayService->processPayment(
                $payment,
                $paymentGateway,
                $validated['options'] ?? []
            );

            return response()->json([
                'message' => 'Payment processed successfully.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
















