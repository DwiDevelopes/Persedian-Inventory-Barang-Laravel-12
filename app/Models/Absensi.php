<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    public const STATUS = ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'];

    protected $fillable = ['sesi_absensi_id', 'user_id', 'status', 'jam_masuk', 'jam_pulang', 'keterangan', 'dicatat_oleh'];

    public function sesi()
    {
        return $this->belongsTo(SesiAbsensi::class, 'sesi_absensi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
