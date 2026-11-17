<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Ikon memakai Bootstrap Icons (SVG inline), bukan emoji.
 * Emoji tampil berbeda-beda di tiap perangkat dan tidak bisa diwarnai CSS.
 */
class IkonTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    /** Emoji yang dulu dipakai; tidak boleh muncul lagi. */
    private array $emojiLama = ['👥', '🎓', '🏫', '🗳', '📷', '📅', '💰', '🏛', '🪑', '👨‍👩‍👧', '👤', '🔒', '👁', '🙈', '⚠'];

    public function test_beranda_memakai_svg_bukan_emoji(): void
    {
        $this->seedPemilihanDraf();

        $html = $this->get('/')->assertOk()->getContent();

        foreach ($this->emojiLama as $emoji) {
            $this->assertStringNotContainsString($emoji, $html, "Emoji {$emoji} masih ada di beranda");
        }

        // Ikon statistik + 7 ikon fitur.
        $this->assertGreaterThanOrEqual(11, substr_count($html, '<svg'));
    }

    public function test_login_memakai_svg_bukan_emoji(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        foreach ($this->emojiLama as $emoji) {
            $this->assertStringNotContainsString($emoji, $html, "Emoji {$emoji} masih ada di login");
        }

        $this->assertGreaterThanOrEqual(5, substr_count($html, '<svg'));
    }

    public function test_komponen_ikon_merender_svg(): void
    {
        $html = $this->blade('<x-ikon nama="lock" ukuran="18" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('width="18"', $html);
        $this->assertStringContainsString('viewBox="0 0 16 16"', $html);
        $this->assertStringContainsString('<path', $html);
    }

    public function test_komponen_ikon_menerima_warna(): void
    {
        $html = $this->blade('<x-ikon nama="people" warna="#09656F" />');

        $this->assertStringContainsString('fill="#09656F"', $html);
    }

    public function test_komponen_ikon_aman_untuk_nama_tidak_dikenal(): void
    {
        // Nama salah tidak boleh membuat halaman error.
        $html = $this->blade('<x-ikon nama="tidak-ada" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('<path', $html);
    }

    public function test_semua_ikon_yang_dipakai_tersedia(): void
    {
        $dipakai = ['people', 'mortarboard', 'building', 'check2-square', 'qr-code-scan',
                    'calendar-week', 'cash-coin', 'diagram-3', 'grid-3x3', 'person-hearts',
                    'check-lg', 'person', 'lock', 'eye', 'eye-slash', 'exclamation-triangle'];

        foreach ($dipakai as $nama) {
            $html = $this->blade('<x-ikon nama="'.$nama.'" />');
            $this->assertStringContainsString('<path', $html, "Ikon {$nama} tidak punya path");
        }
    }
}
