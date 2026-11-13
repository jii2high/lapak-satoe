<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->reports->create($request->user(), [
            ...$request->target(),
            'violation_type' => $data['violation_type'],
            'description' => $data['description'],
        ]);

        return back();
    }
}
