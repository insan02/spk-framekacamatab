<?php 
namespace App\Http\Controllers;
use App\Models\Kriteria;
use App\Models\Frame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

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
        // Cek manual apakah nama kriteria sudah ada
        $existingKriteria = Kriteria::where('kriteria_nama', $request->kriteria_nama)->first();
        if ($existingKriteria) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['kriteria_nama' => 'Kriteria dengan nama tersebut sudah ada.']);
        }
        
        // Validasi tambahan
        $validator = Validator::make($request->all(), [
            'kriteria_nama' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $kriteria = Kriteria::create($request->all());
        
        // Periksa apakah ada frame yang perlu diperbarui
        $frameCount = Frame::count();
        
        if ($frameCount > 0) {
            // Tambahkan pesan flash untuk kriteria baru
            Session::flash('update_needed', true);
            Session::flash('update_message', "Kriteria baru '{$kriteria->kriteria_nama}' telah ditambahkan. Frame perlu diperbarui dengan nilai untuk kriteria ini.");
        }
        return redirect()->route('kriteria.index')->with('success', 'Kriteria berhasil ditambahkan');
    }

    // Menampilkan form untuk mengedit kriteria
    public function edit(Kriteria $kriteria)
    {
        return view('kriteria.edit', compact('kriteria'));
    }

    // Memperbarui data kriteria
    public function update(Request $request, Kriteria $kriteria)
    {
        // Cek manual untuk nama duplikat (kecuali untuk record saat ini)
        $existingKriteria = Kriteria::where('kriteria_nama', $request->kriteria_nama)
                            ->where('kriteria_id', '!=', $kriteria->kriteria_id)
                            ->first();
        
        if ($existingKriteria) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['kriteria_nama' => 'Kriteria dengan nama tersebut sudah ada.']);
        }
        
        // Validasi tambahan
        $validator = Validator::make($request->all(), [
            'kriteria_nama' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $oldName = $kriteria->kriteria_nama;
        
        try {
            $kriteria->update([
                'kriteria_nama' => $request->kriteria_nama
            ]);
            
            // Tambahkan pesan flash jika nama berubah
            if ($oldName !== $request->kriteria_nama) {
                $frameCount = $kriteria->frameSubkriterias()->distinct('frame_id')->count('frame_id');
                
                if ($frameCount > 0) {
                    Session::flash('update_needed', true);
                    Session::flash('update_message', "Kriteria '{$oldName}' telah diubah menjadi '{$kriteria->kriteria_nama}'. Perubahan ini otomatis diterapkan ke semua frame.");
                }
            }
            
            return redirect()->route('kriteria.index')
                ->with('success', 'Kriteria berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui kriteria: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Menghapus kriteria
    public function destroy(Kriteria $kriteria)
    {
        try {
            $kriteriaName = $kriteria->kriteria_nama;
            
            // Cek semua relasi yang menggunakan kriteria ini
            $subkriteriaCount = $kriteria->subkriterias()->count();
            $frameRelationCount = $kriteria->frameSubkriterias()->count();
            $penilaianCount = $kriteria->detailPenilaians()->count();

            $errorMessages = [];
            
            if ($subkriteriaCount > 0) {
                $errorMessages[] = "memiliki {$subkriteriaCount} subkriteria";
            }
            
            if ($frameRelationCount > 0) {
                $errorMessages[] = "digunakan dalam {$frameRelationCount} frame";
            }
            
            if ($penilaianCount > 0) {
                $errorMessages[] = "digunakan dalam {$penilaianCount} penilaian";
            }

            if (!empty($errorMessages)) {
                $message = "Kriteria '{$kriteriaName}' tidak dapat dihapus karena: ";
                $message .= implode(', ', $errorMessages);
                
                return redirect()->back()
                    ->with('error', $message);
            }

            $kriteria->delete();
            
            return redirect()->route('kriteria.index')
                ->with('success', "Kriteria '{$kriteriaName}' berhasil dihapus");
        } catch (\Exception $e) {
            return redirect()->route('kriteria.index')
                ->with('error', "Gagal menghapus kriteria '{$kriteriaName}': " . $e->getMessage());
        }
    }

}