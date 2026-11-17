export default function denahPage(initial) {
    return {
        tab: 'kursi',
        modeEdit: false,

        idKelas: initial.idKelas,
        daftarKelas: initial.daftarKelas,
        bisaKelolaKursi: initial.bisaKelolaKursi,
        bisaKelolaRuangan: initial.bisaKelolaRuangan,

        denah: initial.denah,
        kursi: initial.kursi,
        siswa: initial.siswa,
        cariSiswa: '',
        sorotSiswaId: null,
        siswaTerpilih: null,
        kursiTerpilih: null,
        kursiSwapPertama: null,
        siswaAkanDitempatkan: '',
        drawerSiswa: false,
        pengaturanBuka: false,
        pengaturan: {
            jumlah_baris: initial.denah?.jumlah_baris ?? 6,
            jumlah_kolom: initial.denah?.jumlah_kolom ?? 4,
            posisi_meja_guru: initial.denah?.posisi_meja_guru ?? 'depan_tengah',
            posisi_pintu: initial.denah?.posisi_pintu ?? 'kanan',
        },

        ruangan: initial.ruangan,
        gedung: initial.gedung?.[0] ?? 'Gedung Utama',
        lantai: initial.lantai?.[0] ?? 1,
        cariRuangan: '',
        ruanganTerpilih: null,
        drawerRuangan: false,
        modalRuangan: false,
        formRuangan: { ...initial.formRuanganKosong },
        kategoriRuangan: initial.kategoriRuangan,
        statusRuangan: initial.statusRuangan,
        toast: null,

        init() {
            this.$watch('cariSiswa', () => this.sorotSiswa());
            this.$watch('cariRuangan', () => this.sorotRuangan());
        },

        csrf() {
            return document.querySelector('meta[name=csrf-token]').content;
        },

        headers(json = true) {
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrf(),
            };
            if (json) headers['Content-Type'] = 'application/json';
            return headers;
        },

        jumlahBaris() {
            return Number(this.denah?.jumlah_baris ?? 6);
        },

        jumlahKolom() {
            return Number(this.denah?.jumlah_kolom ?? 4);
        },

        kursiPada(baris, kolom) {
            return this.kursi.find((k) => Number(k.baris) === Number(baris) && Number(k.kolom) === Number(kolom)) ?? null;
        },

        siswaBelumDuduk() {
            const terisi = new Set(this.kursi.map((k) => k.siswa?.id_siswa).filter(Boolean));
            return this.siswa.filter((s) => !terisi.has(s.id_siswa));
        },

        gantiKelas() {
            const url = new URL(window.location.href);
            url.searchParams.set('id_kelas', this.idKelas);
            window.location.href = url.toString();
        },

        klikKursi(kursi, baris, kolom) {
            if (!this.modeEdit) {
                if (kursi) {
                    this.bukaSiswa(kursi);
                }
                return;
            }

            if (this.siswaAkanDitempatkan && !kursi) {
                this.tempatkan(baris, kolom);
                return;
            }

            if (!kursi) {
                if (this.kursiSwapPertama) {
                    this.pindah(this.kursiSwapPertama, baris, kolom);
                }
                return;
            }

            if (!this.kursiSwapPertama) {
                this.kursiSwapPertama = kursi;
                return;
            }

            if (this.kursiSwapPertama.id_posisi === kursi.id_posisi) {
                this.kursiSwapPertama = null;
                return;
            }

            this.swap(this.kursiSwapPertama, kursi);
        },

        bukaSiswa(kursi) {
            this.siswaTerpilih = kursi.siswa;
            this.kursiTerpilih = kursi;
            this.sorotSiswaId = kursi.siswa?.id_siswa ?? null;
            this.drawerSiswa = true;
        },

        tutupDrawerSiswa() {
            this.drawerSiswa = false;
            this.siswaTerpilih = null;
            this.sorotSiswaId = null;
        },

        async swap(a, b) {
            try {
                const res = await fetch(`/denah/kelas/${this.idKelas}/swap`, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({ posisi_a: a.id_posisi, posisi_b: b.id_posisi }),
                });
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(payload.message ?? 'Gagal menukar posisi.');
                this.kursi = payload.kursi ?? this.kursi;
                this.kursiSwapPertama = null;
                this.showToast(payload.message ?? 'Posisi ditukar.');
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        async pindah(kursi, baris, kolom) {
            try {
                const res = await fetch(`/denah/posisi/${kursi.id_posisi}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({ baris, kolom }),
                });
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(payload.message ?? 'Gagal memindahkan kursi.');
                this.kursiSwapPertama = null;
                await this.muatKursi();
                this.showToast(payload.message ?? 'Posisi dipindahkan.');
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        async tempatkan(baris, kolom) {
            try {
                const res = await fetch(`/denah/kelas/${this.idKelas}/tempatkan`, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({
                        id_siswa: Number(this.siswaAkanDitempatkan),
                        baris,
                        kolom,
                    }),
                });
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(payload.message ?? 'Gagal menempatkan siswa.');
                this.kursi = payload.kursi ?? this.kursi;
                this.siswaAkanDitempatkan = '';
                this.showToast(payload.message ?? 'Siswa ditempatkan.');
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        async acak() {
            if (!window.confirm('Acak posisi seluruh siswa? Posisi lama akan diganti.')) return;

            try {
                const res = await fetch(`/denah/kelas/${this.idKelas}/acak`, {
                    method: 'POST',
                    headers: this.headers(),
                });
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(payload.message ?? 'Gagal mengacak posisi.');
                this.kursi = payload.kursi ?? this.kursi;
                this.kursiSwapPertama = null;
                this.showToast(payload.message ?? 'Posisi diacak.');
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        async muatKursi() {
            const res = await fetch(`/denah/kelas/${this.idKelas}/data`, { headers: this.headers(false) });
            if (!res.ok) return;
            const data = await res.json();
            this.denah = data.denah;
            this.kursi = data.kursi;
            this.siswa = data.siswa;
            this.bisaKelolaKursi = data.bisa_kelola;
        },

        sorotSiswa() {
            const q = this.cariSiswa.toLowerCase().trim();
            if (!q) {
                this.sorotSiswaId = null;
                return;
            }

            const kursi = this.kursi.find((k) => k.siswa?.nama_siswa?.toLowerCase().includes(q));
            if (kursi) {
                this.bukaSiswa(kursi);
                return;
            }

            const belum = this.siswa.find((s) => s.nama_siswa?.toLowerCase().includes(q));
            if (belum) {
                this.sorotSiswaId = belum.id_siswa;
                this.siswaTerpilih = belum;
                this.siswaAkanDitempatkan = String(belum.id_siswa);
                this.drawerSiswa = true;
            }
        },

        bukaPengaturan() {
            this.pengaturan = {
                jumlah_baris: this.jumlahBaris(),
                jumlah_kolom: this.jumlahKolom(),
                posisi_meja_guru: this.denah?.posisi_meja_guru ?? 'depan_tengah',
                posisi_pintu: this.denah?.posisi_pintu ?? 'kanan',
            };
            this.pengaturanBuka = true;
        },

        async simpanDenah() {
            try {
                const res = await fetch(`/denah/kelas/${this.idKelas}`, {
                    method: 'PUT',
                    headers: this.headers(),
                    body: JSON.stringify(this.pengaturan),
                });
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const pesan = payload.errors ? Object.values(payload.errors).flat()[0] : (payload.message ?? 'Gagal menyimpan denah.');
                    throw new Error(pesan);
                }
                this.denah = payload.denah;
                this.kursi = payload.kursi ?? this.kursi;
                this.pengaturanBuka = false;
                this.showToast(payload.message ?? 'Denah disimpan.');
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        ruanganTampil() {
            const q = this.cariRuangan.toLowerCase().trim();

            return this.ruangan.filter((r) => {
                if (this.gedung && r.gedung !== this.gedung) return false;
                if (Number(r.lantai) !== Number(this.lantai)) return false;
                if (!q) return true;
                return r.nama_ruangan?.toLowerCase().includes(q) || r.kode_ruangan?.toLowerCase().includes(q);
            });
        },

        lantaiTersedia() {
            const lantai = this.ruangan
                .filter((r) => !this.gedung || r.gedung === this.gedung)
                .map((r) => Number(r.lantai));
            return [...new Set(lantai)].sort((a, b) => a - b);
        },

        gedungTersedia() {
            return [...new Set(this.ruangan.map((r) => r.gedung))].sort();
        },

        sorotRuangan() {
            const q = this.cariRuangan.toLowerCase().trim();
            if (!q) {
                this.ruanganTerpilih = null;
                return;
            }

            const ruangan = this.ruangan.find((r) =>
                r.nama_ruangan?.toLowerCase().includes(q) || r.kode_ruangan?.toLowerCase().includes(q),
            );

            if (ruangan) {
                this.gedung = ruangan.gedung;
                this.lantai = Number(ruangan.lantai);
                this.ruanganTerpilih = ruangan;
                this.drawerRuangan = true;
            }
        },

        klikRuangan(ruangan) {
            this.ruanganTerpilih = ruangan;
            this.drawerRuangan = true;
        },

        gayaRuangan(ruangan) {
            const sel = 34;
            return {
                left: `${Number(ruangan.posisi_x ?? 0) * sel}px`,
                top: `${Number(ruangan.posisi_y ?? 0) * sel}px`,
                width: `${Math.max(2, Number(ruangan.lebar_grid ?? 1)) * sel}px`,
                height: `${Math.max(2, Number(ruangan.tinggi_grid ?? 1)) * sel}px`,
            };
        },

        kelasRuangan(ruangan) {
            return {
                'tersedia': 'border-emerald-400 bg-emerald-50 text-emerald-900',
                'maintenance': 'border-amber-400 bg-amber-50 text-amber-900',
                'tutup': 'border-gray-300 bg-gray-100 text-gray-500',
            }[ruangan.status] ?? 'border-gray-300 bg-white';
        },

        async muatRuangan() {
            const res = await fetch('/denah/sekolah/data', { headers: this.headers(false) });
            if (!res.ok) return;
            const data = await res.json();
            this.ruangan = data.ruangan;
            if (!this.gedungTersedia().includes(this.gedung)) {
                this.gedung = this.gedungTersedia()[0] ?? '';
            }
            if (!this.lantaiTersedia().includes(Number(this.lantai))) {
                this.lantai = this.lantaiTersedia()[0] ?? 1;
            }
            this.ruanganTerpilih = this.ruanganTerpilih
                ? this.ruangan.find((r) => r.id_ruangan === this.ruanganTerpilih.id_ruangan) ?? null
                : null;
        },

        bukaRuangan(ruangan = null) {
            if (ruangan) {
                this.formRuangan = {
                    ...ruangan,
                    fasilitas: Array.isArray(ruangan.fasilitas) ? ruangan.fasilitas.join(', ') : '',
                };
            } else {
                this.formRuangan = { ...initial.formRuanganKosong };
            }
            this.modalRuangan = true;
        },

        tutupRuangan() {
            this.modalRuangan = false;
        },

        async simpanRuangan() {
            const payload = {
                ...this.formRuangan,
                lantai: Number(this.formRuangan.lantai),
                kapasitas: this.formRuangan.kapasitas ? Number(this.formRuangan.kapasitas) : null,
                posisi_x: Number(this.formRuangan.posisi_x),
                posisi_y: Number(this.formRuangan.posisi_y),
                lebar_grid: Number(this.formRuangan.lebar_grid),
                tinggi_grid: Number(this.formRuangan.tinggi_grid),
                fasilitas: String(this.formRuangan.fasilitas ?? '')
                    .split(',')
                    .map((item) => item.trim())
                    .filter(Boolean),
            };

            const edit = Boolean(payload.id_ruangan);
            const url = edit ? `/denah/ruangan/${payload.id_ruangan}` : '/denah/ruangan';

            try {
                const res = await fetch(url, {
                    method: edit ? 'PUT' : 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const pesan = data.errors ? Object.values(data.errors).flat()[0] : (data.message ?? 'Gagal menyimpan ruangan.');
                    throw new Error(pesan);
                }
                this.modalRuangan = false;
                await this.muatRuangan();
                this.showToast(data.message ?? 'Ruangan disimpan.');
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        async ubahStatus(ruangan, status) {
            try {
                const res = await fetch(`/denah/ruangan/${ruangan.id_ruangan}/status`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({ status }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message ?? 'Gagal mengubah status.');
                await this.muatRuangan();
                this.showToast(data.message ?? 'Status diperbarui.');
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        async hapusRuangan(ruangan) {
            if (!window.confirm(`Hapus ruangan ${ruangan.nama_ruangan}?`)) return;

            try {
                const res = await fetch(`/denah/ruangan/${ruangan.id_ruangan}`, {
                    method: 'DELETE',
                    headers: this.headers(),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message ?? 'Gagal menghapus ruangan.');
                this.drawerRuangan = false;
                await this.muatRuangan();
                this.showToast(data.message ?? 'Ruangan dihapus.');
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        showToast(pesan, tipe = 'success') {
            this.toast = { pesan, tipe };
            window.setTimeout(() => (this.toast = null), 3500);
        },
    };
}
