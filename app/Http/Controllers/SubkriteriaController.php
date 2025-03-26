<?php

namespace App\Http\Controllers;

use App\Models\Subkriteria;
use App\Models\Kriteria;
use App\Models\FrameSubkriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

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
        // Validasi dasar
        $validator = Validator::make($request->all(), [
            'kriteria_id' => 'required|exists:kriterias,kriteria_id',
            'subkriteria_nama' => 'required|string|max:255',
            'subkriteria_bobot' => 'required|integer|min:1|max:5',
        ], [
            'kriteria_id.required' => 'Kriteria harus dipilih',
            'kriteria_id.exists' => 'Kriteria yang dipilih tidak valid',
            'subkriteria_nama.required' => 'Nama subkriteria tidak boleh kosong',
            'subkriteria_nama.max' => 'Nama subkriteria maksimal 255 karakter',
            'subkriteria_bobot.required' => 'Bobot subkriteria tidak boleh kosong',
            'subkriteria_bobot.min' => 'Bobot subkriteria minimal 1',
            'subkriteria_bobot.max' => 'Bobot subkriteria maksimal 5'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Cek manual apakah subkriteria dengan nama yang sama sudah ada dalam kriteria yang sama
        $existingSubkriteria = Subkriteria::where('kriteria_id', $request->kriteria_id)
                                ->where('subkriteria_nama', $request->subkriteria_nama)
                                ->first();
        
        if ($existingSubkriteria) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['subkriteria_nama' => 'Subkriteria dengan nama "' . $request->subkriteria_nama . '" sudah ada untuk kriteria ini. Silakan gunakan nama yang berbeda.']);
        }

        $subkriteria = Subkriteria::create($request->all());
        
        // Tampilkan pesan flash untuk subkriteria baru
        $kriteria = Kriteria::find($request->kriteria_id);
        $frameCount = FrameSubkriteria::where('kriteria_id', $kriteria->kriteria_id)
            ->distinct('frame_id')
            ->count('frame_id');
        
        if ($frameCount > 0) {
            Session::flash('update_needed', true);
            Session::flash('update_message', "Subkriteria baru '{$subkriteria->subkriteria_nama}' telah ditambahkan untuk kriteria '{$kriteria->kriteria_nama}'. Frame yang menggunakan kriteria ini mungkin perlu diperbarui.");
        }

        return redirect()->route('subkriteria.index')->with('success', 'Subkriteria "' . $subkriteria->subkriteria_nama . '" berhasil ditambahkan');
    }

    // Menampilkan form untuk mengedit subkriteria
    public function edit(Subkriteria $subkriteria)
    {
        $kriterias = Kriteria::all();
        return view('subkriteria.edit', compact('subkriteria', 'kriterias'));
    }

    // Memperbarui data subkriteria
    public function update(Request $request, Subkriteria $subkriteria)
    {
        // Validasi dasar
        $validator = Validator::make($request->all(), [
            'kriteria_id' => 'required|exists:kriterias,kriteria_id',
            'subkriteria_nama' => 'required|string|max:255',
            'subkriteria_bobot' => 'required|integer|min:1|max:5',
        ], [
            'kriteria_id.required' => 'Kriteria harus dipilih',
            'kriteria_id.exists' => 'Kriteria yang dipilih tidak valid',
            'subkriteria_nama.required' => 'Nama subkriteria tidak boleh kosong',
            'subkriteria_nama.max' => 'Nama subkriteria maksimal 255 karakter',
            'subkriteria_bobot.required' => 'Bobot subkriteria tidak boleh kosong',
            'subkriteria_bobot.min' => 'Bobot subkriteria minimal 1',
            'subkriteria_bobot.max' => 'Bobot subkriteria maksimal 5'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Cek manual untuk nama duplikat dalam kriteria yang sama (kecuali untuk record saat ini)
        $existingSubkriteria = Subkriteria::where('kriteria_id', $request->kriteria_id)
                               ->where('subkriteria_nama', $request->subkriteria_nama)
                               ->where('subkriteria_id', '!=', $subkriteria->subkriteria_id)
                               ->first();
        
        if ($existingSubkriteria) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['subkriteria_nama' => 'Subkriteria dengan nama "' . $request->subkriteria_nama . '" sudah ada untuk kriteria ini. Silakan gunakan nama yang berbeda.']);
        }

        $oldName = $subkriteria->subkriteria_nama;
        $oldBobot = $subkriteria->subkriteria_bobot;
        $oldKriteriaId = $subkriteria->kriteria_id;
        
        try {
            $subkriteria->update($request->all());

            // Periksa apakah ada frame yang terpengaruh dan perlu diperbarui
            $frameCount = FrameSubkriteria::where('subkriteria_id', $subkriteria->subkriteria_id)
                ->count();
            
            if ($frameCount > 0 && ($oldName != $request->subkriteria_nama || 
                                   $oldBobot != $request->subkriteria_bobot || 
                                   $oldKriteriaId != $request->kriteria_id)) {
                
                $message = "Subkriteria '{$oldName}' telah diperbarui menjadi '{$subkriteria->subkriteria_nama}'. ";
                
                if ($oldBobot != $request->subkriteria_bobot) {
                    $message .= "Bobot berubah dari {$oldBobot} menjadi {$request->subkriteria_bobot}. ";
                }
                
                if ($oldKriteriaId != $request->kriteria_id) {
                    $oldKriteria = Kriteria::find($oldKriteriaId);
                    $newKriteria = Kriteria::find($request->kriteria_id);
                    $message .= "Kriteria berubah dari '{$oldKriteria->kriteria_nama}' menjadi '{$newKriteria->kriteria_nama}'. ";
                }
                
                $message .= "Perubahan ini otomatis diterapkan ke semua frame terkait.";
                
                Session::flash('update_needed', true);
                Session::flash('update_message', $message);
                
                // Jika kriteria berubah, perbarui FrameSubkriteria juga
                if ($oldKriteriaId != $request->kriteria_id) {
                    FrameSubkriteria::where('subkriteria_id', $subkriteria->subkriteria_id)
                        ->update(['kriteria_id' => $request->kriteria_id]);
                }
            }

            return redirect()->route('subkriteria.index')->with('success', 'Subkriteria "' . $subkriteria->subkriteria_nama . '" berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui subkriteria: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Menghapus subkriteria
    public function destroy(Subkriteria $subkriteria)
{
    try {
        $subkriteriaName = $subkriteria->subkriteria_nama;
        $kriteriaName = $subkriteria->kriteria->kriteria_nama;
        
        // Cek semua relasi yang menggunakan subkriteria ini
        $frameRelationCount = $subkriteria->frameSubkriterias()->count();
        $penilaianCount = $subkriteria->detailPenilaians()->count();

        $errorMessages = [];
        
        if ($frameRelationCount > 0) {
            $errorMessages[] = "digunakan dalam {$frameRelationCount} frame";
        }
        
        if ($penilaianCount > 0) {
            $errorMessages[] = "digunakan dalam {$penilaianCount} penilaian";
        }

        if (!empty($errorMessages)) {
            $message = "Subkriteria '{$subkriteriaName}' (Kriteria: {$kriteriaName}) tidak dapat dihapus karena: ";
            $message .= implode(', ', $errorMessages);
            return redirect()->back()
                ->with('error', $message);
        }

        $subkriteria->delete();
        
        return redirect()->route('subkriteria.index')->with('success', 'Subkriteria "' . $subkriteriaName . '" berhasil dihapus');
    } catch (\Exception $e) {
        return redirect()->route('subkriteria.index')
            ->with('error', 'Gagal menghapus subkriteria: ' . $e->getMessage());
    }
}

public function resetSubkriteria($kriteria_id)
{
    try {
        $kriteria = Kriteria::findOrFail($kriteria_id);
        
        // Hanya cari subkriteria yang spesifik untuk kriteria ini
        $restrictedSubkriterias = Subkriteria::where('kriteria_id', $kriteria_id)
            ->whereHas('frameSubkriterias', function($query) use ($kriteria_id) {
                $query->where('kriteria_id', $kriteria_id);
            })
            ->orWhereHas('detailPenilaians', function($query) use ($kriteria_id) {
                $query->whereHas('subkriteria', function($subquery) use ($kriteria_id) {
                    $subquery->where('kriteria_id', $kriteria_id);
                });
            })
            ->get();

        if ($restrictedSubkriterias->isNotEmpty()) {
            $errorDetails = [];
            foreach ($restrictedSubkriterias as $subkriteria) {
                $frameCount = $subkriteria->frameSubkriterias()->where('kriteria_id', $kriteria_id)->count();
                $penilaianCount = $subkriteria->detailPenilaians()->whereHas('subkriteria', function($query) use ($kriteria_id) {
                    $query->where('kriteria_id', $kriteria_id);
                })->count();
                
                $errorDetails[] = "Subkriteria '{$subkriteria->subkriteria_nama}' tidak dapat dihapus karena: " . 
                    ($frameCount > 0 ? "digunakan dalam {$frameCount} frame. " : "") . 
                    ($penilaianCount > 0 ? "digunakan dalam {$penilaianCount} penilaian." : "");
            }

            return redirect()->back()
                ->with('error', 'Tidak dapat mereset subkriteria. Beberapa subkriteria sedang digunakan:' . 
                    '<br>' . implode('<br>', $errorDetails));
        }

        // Hapus hanya subkriteria untuk kriteria ini
        Subkriteria::where('kriteria_id', $kriteria_id)->delete();

        return redirect()->route('subkriteria.index')
            ->with('success', "Semua subkriteria untuk kriteria '{$kriteria->kriteria_nama}' berhasil dihapus");

    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Gagal mereset subkriteria: ' . $e->getMessage());
    }
}
}