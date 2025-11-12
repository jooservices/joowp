<?php

declare(strict_types=1);

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_home_component(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()->assertInertia(
            fn (Assert $page): Assert => $page->component('Home')
        );
    }
}
