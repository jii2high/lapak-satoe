<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): Response
    {
        $range = $this->dashboard->normalizeRange($request->query('range'));

        return Inertia::render('seller/dashboard', [
            'stats' => $this->dashboard->seller($request->user()->store()->firstOrFail(), $range),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $range = $this->dashboard->normalizeRange($request->query('range'));

        return response()->json(
            $this->dashboard->seller($request->user()->store()->firstOrFail(), $range),
        );
    }
}
