<?php

namespace App\Http\Controllers;

use App\Models\Kriteria;
use Illuminate\Http\Request;

class KriteriaController extends Controller
{
    // Menampilkan daftar kriteria
    public function index()
    {
        $kriterias = Kriteria::all();
        return view('kriteria.index', compact('kriterias'));
    }

    // Menampilkan form untuk membuat kriteria baru
    public function create()
    {
        return view('kriteria.create');
    }

    // Menyimpan data kriteria baru
    public function store(Request $request)
    {
        $request->validate([
            'kriteria_nama' => 'required|string|max:255',
        ]);

        Kriteria::create($request->all());

        return redirect()->route('kriteria.index')->with('success', 'Kriteria berhasil ditambahkan');
    }

    // Menampilkan form untuk mengedit kriteria
    public function edit(Kriteria $kriteria)
{
    return view('kriteria.edit', compact('kriteria'));
}

public function update(Request $request, Kriteria $kriteria)
{
    $request->validate([
        'kriteria_nama' => 'required|string|max:255',
    ]);

    try {
        $kriteria->update([
            'kriteria_nama' => $request->kriteria_nama
        ]);

        return redirect()->route('kriteria.index')
            ->with('success', 'Kriteria berhasil diperbarui');
    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Gagal memperbarui kriteria')
            ->withInput();
    }
}

public function destroy(Kriteria $kriteria)
{
    try {
        $kriteria->delete();
        return redirect()->route('kriteria.index')
            ->with('success', 'Kriteria berhasil dihapus');
    } catch (\Exception $e) {
        return redirect()->route('kriteria.index')
            ->with('error', 'Gagal menghapus kriteria');
    }
}

}
