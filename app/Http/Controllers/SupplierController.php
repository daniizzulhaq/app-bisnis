<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->cari($request->q)
            ->withCount('transaksiDetails')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('supplier.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('supplier.form', ['supplier' => new Supplier]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()->route('supplier.index')->with('success', "Supplier \"{$supplier->nama}\" berhasil ditambahkan.");
    }

    public function edit(Supplier $supplier): View
    {
        return view('supplier.form', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()->route('supplier.index')->with('success', "Supplier \"{$supplier->nama}\" berhasil diperbarui.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($alasan = $supplier->alasanTidakBisaDihapus()) {
            return back()->with('error', $alasan);
        }

        try {
            $supplier->delete();
        } catch (QueryException) {
            // Jaring pengaman dari foreign key bila ada relasi lain
            return back()->with('error', "Supplier \"{$supplier->nama}\" masih dipakai data lain dan tidak dapat dihapus.");
        }

        return redirect()->route('supplier.index')->with('success', "Supplier \"{$supplier->nama}\" berhasil dihapus.");
    }
}
