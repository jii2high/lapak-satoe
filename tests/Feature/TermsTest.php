<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_terms_page_is_public_and_renders_the_markdown()
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('legal/terms')
                ->where('content', fn ($content) => str_contains($content, 'Syarat dan Ketentuan Lapak Satoe'))
                ->where('content', fn ($content) => str_contains($content, '<strong>hanya memfasilitasi</strong>')));
    }
}
