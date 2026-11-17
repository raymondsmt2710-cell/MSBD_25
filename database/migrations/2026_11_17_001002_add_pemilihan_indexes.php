<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Indexing (materi Optimasi Query & Indexing): hanya kolom yang benar-benar dipakai
// WHERE / JOIN / GROUP BY oleh function, view, dan procedure pemilihan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daftar_pemilih', function (Blueprint $table) {
            // Dipakai fn_partisipasi() dan hitung "sudah memilih".
            $table->index(['id_pemilihan', 'sudah_memilih'], 'idx_daftar_pemilih_status');
        });

        Schema::table('suara', function (Blueprint $table) {
            // Dipakai hitung perolehan suara per kandidat (view hasil & monitor).
            $table->index(['id_pemilihan', 'id_kandidat'], 'idx_suara_pemilihan_kandidat');
        });

        Schema::table('pemilihan', function (Blueprint $table) {
            // Dipakai daftar pemilihan aktif / yang perlu ditutup.
            $table->index(['status', 'waktu_mulai', 'waktu_selesai'], 'idx_pemilihan_status_waktu');
        });
    }

    public function down(): void
    {
        Schema::table('pemilihan', fn (Blueprint $t) => $t->dropIndex('idx_pemilihan_status_waktu'));
        Schema::table('suara', fn (Blueprint $t) => $t->dropIndex('idx_suara_pemilihan_kandidat'));
        Schema::table('daftar_pemilih', fn (Blueprint $t) => $t->dropIndex('idx_daftar_pemilih_status'));
    }
};
