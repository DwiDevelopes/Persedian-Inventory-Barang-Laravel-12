<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use App\Models\Barang;
use App\Models\Satuan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LaporanStokController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('laporan-stok.index');
    }

    /**
     * Query barang sesuai filter. Batas minimum memakai stok_minimum masing-masing barang.
     */
    private function queryBarang(?string $opsi)
    {
        $q = Barang::with(['jenis', 'satuan'])->orderBy('kode_barang');

        if ($opsi == 'minimum') {
            $q->whereColumn('stok', '<=', 'stok_minimum')->where('stok', '>', 0);
        } elseif ($opsi == 'stok-habis') {
            $q->where('stok', '<=', 0);
        } elseif ($opsi == 'aman') {
            $q->whereColumn('stok', '>', 'stok_minimum');
        }

        return $q->get();
    }

    /**
     * Get Data
     */
    public function getData(Request $request)
    {
        $barangs = $this->queryBarang($request->input('opsi'))->map(fn ($b) => [
            'id'             => $b->id,
            'kode_barang'    => $b->kode_barang,
            'nama_barang'    => $b->nama_barang,
            'jenis'          => $b->jenis->jenis_barang ?? '-',
            'satuan'         => $b->satuan->satuan ?? '',
            'lokasi'         => $b->lokasi,
            'stok'           => (int) $b->stok,
            'stok_minimum'   => (int) $b->stok_minimum,
            'harga_beli'     => (int) $b->harga_beli,
            'nilai'          => (int) $b->stok * (int) $b->harga_beli,
            'status'         => $b->status_stok,
        ]);

        return response()->json($barangs->values());
    }

    /**
     * Print Data
    */
    public function printStok(Request $request)
    {
        $selectedOption = $request->input('opsi');
        $barangs = $this->queryBarang($selectedOption);

        // Generate PDF
        $dompdf = new Dompdf();
        $html = view('/laporan-stok/print-stok', compact('barangs', 'selectedOption'))->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('print-stok.pdf', ['Attachment' => false]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Get Satuan
     */
    public function getSatuan()
    {
        $satuans = Satuan::all();
        return response()->json($satuans);
    }
}
