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

        // ---- Buka-tutup panel menu di layar sempit ----
        // Panel TIDAK diingat antar halaman: state terbuka sengaja tidak
        // disimpan, supaya setelah berpindah halaman menu kembali tertutup dan
        // konten tidak tertutup panel yang tidak diminta.
        var toggle = document.querySelector('.kerangka-admin .sidebar-toggle, .kerangka .sidebar-toggle');
        var nav = document.getElementById('menu-admin');

        if (toggle && nav && aside) {
            function setTerbuka(terbuka) {
                aside.classList.toggle('sidebar-terbuka', terbuka);
                toggle.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
            }

            toggle.addEventListener('click', function () {
                setTerbuka(! aside.classList.contains('sidebar-terbuka'));
            });

            // Escape menutup panel dan mengembalikan fokus ke tombolnya, supaya
            // pengguna keyboard tidak "tersesat" di dalam panel yang tersembunyi.
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && aside.classList.contains('sidebar-terbuka')) {
                    setTerbuka(false);
                    toggle.focus();
                }
            });
        }
    })();
</script>
