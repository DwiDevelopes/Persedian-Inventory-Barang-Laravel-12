<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['barang_masuks', 'barang_keluars'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->string('kode_barang')->nullable()->index()->after('kode_transaksi');
            });
        }

        // Isi kode_barang untuk transaksi lama berdasarkan nama barang
        foreach (DB::table('barangs')->select('kode_barang', 'nama_barang')->get() as $b) {
            DB::table('barang_masuks')->where('nama_barang', $b->nama_barang)->whereNull('kode_barang')->update(['kode_barang' => $b->kode_barang]);
            DB::table('barang_keluars')->where('nama_barang', $b->nama_barang)->whereNull('kode_barang')->update(['kode_barang' => $b->kode_barang]);
        }
    }

    public function down(): void
    {
        foreach (['barang_masuks', 'barang_keluars'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn('kode_barang');
            });
        }
    }
};
