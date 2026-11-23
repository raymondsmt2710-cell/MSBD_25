@extends('layouts.akun')

@section('judul', 'Profil & Sandi')

@section('isi')

<div class="hero">
    <div class="hero-inner">
        <div class="lencana">
            <span class="dot"></span>
            Akun Saya
        </div>
        <h1>Profil &amp; <span>Sandi</span></h1>
        <p>Ubah sandi akun Anda di sini. Sandi lama tetap diperlukan sebagai pengaman.</p>
    </div>
</div>

<section style="padding-top:34px">
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Akun</div>
            <h2>Informasi Akun</h2>
        </div>

        <div class="kartu">
            <div class="data-grid">
                <div class="data-item">
                    <div class="kunci">Username</div>
                    <div class="nilai">{{ $pengguna->username }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Peran</div>
                    <div class="nilai">{{ $pengguna->role->label() }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Status Akun</div>
                    <div class="nilai">
                        <span class="pill">{{ $pengguna->is_active ? 'aktif' : 'nonaktif' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@if (! empty($dataDiri))
    <section>
        <div class="wrap">
            <div class="judul-bagian">
                <div class="kicker">Data Diri</div>
                <h2>Data Diri</h2>
                <p>Data ini dikelola administrator sekolah. Hubungi admin bila ada yang perlu diperbaiki.</p>
            </div>

            <div class="kartu">
                <div class="data-grid">
                    @foreach ($dataDiri as $kunci => $nilai)
                        <div class="data-item">
                            <div class="kunci">{{ $kunci }}</div>
                            <div class="nilai">
                                @if ($kunci === 'Status')
                                    <span class="pill">{{ $nilai ?: '—' }}</span>
                                @else
                                    {{ filled($nilai) ? $nilai : '—' }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif

<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Keamanan</div>
            <h2>Ganti Sandi</h2>
            <p>Minimal 8 karakter. Setelah berhasil, gunakan sandi baru pada login berikutnya.</p>
        </div>

        @if (session('success'))
            <div class="notif notif-sukses">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="notif notif-gagal">{{ session('error') }}</div>
        @endif

        <div class="kartu">
            <form method="POST" action="{{ route('akun.sandi.simpan') }}">
                @csrf
                @method('PUT')

                <div class="form-baris">
                    <label class="form-label" for="sandi_lama">Sandi Lama</label>
                    <input class="form-input @error('sandi_lama') form-input-salah @enderror"
                           type="password" id="sandi_lama" name="sandi_lama"
                           autocomplete="current-password" required>
                    @error('sandi_lama')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-baris">
                    <label class="form-label" for="sandi">Sandi Baru</label>
                    <input class="form-input @error('sandi') form-input-salah @enderror"
                           type="password" id="sandi" name="sandi"
                           autocomplete="new-password" minlength="8" required>
                    @error('sandi')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-baris">
                    <label class="form-label" for="sandi_confirmation">Ulangi Sandi Baru</label>
                    <input class="form-input" type="password" id="sandi_confirmation"
                           name="sandi_confirmation" autocomplete="new-password" minlength="8" required>
                </div>

                <div class="form-aksi">
                    <button class="btn" type="submit">
                        <x-ikon nama="lock" ukuran="15" /> Simpan Sandi Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="bagian-keluar">
    <div class="wrap">
        <div class="kartu">
            <p class="kosong" style="margin-bottom:12px">Selesai menggunakan akun ini?</p>
            <form method="POST" action="{{ route('logout') }}"
                  onsubmit="try{sessionStorage.removeItem('sidebar-admin-scroll')}catch(e){}">
                @csrf
                <button class="btn garis" type="submit">
                    <x-ikon nama="box-arrow-in-right" ukuran="15" /> Keluar
                </button>
            </form>
        </div>
    </div>
</section>

@endsection
