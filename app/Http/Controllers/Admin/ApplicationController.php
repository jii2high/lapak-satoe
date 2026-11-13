<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectApplicationRequest;
use App\Models\SellerApplication;
use App\Services\ApplicationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $applications) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/applications', $this->payload($request));
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
        $query = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $status = is_string($status) && in_array($status, ApplicationStatus::values(), true)
            ? $status
            : 'all';
        $pattern = '%'.addcslashes($query, '%_\\').'%';

        $applications = SellerApplication::query()
            ->with(['user:id,display_name,username', 'categories:id,name', 'files'])
            ->when($query !== '', fn (Builder $sub) => $sub->where(fn (Builder $words) => $words
                ->where('full_name', 'like', $pattern)
                ->orWhere('store_name', 'like', $pattern)
                ->orWhere('nis', 'like', $pattern)
                ->orWhereHas('user', fn (Builder $user) => $user
                    ->where('username', 'like', $pattern)
                    ->orWhere('display_name', 'like', $pattern))))
            ->when($status !== 'all', fn (Builder $sub) => $sub->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SellerApplication $application): array => [
                'id' => $application->id,
                'full_name' => $application->full_name,
                'nis' => $application->nis,
                'whatsapp' => $application->whatsapp,
                'store_name' => $application->store_name,
                'description' => $application->description,
                'status' => $application->status->value,
                'reject_note' => $application->reject_note,
                'submitted_at' => $application->created_at?->toIso8601String(),
                'applicant' => [
                    'display_name' => $application->user->display_name,
                    'username' => $application->user->username,
                ],
                'categories' => $application->categories->pluck('name')->values()->all(),
                'product_photos' => $application->files
                    ->where('type', 'product_photo')
                    ->map(fn ($file) => Storage::disk('public')->url($file->path))
                    ->values()
                    ->all(),
                'student_card_url' => route('admin.applications.card', $application),
            ]);

        return [
            'applications' => $applications,
            'filters' => [
                'q' => $query,
                'status' => $status,
            ],
        ];
    }

    public function approve(Request $request, SellerApplication $application): RedirectResponse
    {
        $this->applications->approve($application, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pendaftar {$application->full_name} diterima.",
        ]);

        return back();
    }

    public function reject(RejectApplicationRequest $request, SellerApplication $application): RedirectResponse
    {
        $this->applications->reject($application, $request->user(), $request->validated('reject_note'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pendaftaran {$application->store_name} ditolak.",
        ]);

        return back();
    }

    public function card(SellerApplication $application): StreamedResponse
    {
        $card = $application->files()->where('type', 'student_card')->firstOrFail();

        abort_unless(Storage::disk('local')->exists($card->path), 404);

        return Storage::disk('local')->response($card->path);
    }
}
