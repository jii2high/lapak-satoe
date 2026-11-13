<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\SellerApplicationRequest;
use App\Models\Category;
use App\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $applications) {}

    public function create(Request $request): Response
    {
        $user = $request->user();
        $application = $user->sellerApplication;

        return Inertia::render('seller/application', [
            'application' => $application === null ? null : [
                'status' => $application->status->value,
                'reject_note' => $application->reject_note,
            ],
            'is_seller' => $user->hasRole(Role::Seller),
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(SellerApplicationRequest $request): RedirectResponse
    {
        $this->applications->submit(
            $request->user(),
            $request->safe()->only(['full_name', 'nis', 'whatsapp', 'store_name', 'description', 'categories']),
            $request->file('product_photos'),
            $request->file('student_card'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pendaftaran terkirim. Admin akan meninjau.',
        ]);

        return redirect()->route('seller-application.create');
    }
}