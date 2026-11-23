export default function kasPage(initial) {
    return {
        ringkasan: initial.ringkasan,
        transaksi: initial.transaksi.data,
        pagination: initial.transaksi,
        matriks: initial.matriks,
        daftarKelas: initial.daftarKelas,
        anggota: initial.anggota,
        idKelas: initial.idKelas,
        bisaKelola: initial.bisaKelola,
        kategori: initial.kategori,
        tahun: initial.tahun,

        tab: 'transaksi',
        filter: {
            dari: initial.filter.dari ?? '',
            sampai: initial.filter.sampai ?? '',
            jenis: initial.filter.jenis ?? '',
            kategori: initial.filter.kategori ?? '',
            q: initial.filter.q ?? '',
            termasuk_batal: Boolean(initial.filter.termasuk_batal),
        },
        page: initial.filter.page ?? 1,

        modalBuka: false,
        modeEdit: false,
        form: { ...initial.formKosong, bukti: null },
        errors: {},
        menyimpan: false,
        memuat: false,
        toast: null,

        generate: {
            periode: new Date().toISOString().slice(0, 7),
            nominal: initial.matriks.nominal_default || 20000,
        },

        init() {
            this.$watch('filter', () => {
                this.page = 1;
                this.muatData();
            }, { deep: true });
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

        formatRp(nilai) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(Number(nilai) || 0);
        },

        async muatData() {
            this.memuat = true;
            try {
                const params = new URLSearchParams({
                    id_kelas: this.idKelas,
                    tahun: this.tahun,
                    page: this.page,
                    ...this.filter,
                });
                const res = await fetch(`/kas/data?${params.toString()}`, { headers: this.headers(false) });
                if (!res.ok) throw new Error('Gagal memuat data kas.');
                const data = await res.json();
                this.ringkasan = data.ringkasan;
                this.transaksi = data.transaksi.data;
                this.pagination = data.transaksi;
                this.matriks = data.matriks;
                this.anggota = data.anggota;
            } catch (error) {
                this.showToast(error.message, 'error');
            } finally {
                this.memuat = false;
            }
        },

        muatHalaman(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.page = page;
            this.muatData();
        },

        gantiKelas() {
            const url = new URL(window.location.href);
            url.searchParams.set('id_kelas', this.idKelas);
            window.location.href = url.toString();
        },

        resetFilter() {
            this.filter = { dari: '', sampai: '', jenis: '', kategori: '', q: '', termasuk_batal: false };
        },

        bukaTambah() {
            this.modeEdit = false;
            this.errors = {};
            this.form = { ...initial.formKosong, id_kelas: this.idKelas, bukti: null };
            this.modalBuka = true;
        },

        bukaEdit(transaksi) {
            this.modeEdit = true;
            this.errors = {};
            this.form = {
                id_transaksi: transaksi.id_transaksi,
                tanggal: transaksi.tanggal?.slice(0, 10) ?? '',
                jenis: transaksi.jenis,
                kategori: transaksi.kategori,
                nominal: transaksi.nominal,
                keterangan: transaksi.keterangan ?? '',
                id_siswa: transaksi.id_siswa ?? '',
                bukti: null,
            };
            this.modalBuka = true;
        },

        tutupModal() {
            if (this.menyimpan) return;
            this.modalBuka = false;
            this.errors = {};
        },

        pilihBukti(event) {
            this.form.bukti = event.target.files?.[0] ?? null;
        },

        error(field) {
            return this.errors[field]?.[0] ?? null;
        },

        async simpan() {
            this.errors = {};
            this.menyimpan = true;

            const data = new FormData();
            data.append('id_kelas', this.idKelas);
            data.append('tanggal', this.form.tanggal);
            data.append('jenis', this.form.jenis);
            data.append('kategori', this.form.kategori);
            data.append('nominal', this.form.nominal);
            data.append('keterangan', this.form.keterangan);
            data.append('id_siswa', this.form.id_siswa ?? '');
            if (this.form.bukti) data.append('bukti', this.form.bukti);
            if (this.modeEdit) data.append('_method', 'PUT');

            const url = this.modeEdit
                ? `/kas/transaksi/${this.form.id_transaksi}`
                : '/kas/transaksi';

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: this.headers(false),
                    body: data,
                });

                if (res.status === 422) {
                    const payload = await res.json();
                    this.errors = payload.errors ?? {};
                    this.showToast(payload.message ?? 'Periksa kembali form.', 'error');
                    return;
                }

                if (!res.ok) throw new Error('Gagal menyimpan transaksi.');

                this.modalBuka = false;
                this.showToast(this.modeEdit ? 'Transaksi diperbarui.' : 'Transaksi dicatat.');
                await this.muatData();
            } catch (error) {
                this.showToast(error.message, 'error');
            } finally {
                this.menyimpan = false;
            }
        },

        async batalkan(transaksi) {
            const alasan = window.prompt(`Batalkan transaksi "${transaksi.keterangan}"? Tulis alasan pembatalan:`);
            if (!alasan) return;

            // Kirim JSON, bukan FormData: headers(false) tidak menyertakan
            // Content-Type sehingga PHP tidak mengurai body FormData dan
            // `_method=DELETE` tidak pernah terbaca -> request jatuh ke route
            // POST /kas/transaksi (store) dan gagal validasi.
            try {
                const res = await fetch(`/kas/transaksi/${transaksi.id_transaksi}`, {
                    method: 'DELETE',
                    headers: this.headers(),
                    body: JSON.stringify({ alasan_batal: alasan }),
                });
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(payload.message ?? 'Gagal membatalkan transaksi.');
                this.showToast(payload.message ?? 'Transaksi dibatalkan.');
                await this.muatData();
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        async toggleIuran(siswa, bulan) {
            if (!this.bisaKelola) return;

            const cell = siswa.iuran[bulan];
            if (!cell) {
                this.showToast('Tagihan bulan ini belum dibuat. Klik "Buat tagihan".', 'error');
                return;
            }

            if (cell.status === 'terverifikasi') {
                if (!window.confirm(`Batalkan verifikasi iuran ${siswa.nama} bulan ${this.matriks.bulan[bulan]}?`)) return;

                try {
                    const res = await fetch(`/kas/iuran/${cell.id_iuran}/batal`, {
                        method: 'PATCH',
                        headers: this.headers(),
                    });
                    const payload = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(payload.message ?? 'Gagal membatalkan verifikasi.');
                    this.showToast(payload.message ?? 'Verifikasi dibatalkan.');
                    await this.muatData();
                } catch (error) {
                    this.showToast(error.message, 'error');
                }
                return;
            }

            if (!window.confirm(`Tandai iuran ${siswa.nama} bulan ${this.matriks.bulan[bulan]} sebagai lunas?`)) return;

            try {
                const res = await fetch(`/kas/iuran/${cell.id_iuran}/verifikasi`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({ status: 'terverifikasi' }),
                });
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(payload.message ?? 'Gagal memverifikasi iuran.');
                this.showToast(payload.message ?? 'Iuran diverifikasi.');
                await this.muatData();
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        async generateIuran() {
            try {
                const res = await fetch('/kas/iuran/generate', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({
                        id_kelas: this.idKelas,
                        periode: this.generate.periode,
                        nominal: this.generate.nominal,
                    }),
                });
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const pesan = payload.errors
                        ? Object.values(payload.errors).flat()[0]
                        : (payload.message ?? 'Gagal membuat tagihan.');
                    throw new Error(pesan);
                }
                this.showToast(payload.message ?? 'Tagihan dibuat.');
                await this.muatData();
            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        chipClass(status) {
            return {
                'belum_bayar': 'bg-gray-100 text-gray-500 border-gray-200',
                'menunggu': 'bg-amber-100 text-amber-700 border-amber-200',
                'terverifikasi': 'bg-emerald-100 text-emerald-700 border-emerald-200',
                'ditolak': 'bg-rose-100 text-rose-700 border-rose-200',
            }[status] ?? 'bg-gray-100 text-gray-400 border-gray-200';
        },

        statusLabel(status) {
            return {
                'belum_bayar': 'Belum',
                'menunggu': 'Menunggu',
                'terverifikasi': 'Lunas',
                'ditolak': 'Ditolak',
            }[status] ?? '—';
        },

        urlLaporan(path) {
            const params = new URLSearchParams({
                id_kelas: this.idKelas,
                tahun: this.tahun,
                ...this.filter,
            });
            return `${path}?${params.toString()}`;
        },

        showToast(pesan, tipe = 'success') {
            this.toast = { pesan, tipe };
            window.setTimeout(() => (this.toast = null), 3500);
        },
    };
}
