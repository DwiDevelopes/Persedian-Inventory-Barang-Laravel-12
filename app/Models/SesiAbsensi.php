<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesiAbsensi extends Model
{
    protected $fillable = ['tanggal', 'jam_buka', 'batas_terlambat', 'jam_tutup', 'status', 'catatan', 'dibuka_oleh'];

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'sesi_absensi_id');
    }

    /** Sesi hari ini yang masih menerima absen (status buka & belum lewat jam tutup). */
    public static function terbukaHariIni(): ?self
    {
        $sesi = static::where('tanggal', now()->toDateString())->where('status', 'buka')->first();
        if ($sesi && $sesi->jam_tutup && now()->format('H:i:s') > $sesi->jam_tutup) {
            return null;
        }
        return $sesi;
    }
}
