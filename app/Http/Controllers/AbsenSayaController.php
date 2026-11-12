<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\SesiAbsensi;
use Illuminate\Http\Request;

/**
 * Absensi - sisi pegawai (admin gudang / kepala gudang): absen masuk & pulang saat sesi dibuka super admin.
 */
class AbsenSayaController extends Controller
{
    public function index()
    {
        return view('absen-saya.index');
    }

    private static function jam(?string $t): ?string
    {
        return $t ? substr($t, 0, 5) : null;
    }

    public function status()
    {
        $sesi = SesiAbsensi::terbukaHariIni();
        $sesiHariIni = SesiAbsensi::where('tanggal', now()->toDateString())->first();
        $saya = $sesiHariIni ? Absensi::where('sesi_absensi_id', $sesiHariIni->id)->where('user_id', auth()->id())->first() : null;

        $riwayat = Absensi::with('sesi')->where('user_id', auth()->id())->latest('id')->limit(31)->get()->map(fn ($a) => [
            'tanggal'    => substr((string) $a->sesi->tanggal, 0, 10),
            'status'     => $a->status,
            'jam_masuk'  => self::jam($a->jam_masuk),
            'jam_pulang' => self::jam($a->jam_pulang),
            'keterangan' => $a->keterangan,
        ]);

        return response()->json([
            'terbuka'    => (bool) $sesi,
            'sesi'       => $sesiHariIni ? [
                'status'          => $sesi ? 'buka' : 'tutup',
                'jam_buka'        => self::jam($sesiHariIni->jam_buka),
                'batas_terlambat' => self::jam($sesiHariIni->batas_terlambat),
                'jam_tutup'       => self::jam($sesiHariIni->jam_tutup),
                'catatan'         => $sesiHariIni->catatan,
            ] : null,
            'saya'       => $saya ? [
                'status' => $saya->status, 'jam_masuk' => self::jam($saya->jam_masuk),
                'jam_pulang' => self::jam($saya->jam_pulang), 'keterangan' => $saya->keterangan,
            ] : null,
            'riwayat'    => $riwayat,
        ]);
    }

    public function masuk()
    {
        $sesi = SesiAbsensi::terbukaHariIni();
        if (!$sesi) {
            return response()->json(['message' => 'Absensi belum dibuka oleh Super Admin atau sudah ditutup.'], 422);
        }
        if (Absensi::where('sesi_absensi_id', $sesi->id)->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Anda sudah absen hari ini.'], 422);
        }

        $sekarang = now();
        $terlambat = $sekarang->format('H:i:s') > $sesi->batas_terlambat;
        Absensi::create([
            'sesi_absensi_id' => $sesi->id,
            'user_id'         => auth()->id(),
            'status'          => $terlambat ? 'terlambat' : 'hadir',
            'jam_masuk'       => $sekarang->format('H:i:s'),
        ]);

        return response()->json(['success' => true, 'message' => $terlambat ? 'Absen masuk tercatat (terlambat).' : 'Absen masuk tercatat. Selamat bekerja!']);
    }

    public function pulang()
    {
        $sesi = SesiAbsensi::where('tanggal', now()->toDateString())->first();
        $a = $sesi ? Absensi::where('sesi_absensi_id', $sesi->id)->where('user_id', auth()->id())->first() : null;

        if (!$a || !$a->jam_masuk) {
            return response()->json(['message' => 'Anda belum absen masuk hari ini.'], 422);
        }
        if ($a->jam_pulang) {
            return response()->json(['message' => 'Anda sudah absen pulang.'], 422);
        }

        $a->update(['jam_pulang' => now()->format('H:i:s')]);

        return response()->json(['success' => true, 'message' => 'Absen pulang tercatat. Hati-hati di jalan!']);
    }
}
