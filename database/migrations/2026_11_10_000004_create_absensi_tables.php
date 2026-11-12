<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sesi absensi: dibuka & ditutup oleh super admin, satu sesi per tanggal
        Schema::create('sesi_absensis', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->time('jam_buka');
            $table->time('batas_terlambat');
            $table->time('jam_tutup')->nullable();
            $table->string('status')->default('buka'); // buka | tutup
            $table->string('catatan')->nullable();
            $table->foreignId('dibuka_oleh');
            $table->timestamps();
        });

        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_absensi_id')->constrained('sesi_absensis')->cascadeOnDelete();
            $table->foreignId('user_id');
            $table->string('status'); // hadir | terlambat | izin | sakit | alpa
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('keterangan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable(); // terisi bila diubah manual oleh super admin
            $table->timestamps();
            $table->unique(['sesi_absensi_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
        Schema::dropIfExists('sesi_absensis');
    }
};
