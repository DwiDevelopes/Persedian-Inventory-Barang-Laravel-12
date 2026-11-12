<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\SesiAbsensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Absensi - sisi Super Admin: membuka/menutup sesi, memantau, mengubah status, dan rekap bulanan.
 */
class AbsensiController extends Controller
{
    private function pegawai()
    {
        return User::with('role')
            ->whereHas('role', fn ($q) => $q->where('role', '!=', 'superadmin'))
            ->orderBy('name')->get();
    }

    private static function jam(?string $t): ?string
    {
        return $t ? substr($t, 0, 5) : null;
    }

    public function index()
    {
        return view('absensi.index');
    }

    /** Sesi + daftar kehadiran pada satu tanggal. */
    public function getData(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());
        $sesi = SesiAbsensi::where('tanggal', $tanggal)->first();
        $rows = $sesi ? Absensi::where('sesi_absensi_id', $sesi->id)->get()->keyBy('user_id') : collect();

        $list = $this->pegawai()->map(function ($u) use ($rows) {
            $a = $rows->get($u->id);
            return [
                'user_id'    => $u->id,
                'nama'       => $u->name,
                'role'       => $u->role->role ?? '-',
                'status'     => $a->status ?? null,
                'jam_masuk'  => self::jam($a->jam_masuk ?? null),
                'jam_pulang' => self::jam($a->jam_pulang ?? null),
                'keterangan' => $a->keterangan ?? null,
            ];
        });

        $ringkasan = ['belum' => $list->whereNull('status')->count()];
        foreach (Absensi::STATUS as $s) {
            $ringkasan[$s] = $list->where('status', $s)->count();
        }

        return response()->json([
            'sesi' => $sesi ? [
                'id'              => $sesi->id,
                'tanggal'         => $sesi->tanggal,
                'status'          => $sesi->status,
                'jam_buka'        => self::jam($sesi->jam_buka),
                'batas_terlambat' => self::jam($sesi->batas_terlambat),
                'jam_tutup'       => self::jam($sesi->jam_tutup),
                'catatan'         => $sesi->catatan,
            ] : null,
            'rows'      => $list->values(),
            'ringkasan' => $ringkasan,
        ]);
    }

    /** Buka (atau buka kembali) absensi untuk suatu tanggal. */
    public function buka(Request $request)
    {
        $v = Validator::make($request->all(), [
            'tanggal'         => 'required|date',
            'batas_terlambat' => 'required|date_format:H:i',
            'jam_tutup'       => 'nullable|date_format:H:i',
            'catatan'         => 'nullable|string|max:255',
        ], [
            'batas_terlambat.required'    => 'Isi batas jam terlambat !',
            'batas_terlambat.date_format' => 'Format jam harus HH:MM !',
            'jam_tutup.date_format'       => 'Format jam harus HH:MM !',
        ]);
        if ($v->fails()) {
            return response()->json($v->errors(), 422);
        }

        $sesi = SesiAbsensi::firstOrNew(['tanggal' => $request->tanggal]);
        if (!$sesi->exists) {
            $sesi->jam_buka = now()->format('H:i:s');
            $sesi->dibuka_oleh = auth()->id();
        }
        $sesi->batas_terlambat = $request->batas_terlambat . ':00';
        $sesi->jam_tutup = $request->filled('jam_tutup') ? $request->jam_tutup . ':00' : null;
        $sesi->catatan = $request->catatan;
        $sesi->status = 'buka';
        $sesi->save();

        return response()->json(['success' => true, 'message' => 'Absensi dibuka. Pegawai sekarang bisa absen.']);
    }

    /** Tutup absensi; pegawai yang belum absen otomatis dicatat alpa. */
    public function tutup(SesiAbsensi $sesi)
    {
        DB::transaction(function () use ($sesi) {
            $sudah = Absensi::where('sesi_absensi_id', $sesi->id)->pluck('user_id')->all();
            foreach ($this->pegawai() as $u) {
                if (!in_array($u->id, $sudah)) {
                    Absensi::create([
                        'sesi_absensi_id' => $sesi->id, 'user_id' => $u->id, 'status' => 'alpa',
                        'keterangan' => 'Tidak absen', 'dicatat_oleh' => auth()->id(),
                    ]);
                }
            }
            $sesi->update(['status' => 'tutup']);
        });

        return response()->json(['success' => true, 'message' => 'Absensi ditutup. Yang belum absen dicatat alpa.']);
    }

    /** Ubah / isi status kehadiran seorang pegawai secara manual. */
    public function set(Request $request, SesiAbsensi $sesi)
    {
        $v = Validator::make($request->all(), [
            'user_id'    => 'required|exists:users,id',
            'status'     => 'required|in:' . implode(',', Absensi::STATUS),
            'jam_masuk'  => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'keterangan' => 'nullable|string|max:255',
        ], ['status.required' => 'Pilih status !', 'status.in' => 'Status tidak valid !']);
        if ($v->fails()) {
            return response()->json($v->errors(), 422);
        }

        $tanpaJam = in_array($request->status, ['izin', 'sakit', 'alpa']);
        Absensi::updateOrCreate(
            ['sesi_absensi_id' => $sesi->id, 'user_id' => $request->user_id],
            [
                'status'       => $request->status,
                'jam_masuk'    => $tanpaJam || !$request->filled('jam_masuk') ? null : $request->jam_masuk . ':00',
                'jam_pulang'   => $tanpaJam || !$request->filled('jam_pulang') ? null : $request->jam_pulang . ':00',
                'keterangan'   => $request->keterangan,
                'dicatat_oleh' => auth()->id(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Kehadiran diperbarui.']);
    }

    /** Rekap per bulan untuk tabel + grafik. */
    public function rekap(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        try {
            $awal = Carbon::createFromFormat('!Y-m', $bulan)->startOfMonth();
        } catch (\Throwable $e) {
            $awal = now()->startOfMonth();
        }
        $akhir = $awal->copy()->endOfMonth();

        $sesiIds = SesiAbsensi::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])->pluck('id');
        $abs = Absensi::whereIn('sesi_absensi_id', $sesiIds)->get()->groupBy('user_id');

        $rows = $this->pegawai()->map(function ($u) use ($abs) {
            $g = $abs->get($u->id, collect());
            $r = ['nama' => $u->name, 'role' => $u->role->role ?? '-'];
            foreach (Absensi::STATUS as $s) {
                $r[$s] = $g->where('status', $s)->count();
            }
            $hadirTotal = $r['hadir'] + $r['terlambat'];
            $r['persen'] = $sesiIds->count() ? round($hadirTotal / $sesiIds->count() * 100) : 0;
            return $r;
        });

        return response()->json([
            'bulan'      => $awal->format('Y-m'),
            'total_sesi' => $sesiIds->count(),
            'rows'       => $rows->values(),
        ]);
    }
}
