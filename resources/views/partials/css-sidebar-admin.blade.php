{{--
    CSS sidebar admin yang bisa dipakai ulang.

    Layout admin sudah punya aturan ini; layout lain (pemilihan, app-layout)
    menyertakan partial ini supaya sidebar tampil identik saat dibuka admin.
    Semua selektor dibatasi pada .kerangka-admin agar tidak menimpa gaya
    layout pemanggil.

    CATATAN: partial ini hanya berisi CSS. Skrip posisi gulir ada di
    partials/script-sidebar-admin.blade.php dan WAJIB disertakan di AKHIR <body>
    (setelah <aside> ada di DOM).
--}}
<style>
    .kerangka-admin{display:grid;grid-template-columns:240px 1fr;min-height:100vh}
    /* Sidebar menempel penuh setinggi layar. overflow-y:auto membuat gulirannya
       milik <aside> sendiri. Posisinya SENGAJA dipertahankan antar halaman
       (disimpan di sessionStorage) supaya menu yang sedang dibuka tidak
       melompat kembali ke atas saat berpindah halaman. */
    .kerangka-admin > aside{background:#043d44;color:#d7eef0;padding:18px 12px;position:sticky;top:0;height:100vh;overflow-y:auto;scroll-behavior:auto}
    .kerangka-admin > aside .brand{display:flex;align-items:center;gap:10px;padding:0 8px 16px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:10px}
    .kerangka-admin > aside .brand .nama{font-size:13px;font-weight:800;color:#fff;line-height:1.1}
    .kerangka-admin > aside .brand .sub{font-size:11px;color:#8fc3c8}
    .kerangka-admin .logo-sidebar{display:inline-flex;align-items:center;justify-content:center;background:#F9F5E9;border-radius:9px;padding:4px 6px;flex-shrink:0}
    .kerangka-admin .grup{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:#7fb1b6;font-weight:700;padding:14px 10px 6px}
    .kerangka-admin aside a.menu{display:block;padding:7px 10px;border-radius:9px;font-size:13px;color:#d7eef0}
    .kerangka-admin aside a.menu:hover{background:rgba(255,255,255,.07);color:#fff}
    .kerangka-admin aside a.menu.aktif{background:#09656F;color:#fff;font-weight:600}
    .kerangka-admin .isi-admin{min-width:0}
    @media(max-width:900px){
        .kerangka-admin{grid-template-columns:1fr}
        .kerangka-admin > aside{position:static;height:auto;max-height:none}
        .kerangka-admin > aside nav{display:flex;flex-wrap:wrap;gap:2px}
        .kerangka-admin .grup{display:none}
    }
</style>
