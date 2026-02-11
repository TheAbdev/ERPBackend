<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use App\Modules\ERP\Services\AccountingService;
use App\Modules\HR\Http\Requests\StorePayrollRequest;
use App\Modules\HR\Http\Requests\UpdatePayrollRequest;
use App\Modules\HR\Http\Resources\PayrollResource;
use App\Modules\HR\Models\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payroll::class);

        $query = Payroll::with(['employee'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $payrolls = $query->latest()->paginate();

        return PayrollResource::collection($payrolls);
    }

    public function store(StorePayrollRequest $request): JsonResponse
    {
        $this->authorize('create', Payroll::class);

        $payload = $request->validated();
        $payload['net_salary'] = $this->calculateNetSalary($payload);

        $payroll = Payroll::create($payload);

        event(new EntityCreated($payroll, $request->user()->id));

        return response()->json([
            'message' => 'Payroll created successfully.',
            'data' => new PayrollResource($payroll->load(['employee'])),
        ], 201);
    }

    public function show(Payroll $payroll): JsonResponse
    {
        $this->authorize('view', $payroll);

        return response()->json([
            'data' => new PayrollResource($payroll->load(['employee'])),
        ]);
    }

    public function update(UpdatePayrollRequest $request, Payroll $payroll): JsonResponse
    {
        $this->authorize('update', $payroll);

        $payload = $request->validated();
        $payload['net_salary'] = $this->calculateNetSalary(array_merge($payroll->toArray(), $payload));

        $payroll->update($payload);

        event(new EntityUpdated($payroll->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Payroll updated successfully.',
            'data' => new PayrollResource($payroll->load(['employee'])),
        ]);
    }

    public function destroy(Payroll $payroll): JsonResponse
    {
        $this->authorize('delete', $payroll);

        event(new EntityDeleted($payroll, request()->user()->id));

        $payroll->delete();

        return response()->json([
            'message' => 'Payroll deleted successfully.',
        ]);
    }

    public function approve(Request $request, Payroll $payroll): JsonResponse
    {
        $this->authorize('update', $payroll);

        if ($payroll->status !== 'draft') {
            return response()->json([
                'message' => 'Payroll is already approved or paid.',
            ], 422);
        }

        if (! $payroll->expense_account_id || ! $payroll->payable_account_id) {
            return response()->json([
                'message' => 'Expense and payable accounts are required to approve payroll.',
            ], 422);
        }

        $netSalary = $payroll->net_salary;

        if ($netSalary <= 0) {
            return response()->json([
                'message' => 'Net salary must be greater than zero.',
            ], 422);
        }

        $period = $this->accountingService->getActiveFiscalPeriod($payroll->period_end);
        $year = $this->accountingService->getActiveFiscalYear($payroll->period_end);

        DB::transaction(function () use ($payroll, $netSalary, $period, $year, $request) {
            $entry = JournalEntry::create([
                'tenant_id' => $payroll->tenant_id,
                'fiscal_year_id' => $year->id,
                'fiscal_period_id' => $period->id,
                'entry_date' => $payroll->period_end,
                'reference_type' => Payroll::class,
                'reference_id' => $payroll->id,
                'description' => 'Payroll posting',
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            $entry->lines()->create([
                'tenant_id' => $payroll->tenant_id,
                'account_id' => $payroll->expense_account_id,
                'currency_id' => null,
                'debit' => $netSalary,
                'credit' => 0,
                'amount_base' => $netSalary,
                'description' => 'Payroll expense',
                'line_number' => 1,
            ]);

            $entry->lines()->create([
                'tenant_id' => $payroll->tenant_id,
                'account_id' => $payroll->payable_account_id,
                'currency_id' => null,
                'debit' => 0,
                'credit' => $netSalary,
                'amount_base' => $netSalary,
                'description' => 'Payroll payable',
                'line_number' => 2,
            ]);

            $this->accountingService->postJournalEntry($entry, $request->user()->id);

            $payroll->update([
                'status' => 'approved',
                'journal_entry_id' => $entry->id,
            ]);
        });

        return response()->json([
            'message' => 'Payroll approved successfully.',
            'data' => new PayrollResource($payroll->fresh()->load(['employee'])),
        ]);
    }

    public function markPaid(Request $request, Payroll $payroll): JsonResponse
    {
        $this->authorize('update', $payroll);

        if ($payroll->status !== 'approved') {
            return response()->json([
                'message' => 'Payroll must be approved before marking as paid.',
            ], 422);
        }

        $payroll->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payroll marked as paid.',
            'data' => new PayrollResource($payroll->fresh()->load(['employee'])),
        ]);
    }

    protected function calculateNetSalary(array $payload): float
    {
        $base = (float) ($payload['base_salary'] ?? 0);
        $allowances = (float) ($payload['allowances'] ?? 0);
        $deductions = (float) ($payload['deductions'] ?? 0);

        return round($base + $allowances - $deductions, 2);
    }
}

