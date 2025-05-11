<?php 
namespace App\Http\Controllers;
use App\Models\Kriteria;
use App\Models\Frame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogService; // Tambahkan ini

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
        // Validasi input
        $validator = Validator::make($request->all(), [
            'kriteria_nama' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/' // Memastikan hanya huruf dan spasi
            ],
        ], [
            'kriteria_nama.regex' => 'Nama kriteria hanya boleh berisi huruf.'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Cek manual apakah nama kriteria sudah ada
        $existingKriteria = Kriteria::where('kriteria_nama', $request->kriteria_nama)->first();
        if ($existingKriteria) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['kriteria_nama' => 'Kriteria dengan nama tersebut sudah ada.']);
        }

        $kriteria = Kriteria::create($request->all());
        
        // Catat aktivitas
        ActivityLogService::log(
            'create',
            'kriteria',
            $kriteria->kriteria_id,
            null,
            $kriteria->toArray(),
            'Membuat kriteria baru: ' . $kriteria->kriteria_nama
        );
        
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
        // Validasi input
        $validator = Validator::make($request->all(), [
            'kriteria_nama' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/' // Memastikan hanya huruf dan spasi
            ],
        ], [
            'kriteria_nama.regex' => 'Nama kriteria hanya boleh berisi huruf.'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Cek manual untuk nama duplikat (kecuali untuk record saat ini)
        $existingKriteria = Kriteria::where('kriteria_nama', $request->kriteria_nama)
                            ->where('kriteria_id', '!=', $kriteria->kriteria_id)
                            ->first();
        
        if ($existingKriteria) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['kriteria_nama' => 'Kriteria dengan nama tersebut sudah ada.']);
        }

        $oldName = $kriteria->kriteria_nama;
        $oldData = $kriteria->toArray();
        
        // Periksa apakah ada perubahan data
        if ($oldName === $request->kriteria_nama) {
            // Tidak ada perubahan data, langsung redirect tanpa update dan log
            return redirect()->route('kriteria.index')
                ->with('info', 'Tidak ada perubahan data pada kriteria');
        }
        
        try {
            $kriteria->update([
                'kriteria_nama' => $request->kriteria_nama
            ]);
            
            // Catat aktivitas ke log hanya jika ada perubahan
            ActivityLogService::log(
                'update',
                'kriteria',
                $kriteria->kriteria_id,
                $oldData,
                $kriteria->toArray(),
                'Mengubah kriteria dari "' . $oldName . '" menjadi "' . $kriteria->kriteria_nama . '"'
            );
            
            // Tambahkan pesan flash karena nama sudah pasti berubah (sudah dicek sebelumnya)
            $frameCount = $kriteria->frameSubkriterias()->distinct('frame_id')->count('frame_id');
            
            if ($frameCount > 0) {
                Session::flash('update_needed', true);
                Session::flash('update_message', "Kriteria '{$oldName}' telah diubah menjadi '{$kriteria->kriteria_nama}'. Perubahan ini otomatis diterapkan ke semua frame.");
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
            $kriteriaData = $kriteria->toArray();
            
            // Cek semua relasi yang menggunakan kriteria ini
            $subkriteriaCount = $kriteria->subkriterias()->count();
            $frameRelationCount = $kriteria->frameSubkriterias()->count();

            $errorMessages = [];
            
            if ($subkriteriaCount > 0) {
                $errorMessages[] = "memiliki {$subkriteriaCount} subkriteria";
            }
            
            if ($frameRelationCount > 0) {
                $errorMessages[] = "digunakan dalam {$frameRelationCount} frame";
            }
            
            if (!empty($errorMessages)) {
                $message = "Kriteria '{$kriteriaName}' tidak dapat dihapus karena: ";
                $message .= implode(', ', $errorMessages);
                
                return redirect()->back()
                    ->with('error', $message);
            }

            $kriteria->delete();
            
            // Catat aktivitas
            ActivityLogService::log(
                'delete',
                'kriteria',
                $kriteria->kriteria_id,
                $kriteriaData,
                null,
                'Menghapus kriteria: ' . $kriteriaName
            );
            
            return redirect()->route('kriteria.index')
                ->with('success', "Kriteria '{$kriteriaName}' berhasil dihapus");
        } catch (\Exception $e) {
            return redirect()->route('kriteria.index')
                ->with('error', "Gagal menghapus kriteria '{$kriteriaName}': " . $e->getMessage());
        }
    }
}