<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

trait RunsOrderAction
{
    private function runOrderAction(Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->validator->errors()->first(),
            ]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $success]);

        return back();
    }
}
