<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarangRequest;
use App\Models\Barang;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarangController extends Controller
{
    public function index(Request $request): View
    {
        $barangs = Barang::query()
            ->cari($request->q)
            ->withCount('transaksiDetails')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('barang.index', compact('barangs'));
    }

    public function create(): View
    {
        return view('barang.form', ['barang' => new Barang(['satuan' => 'kg'])]);
    }

    public function store(BarangRequest $request): RedirectResponse
    {
        $barang = Barang::create($request->validated());

        return redirect()->route('barang.index')->with('success', "Barang \"{$barang->nama}\" berhasil ditambahkan.");
    }

    public function edit(Barang $barang): View
    {
        return view('barang.form', compact('barang'));
    }

    public function update(BarangRequest $request, Barang $barang): RedirectResponse
    {
        $barang->update($request->validated());

        return redirect()->route('barang.index')->with('success', "Barang \"{$barang->nama}\" berhasil diperbarui.");
    }

    public function destroy(Barang $barang): RedirectResponse
    {
        if ($alasan = $barang->alasanTidakBisaDihapus()) {
            return back()->with('error', $alasan);
        }

        try {
            $barang->delete();
        } catch (QueryException) {
            return back()->with('error', "Barang \"{$barang->nama}\" masih dipakai data lain dan tidak dapat dihapus.");
        }

        return redirect()->route('barang.index')->with('success', "Barang \"{$barang->nama}\" berhasil dihapus.");
    }
}
