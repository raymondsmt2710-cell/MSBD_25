<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    
    public function test_the_application_shows_landing_page_for_guest(): void
    {
        $this->get('/')->assertOk()->assertSee('SakuSaka');
    }
}
