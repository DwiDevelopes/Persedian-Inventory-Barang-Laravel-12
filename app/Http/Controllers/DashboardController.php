<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard: ringkasan persediaan + data untuk semua grafik.
     */
    public function index()
    {
        $barangs = Barang::with(['jenis', 'satuan'])->get();

        // ---- KPI ----
        $kpi = [
            'barang'         => $barangs->count(),
            'total_stok'     => (int) $barangs->sum('stok'),
            'nilai'          => (int) $barangs->sum(fn ($b) => (int) $b->stok * (int) $b->harga_beli),
            'nilai_jual'     => (int) $barangs->sum(fn ($b) => (int) $b->stok * (int) $b->harga_jual),
            'menipis'        => $barangs->filter(fn ($b) => $b->status_stok === 'menipis')->count(),
            'habis'          => $barangs->filter(fn ($b) => $b->status_stok === 'habis')->count(),
            'masuk_bulan'    => (int) BarangMasuk::whereYear('tanggal_masuk', now()->year)->whereMonth('tanggal_masuk', now()->month)->sum('jumlah_masuk'),
            'keluar_bulan'   => (int) BarangKeluar::whereYear('tanggal_keluar', now()->year)->whereMonth('tanggal_keluar', now()->month)->sum('jumlah_keluar'),
        ];

        // ---- Masuk & keluar per bulan (12 bulan terakhir) ----
        $masukBulan = BarangMasuk::selectRaw('DATE_FORMAT(tanggal_masuk, "%Y-%m") as bulan, SUM(jumlah_masuk) as total')
            ->groupBy('bulan')->pluck('total', 'bulan');
        $keluarBulan = BarangKeluar::selectRaw('DATE_FORMAT(tanggal_keluar, "%Y-%m") as bulan, SUM(jumlah_keluar) as total')
            ->groupBy('bulan')->pluck('total', 'bulan');
        $months = $masukBulan->keys()->merge($keluarBulan->keys())->unique()->sort()->values()->take(-12);

        $bulanan = [
            'labels' => $months->map(fn ($m) => Carbon::createFromFormat('!Y-m', $m)->locale('id')->translatedFormat('M Y'))->all(),
            'masuk'  => $months->map(fn ($m) => (int) ($masukBulan[$m] ?? 0))->all(),
            'keluar' => $months->map(fn ($m) => (int) ($keluarBulan[$m] ?? 0))->all(),
        ];

        // ---- Tren harian 30 hari ----
        $mulai = now()->subDays(29)->startOfDay();
        $masukHari = BarangMasuk::where('tanggal_masuk', '>=', $mulai->toDateString())
            ->selectRaw('tanggal_masuk as tgl, SUM(jumlah_masuk) as total')->groupBy('tgl')->pluck('total', 'tgl');
        $keluarHari = BarangKeluar::where('tanggal_keluar', '>=', $mulai->toDateString())
            ->selectRaw('tanggal_keluar as tgl, SUM(jumlah_keluar) as total')->groupBy('tgl')->pluck('total', 'tgl');

        $harian = ['labels' => [], 'masuk' => [], 'keluar' => []];
        for ($i = 0; $i < 30; $i++) {
            $d = $mulai->copy()->addDays($i);
            $key = $d->toDateString();
            $harian['labels'][] = $d->format('d/m');
            $harian['masuk'][]  = (int) ($masukHari->first(fn ($v, $k) => substr($k, 0, 10) === $key) ?? 0);
            $harian['keluar'][] = (int) ($keluarHari->first(fn ($v, $k) => substr($k, 0, 10) === $key) ?? 0);
        }

        // ---- Status stok (doughnut) ----
        $status = [
            'labels' => ['Aman', 'Menipis', 'Habis'],
            'data'   => [
                $barangs->filter(fn ($b) => $b->status_stok === 'aman')->count(),
                $kpi['menipis'],
                $kpi['habis'],
            ],
        ];

        // ---- Stok & nilai persediaan per jenis ----
        $perJenis = $barangs->groupBy(fn ($b) => $b->jenis->jenis_barang ?? 'Tanpa Jenis')->map(fn ($g) => [
            'stok'  => (int) $g->sum('stok'),
            'nilai' => (int) $g->sum(fn ($b) => (int) $b->stok * (int) $b->harga_beli),
        ])->sortByDesc('stok');
        $jenis = [
            'labels' => $perJenis->keys()->all(),
            'stok'   => $perJenis->pluck('stok')->all(),
            'nilai'  => $perJenis->pluck('nilai')->all(),
        ];

        // ---- Top 5 barang keluar (30 hari) & top 5 barang masuk ----
        $topKeluar = BarangKeluar::where('tanggal_keluar', '>=', $mulai->toDateString())
            ->selectRaw('nama_barang, SUM(jumlah_keluar) as total')->groupBy('nama_barang')
            ->orderByDesc('total')->limit(5)->get();
        $topMasuk = BarangMasuk::where('tanggal_masuk', '>=', $mulai->toDateString())
            ->selectRaw('nama_barang, SUM(jumlah_masuk) as total')->groupBy('nama_barang')
            ->orderByDesc('total')->limit(5)->get();
        $top = [
            'keluar' => ['labels' => $topKeluar->pluck('nama_barang')->all(), 'data' => $topKeluar->pluck('total')->map(fn ($v) => (int) $v)->all()],
            'masuk'  => ['labels' => $topMasuk->pluck('nama_barang')->all(),  'data' => $topMasuk->pluck('total')->map(fn ($v) => (int) $v)->all()],
        ];

        // ---- Stok vs minimum (barang paling rawan) ----
        $rawan = $barangs->filter(fn ($b) => (int) $b->stok_minimum > 0)
            ->sortBy(fn ($b) => (int) $b->stok / max(1, (int) $b->stok_minimum))->take(8)->values();
        $stokMin = [
            'labels'  => $rawan->pluck('nama_barang')->all(),
            'stok'    => $rawan->map(fn ($b) => (int) $b->stok)->all(),
            'minimum' => $rawan->map(fn ($b) => (int) $b->stok_minimum)->all(),
        ];

        // ---- Barang di bawah / sama dengan stok minimum ----
        $barangMinimum = $barangs->filter(fn ($b) => in_array($b->status_stok, ['menipis', 'habis']))
            ->sortBy('stok')->values();

        // ---- Transaksi terbaru ----
        $terbaru = BarangMasuk::latest()->limit(6)->get()->map(fn ($t) => [
            'jenis' => 'masuk', 'kode' => $t->kode_transaksi, 'barang' => $t->nama_barang, 'jumlah' => $t->jumlah_masuk,
            'tanggal' => $t->tanggal_masuk, 'at' => $t->created_at,
        ])->concat(BarangKeluar::latest()->limit(6)->get()->map(fn ($t) => [
            'jenis' => 'keluar', 'kode' => $t->kode_transaksi, 'barang' => $t->nama_barang, 'jumlah' => $t->jumlah_keluar,
            'tanggal' => $t->tanggal_keluar, 'at' => $t->created_at,
        ]))->sortByDesc('at')->take(8)->values();

        return view('dashboard', [
            'kpi'           => $kpi,
            'user'          => User::count(),
            'barangMasuk'   => BarangMasuk::count(),
            'barangKeluar'  => BarangKeluar::count(),
            'charts'        => compact('bulanan', 'harian', 'status', 'jenis', 'top', 'stokMin'),
            'barangMinimum' => $barangMinimum,
            'terbaru'       => $terbaru,
        ]);
    }

    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}
