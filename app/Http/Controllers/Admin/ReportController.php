<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessReportRequest;
use App\Http\Requests\Admin\RejectReportRequest;
use App\Models\Report;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/reports', $this->payload($request));
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->payload($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $violations = config('lapak.violation_types');
        $query = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $status = is_string($status) && in_array($status, ReportStatus::values(), true)
            ? $status
            : 'all';
        $pattern = '%'.addcslashes($query, '%_\\').'%';

        $reports = Report::query()
            ->with([
                'reporter:id,username',
                'reportedUser:id,username',
                'handler:id,username',
                'product:id,name',
                'store:id,name',
                'order:id,code',
            ])
            ->when($query !== '', fn (Builder $sub) => $sub->where(fn (Builder $words) => $words
                ->whereHas('reporter', fn (Builder $user) => $user->where('username', 'like', $pattern))
                ->orWhereHas('reportedUser', fn (Builder $user) => $user->where('username', 'like', $pattern))
                ->orWhereHas('product', fn (Builder $product) => $product->where('name', 'like', $pattern))
                ->orWhereHas('store', fn (Builder $store) => $store->where('name', 'like', $pattern))
                ->orWhereHas('order', fn (Builder $order) => $order->where('code', 'like', $pattern))))
            ->when($status !== 'all', fn (Builder $sub) => $sub->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Report $report): array => [
                'id' => $report->id,
                'violation_label' => $violations[$report->violation_type] ?? $report->violation_type,
                'description' => $report->description,
                'status' => $report->status->value,
                'target' => $report->targetLabel(),
                'target_kind' => $report->product_id !== null ? 'product' : 'store',
                'reporter' => $report->reporter?->username,
                'reported_user' => $report->reportedUser?->username,
                'order_code' => $report->order?->code,
                'reporter_note' => $report->reporter_note,
                'reported_note' => $report->reported_note,
                'handled_by' => $report->handler?->username,
                'handled_at' => $report->handled_at?->toIso8601String(),
                'restored_at' => $report->restored_at?->toIso8601String(),
                'created_at' => $report->created_at?->toIso8601String(),
            ]);

        return [
            'reports' => $reports,
            'filters' => [
                'q' => $query,
                'status' => $status,
            ],
        ];
    }

    public function process(ProcessReportRequest $request, Report $report): RedirectResponse
    {
        try {
            $this->reports->process($report, $request->user(), $request->validated('note'));
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->validator->errors()->first(),
            ]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Laporan diproses dan objek terlapor dinonaktifkan.']);

        return back();
    }

    public function reject(RejectReportRequest $request, Report $report): RedirectResponse
    {
        try {
            $this->reports->reject($report, $request->user(), $request->validated('note'));
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->validator->errors()->first(),
            ]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Laporan ditolak.']);

        return back();
    }

    public function restore(Report $report): RedirectResponse
    {
        try {
            $this->reports->restore($report);
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->validator->errors()->first('status'),
            ]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Objek terlapor dipulihkan.']);

        return back();
    }

    public function pdf(Request $request, Report $report): HttpResponse
    {
        $report->load(['reporter:id,username', 'reportedUser:id,username', 'handler:id,username', 'product:id,name', 'store:id,name', 'order:id,code']);

        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'violation' => config('lapak.violation_types')[$report->violation_type] ?? $report->violation_type,
            'printedAt' => now(),
        ]);

        $name = "laporan-{$report->id}.pdf";

        return $request->boolean('print') ? $pdf->stream($name) : $pdf->download($name);
    }
}
