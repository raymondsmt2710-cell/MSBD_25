<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Komponen <x-app-layout> untuk halaman Kas Kelas, Denah, dan welcome.
 *
 * Class ini sempat terhapus saat pembersihan sisa Breeze, padahal file blade
 * resources/views/components/app-layout.blade.php masih dipakai. Tanpa class ini,
 * Blade gagal me-render komponen dan seluruh halaman tersebut error.
 */
class AppLayout extends Component
{
    public function __construct(public ?string $judul = null)
    {
    }

    public function render(): View
    {
        return view('components.app-layout');
    }
}
