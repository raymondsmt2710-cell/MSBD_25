{{--
    Menempatkan tautan navigasi tepat di tengah bar.

    Latar masalah: .nav-inner memakai flex + justify-content:space-between
    dengan tiga anak (merek, tautan, aksi). space-between hanya memusatkan anak
    tengah pada SISA RUANG di antara merek dan aksi, sehingga tautan bergeser
    ke kanan karena merek jauh lebih lebar daripada aksi (terukur +60px pada
    1280px).

    Perbaikan: grid dengan kolom kiri/kanan sama lebar, sehingga kolom tengah
    selalu berada di tengah bar. Kolom ditetapkan eksplisit agar halaman tanpa
    tautan nav (mis. /akun) tetap menaruh tombol profil di kanan.

    Semua selektor dibatasi pada .nav-inner agar tidak menimpa gaya layout
    pemanggil. Hanya berlaku di atas 640px; di bawahnya tautan sudah digantikan
    drawer, jadi tidak ada yang perlu dipusatkan.
--}}
<style>
    @media(min-width:641px){
        .nav-inner{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr)}
        .nav-inner > .brand{grid-column:1;justify-self:start;min-width:0}
        /* Tanpa min-width:0, teks merek yang panjang tidak bisa menyusut dan
           akan mendorong tautan tengah keluar dari posisinya. */
        .nav-inner > .brand > div{min-width:0}
        .nav-inner > .brand .nama{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .nav-inner > .nav-links{grid-column:2;justify-self:center}
        .nav-inner > .nav-aksi{grid-column:3;justify-self:end}
        /* Tombol profil khusus ponsel (ada di layout dashboard) tetap
           disembunyikan; letaknya di kolom mana pun tidak berpengaruh. */
        .nav-inner > .nav-profil{grid-column:3;justify-self:end}
    }

    /* Layar menengah: menu guru punya lima item, jadi ruang perlu dihemat agar
       tautan tidak menabrak merek. Tinggi bar tidak berubah. */
    @media(min-width:641px) and (max-width:900px){
        .nav-inner > .brand .sub{display:none}
    }
    @media(min-width:641px) and (max-width:760px){
        .nav-inner > .nav-links a{padding:7px 8px}
    }
</style>
