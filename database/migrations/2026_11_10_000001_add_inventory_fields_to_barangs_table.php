<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->string('lokasi')->nullable()->after('deskripsi');          // lokasi rak / gudang
            $table->unsignedBigInteger('harga_beli')->default(0)->after('stok'); // rupiah
            $table->unsignedBigInteger('harga_jual')->default(0)->after('harga_beli');
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropColumn(['lokasi', 'harga_beli', 'harga_jual']);
        });
    }
};
