{{--
    Skrip posisi gulir sidebar admin.

    WAJIB disertakan di AKHIR <body>, bukan di <head>: skrip ini mencari elemen
    <aside>, dan pada saat <head> diproses <aside> belum ada di DOM sehingga
    skrip akan berhenti lebih awal (itulah sebabnya posisi gulir tidak pernah
    tersimpan).

    Perilaku: posisi gulir DISIMPAN dan DIPULIHKAN antar halaman, supaya admin
    yang sedang menelusuri menu di bagian bawah tidak perlu menggulir ulang.
--}}
<script>
    (function () {
        var kunci = 'sidebar-admin-scroll';
        // Dua bentuk sidebar: .kerangka (layouts/admin) dan .kerangka-admin
        // (dipasang di layout pemilihan & app-layout).
        var aside = document.querySelector('.kerangka > aside, .kerangka-admin > aside');
        if (!aside) return;

        var posisi = 0;
        try {
            var tersimpan = sessionStorage.getItem(kunci);
            if (tersimpan !== null) posisi = parseInt(tersimpan, 10) || 0;
        } catch (e) { /* mode privat */ }

        // Pemulihan diulang: tinggi sidebar belum tentu final saat skrip jalan
        // (font & logo masih dimuat), dan scrollTop diabaikan browser bila
        // scrollHeight masih <= clientHeight.
        var percobaan = 0;

        function pulihkan() {
            if (posisi <= 0) return;
            if (aside.scrollHeight > aside.clientHeight) {
                aside.scrollTop = posisi;
                if (Math.abs(aside.scrollTop - posisi) < 2) return;
            }
            if (++percobaan < 20) {
                requestAnimationFrame(pulihkan);
            }
        }

        pulihkan();
        window.addEventListener('load', pulihkan);
        setTimeout(pulihkan, 300);

        aside.addEventListener('scroll', function () {
            try { sessionStorage.setItem(kunci, String(aside.scrollTop)); } catch (e) { /* abaikan */ }
        }, { passive: true });
    })();
</script>
