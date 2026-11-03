<?php

namespace App\Http\Controllers;

use App\Http\Requests\KlienRequest;
use App\Models\Klien;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KlienController extends Controller
{
    public function index(Request $request): View
    {
        $kliens = Klien::query()
            ->cari($request->q)
            ->withCount('transaksis')
            ->withSum('transaksis', 'total_jual')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('klien.index', compact('kliens'));
    }

    public function create(): View
    {
        return view('klien.form', ['klien' => new Klien]);
    }

    public function store(KlienRequest $request): RedirectResponse
    {
        $klien = Klien::create($request->validated());

        return redirect()->route('klien.index')->with('success', "Klien \"{$klien->nama}\" berhasil ditambahkan.");
    }

    public function edit(Klien $klien): View
    {
        return view('klien.form', compact('klien'));
    }

    public function update(KlienRequest $request, Klien $klien): RedirectResponse
    {
        $klien->update($request->validated());

        return redirect()->route('klien.index')->with('success', "Klien \"{$klien->nama}\" berhasil diperbarui.");
    }

    public function destroy(Klien $klien): RedirectResponse
    {
        if ($alasan = $klien->alasanTidakBisaDihapus()) {
            return back()->with('error', $alasan);
        }

        try {
            $klien->delete();
        } catch (QueryException) {
            return back()->with('error', "Klien \"{$klien->nama}\" masih dipakai data lain dan tidak dapat dihapus.");
        }

        return redirect()->route('klien.index')->with('success', "Klien \"{$klien->nama}\" berhasil dihapus.");
    }
}
