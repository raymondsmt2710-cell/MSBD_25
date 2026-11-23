<script>
    (function () {
        var kunci = 'sidebar-admin-scroll';

        var aside = document.querySelector('.kerangka > aside, .kerangka-admin > aside');
        if (!aside) return;

        var posisi = 0;
        try {
            var tersimpan = sessionStorage.getItem(kunci);
            if (tersimpan !== null) posisi = parseInt(tersimpan, 10) || 0;
        } catch (e) {  }

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
            try { sessionStorage.setItem(kunci, String(aside.scrollTop)); } catch (e) {  }
        }, { passive: true });

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

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && aside.classList.contains('sidebar-terbuka')) {
                    setTerbuka(false);
                    toggle.focus();
                }
            });
        }
    })();
</script>
