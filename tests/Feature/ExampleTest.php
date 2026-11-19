<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Beranda sekarang menampilkan landing page untuk tamu (dulu mengarahkan ke login).
     * Pengguna yang sudah login tetap diarahkan sesuai peran.
     */
    public function test_the_application_shows_landing_page_for_guest(): void
    {
        $this->get('/')->assertOk()->assertSee('SakuSaka');
    }
}
