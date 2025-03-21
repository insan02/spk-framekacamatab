<?php

namespace App\Http\Controllers;

use App\Models\Subkriteria;
use App\Models\Kriteria;
use Illuminate\Http\Request;

class SubkriteriaController extends Controller
{
    // Menampilkan daftar subkriteria
    public function index()
{
    $kriterias = Kriteria::with('subkriterias')->get();
    return view('subkriteria.index', compact('kriterias'));
}

    // Menampilkan form untuk membuat subkriteria baru
    public function create(Request $request)
{
    $selectedKriteria = Kriteria::findOrFail($request->kriteria_id);
    $kriterias = Kriteria::all();
    return view('subkriteria.create', compact('kriterias', 'selectedKriteria'));
}

    // Menyimpan data subkriteria baru
    public function store(Request $request)
    {
        $request->validate([
            'kriteria_id' => 'required|exists:kriterias,kriteria_id',
            'subkriteria_nama' => 'required|string|max:255',
            'subkriteria_bobot' => 'required|numeric',
        ]);

        Subkriteria::create($request->all());

        return redirect()->route('subkriteria.index')->with('success', 'Subkriteria berhasil ditambahkan');
    }

    // Menampilkan form untuk mengedit subkriteria
    public function edit(Subkriteria $subkriteria)
    {
        $kriterias = Kriteria::all(); // Ambil data Kriteria untuk pilihan
        return view('subkriteria.edit', compact('subkriteria', 'kriterias'));
    }

    // Memperbarui data subkriteria
    public function update(Request $request, Subkriteria $subkriteria)
    {
        $request->validate([
            'kriteria_id' => 'required|exists:kriterias,kriteria_id',
            'subkriteria_nama' => 'required|string|max:255',
            'subkriteria_bobot' => 'required|numeric',
        ]);

        $subkriteria->update($request->all());

        return redirect()->route('subkriteria.index')->with('success', 'Subkriteria berhasil diperbarui');
    }

    // Menghapus subkriteria
    public function destroy(Subkriteria $subkriteria)
    {
        try {
            $subkriteria->delete();
            return redirect()->route('subkriteria.index')->with('success', 'Subkriteria berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('subkriteria.index')->with('error', 'Gagal menghapus subkriteria');
        }
    }
}
