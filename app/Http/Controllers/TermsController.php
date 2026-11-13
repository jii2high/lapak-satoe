<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TermsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('legal/terms', [
            'content' => Str::markdown(
                File::get(resource_path('content/S&K.md')),
                [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ],
            ),
        ]);
    }
}