<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Beranda sekarang mengarahkan tamu ke halaman login (dulu menampilkan
     * halaman sambutan bawaan Laravel yang tidak punya tautan ke fitur apa pun).
     */
    public function test_the_application_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
