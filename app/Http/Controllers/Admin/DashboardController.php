<?php

namespace App\Http\Controllers\Admin;

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

        return Inertia::render('admin/dashboard', [
            'stats' => $this->dashboard->admin($range),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $range = $this->dashboard->normalizeRange($request->query('range'));

        return response()->json($this->dashboard->admin($range));
    }
}
