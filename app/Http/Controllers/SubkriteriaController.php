<?php

namespace App\Http\Controllers;

use App\Models\Subkriteria;
use App\Models\Kriteria;
use App\Models\FrameSubkriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogService;

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
    /**
 * Menyimpan data subkriteria baru
 */
public function store(Request $request)
{
    // Validasi dasar untuk semua jenis subkriteria
    $validator = Validator::make($request->all(), [
        'kriteria_id' => 'required|exists:kriterias,kriteria_id',
        'tipe_subkriteria' => 'required|in:teks,rentang nilai',
        'subkriteria_bobot' => 'required|integer|min:1|max:5',
        'subkriteria_keterangan' => 'required|regex:/^[A-Za-z\s]+$/', // Validasi untuk keterangan
    ], [
        'kriteria_id.required' => 'Kriteria harus dipilih',
        'kriteria_id.exists' => 'Kriteria yang dipilih tidak valid',
        'tipe_subkriteria.required' => 'Tipe subkriteria harus dipilih',
        'tipe_subkriteria.in' => 'Tipe subkriteria tidak valid',
        'subkriteria_bobot.required' => 'Bobot subkriteria tidak boleh kosong',
        'subkriteria_keterangan.required' => 'Keterangan bobot subkriteria tidak boleh kosong',
        'subkriteria_keterangan.regex' => 'Keterangan bobot subkriteria hanya boleh berisi huruf dan spasi.',
        'subkriteria_bobot.min' => 'Bobot subkriteria minimal 1',
        'subkriteria_bobot.max' => 'Bobot subkriteria maksimal 5',
        'subkriteria_keterangan.max' => 'Keterangan maksimal 255 karakter',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    // Tentukan nama subkriteria berdasarkan tipe
    $subkriteriaData = [
        'kriteria_id' => $request->kriteria_id,
        'tipe_subkriteria' => $request->tipe_subkriteria,
        'subkriteria_bobot' => $request->subkriteria_bobot,
        'subkriteria_keterangan' => $request->subkriteria_keterangan, // Tambahkan keterangan
    ];

    if ($request->tipe_subkriteria == 'teks') {
        // Validasi tambahan untuk subkriteria teks
        $validator = Validator::make($request->all(), [
            'subkriteria_nama_teks' => 'required|string|max:255',
        ], [
            'subkriteria_nama_teks.required' => 'Nama subkriteria tidak boleh kosong',
            'subkriteria_nama_teks.max' => 'Nama subkriteria maksimal 255 karakter',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $subkriteriaData['subkriteria_nama'] = $request->subkriteria_nama_teks;
    } else {
        // Validasi tambahan untuk subkriteria numerik
        $validator = Validator::make($request->all(), [
            'operator' => 'required|in:<,<=,>,>=,between',
        ], [
            'operator.required' => 'Operator perbandingan harus dipilih',
            'operator.in' => 'Operator perbandingan tidak valid',
        ]);

        // Validasi berdasarkan jenis operator
        if ($request->operator == 'between') {
            $validator = Validator::make($request->all(), [
                'nilai_minimum' => 'required|numeric',
                'nilai_maksimum' => 'required|numeric|gt:nilai_minimum',
            ], [
                'nilai_minimum.required' => 'Nilai minimum harus diisi',
                'nilai_minimum.numeric' => 'Nilai minimum harus berupa angka',
                'nilai_maksimum.required' => 'Nilai maksimum harus diisi',
                'nilai_maksimum.numeric' => 'Nilai maksimum harus berupa angka',
                'nilai_maksimum.gt' => 'Nilai maksimum harus lebih besar dari nilai minimum',
            ]);
        } elseif ($request->operator == '<' || $request->operator == '<=') {
            $validator = Validator::make($request->all(), [
                'nilai_maksimum' => 'required|numeric',
            ], [
                'nilai_maksimum.required' => 'Nilai maksimum harus diisi',
                'nilai_maksimum.numeric' => 'Nilai maksimum harus berupa angka',
            ]);
        } elseif ($request->operator == '>' || $request->operator == '>=') {
            $validator = Validator::make($request->all(), [
                'nilai_minimum' => 'required|numeric',
            ], [
                'nilai_minimum.required' => 'Nilai minimum harus diisi',
                'nilai_minimum.numeric' => 'Nilai minimum harus berupa angka',
            ]);
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Generate nama subkriteria berdasarkan operator dan nilai
        $subkriteriaData['operator'] = $request->operator;
        
        if ($request->operator == 'between') {
            $subkriteriaData['nilai_minimum'] = $request->nilai_minimum;
            $subkriteriaData['nilai_maksimum'] = $request->nilai_maksimum;
            $subkriteriaData['subkriteria_nama'] = number_format($request->nilai_minimum, 0, ',', '.') . 
                                                  ' - ' . 
                                                  number_format($request->nilai_maksimum, 0, ',', '.');
        } elseif ($request->operator == '<' || $request->operator == '<=') {
            $subkriteriaData['nilai_maksimum'] = $request->nilai_maksimum;
            $subkriteriaData['subkriteria_nama'] = $request->operator . ' ' . number_format($request->nilai_maksimum, 0, ',', '.');
        } else {
            $subkriteriaData['nilai_minimum'] = $request->nilai_minimum;
            $subkriteriaData['subkriteria_nama'] = $request->operator . ' ' . number_format($request->nilai_minimum, 0, ',', '.');
        }
    }

    // Cek manual apakah subkriteria dengan nama yang sama sudah ada dalam kriteria yang sama
    $existingSubkriteria = Subkriteria::where('kriteria_id', $request->kriteria_id)
                            ->where('subkriteria_nama', $subkriteriaData['subkriteria_nama'])
                            ->first();
    
    if ($existingSubkriteria) {
        return redirect()->back()
            ->withInput()
            ->withErrors(['subkriteria_nama' => 'Subkriteria dengan nama "' . $subkriteriaData['subkriteria_nama'] . '" sudah ada untuk kriteria ini. Silakan gunakan nama yang berbeda.']);
    }

    // Simpan data subkriteria
    $subkriteria = Subkriteria::create($subkriteriaData);
    
    // Tampilkan pesan flash untuk subkriteria baru
    $kriteria = Kriteria::find($request->kriteria_id);
    $frameCount = FrameSubkriteria::where('kriteria_id', $kriteria->kriteria_id)
        ->distinct('frame_id')
        ->count('frame_id');
    
    if ($frameCount > 0) {
        Session::flash('update_needed', true);
        Session::flash('update_message', "Subkriteria baru '{$subkriteria->subkriteria_nama}' telah ditambahkan untuk kriteria '{$kriteria->kriteria_nama}'. Frame yang menggunakan kriteria ini mungkin perlu diperbarui.");
    }

    // Catat aktivitas
    ActivityLogService::log(
        'create',
        'subkriteria',
        $subkriteria->subkriteria_id,
        null,
        $subkriteria->toArray(),
        'Membuat subkriteria baru: ' . $subkriteria->subkriteria_nama . ' (Kriteria: ' . $kriteria->kriteria_nama . ')'
    );

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
    // Validasi dasar untuk semua tipe
    $validator = Validator::make($request->all(), [
        'kriteria_id' => 'required|exists:kriterias,kriteria_id',
        'tipe_subkriteria' => 'required|in:teks,rentang nilai',
        'subkriteria_bobot' => 'required|integer|min:1|max:5',
        'subkriteria_keterangan' => 'required|regex:/^[A-Za-z\s]+$/', // Validasi untuk keterangan
    ], [
        'kriteria_id.required' => 'Kriteria harus dipilih',
        'kriteria_id.exists' => 'Kriteria yang dipilih tidak valid',
        'tipe_subkriteria.required' => 'Tipe subkriteria harus dipilih',
        'tipe_subkriteria.in' => 'Tipe subkriteria tidak valid',
        'subkriteria_bobot.required' => 'Bobot subkriteria tidak boleh kosong',
        'subkriteria_keterangan.required' => 'Keterangan bobot subkriteria tidak boleh kosong',
        'subkriteria_keterangan.regex' => 'Keterangan bobot subkriteria hanya boleh berisi huruf dan spasi.',
        'subkriteria_bobot.min' => 'Bobot subkriteria minimal 1',
        'subkriteria_bobot.max' => 'Bobot subkriteria maksimal 5',
        'subkriteria_keterangan.max' => 'Keterangan maksimal 255 karakter',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    $data = $request->all();
    
    // Handle berdasarkan tipe subkriteria
    if ($request->tipe_subkriteria == 'teks') {
        // Validasi untuk teks
        $validator = Validator::make($data, [
            'subkriteria_nama' => 'required|string|max:255',
        ], [
            'subkriteria_nama.required' => 'Nama subkriteria tidak boleh kosong',
            'subkriteria_nama.max' => 'Nama subkriteria maksimal 255 karakter',
        ]);

        // Reset field numerik
        $data['operator'] = null;
        $data['nilai_minimum'] = null;
        $data['nilai_maksimum'] = null;
    } else {
        // Validasi untuk rentang nilai
        $validator = Validator::make($data, [
            'operator' => 'required|in:<,<=,>,>=,between',
            'subkriteria_nama_numerik' => 'required',
        ], [
            'operator.required' => 'Operator perbandingan harus dipilih',
            'operator.in' => 'Operator perbandingan tidak valid',
            'subkriteria_nama_numerik.required' => 'Rentang nilai tidak valid',
        ]);

        // Handle validasi numerik berdasarkan operator
        if ($data['operator'] == 'between') {
            $validator->addRules([
                'nilai_minimum' => 'required|numeric',
                'nilai_maksimum' => 'required|numeric|gt:nilai_minimum',
            ]);
        } elseif ($data['operator'] == '<' || $data['operator'] == '<=') {
            $validator->addRules([
                'nilai_maksimum' => 'required|numeric',
            ]);
        } else {
            $validator->addRules([
                'nilai_minimum' => 'required|numeric',
            ]);
        }

        // Update nama subkriteria dari preview
        $data['subkriteria_nama'] = $data['subkriteria_nama_numerik'];
    }

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    // Cek duplikat
    $existingSubkriteria = Subkriteria::where('kriteria_id', $data['kriteria_id'])
        ->where('subkriteria_nama', $data['subkriteria_nama'])
        ->where('subkriteria_id', '!=', $subkriteria->subkriteria_id)
        ->first();

    if ($existingSubkriteria) {
        return redirect()->back()
            ->withInput()
            ->withErrors(['subkriteria_nama' => 'Subkriteria dengan nama ini sudah ada']);
    }

    $oldData = $subkriteria->toArray();
    $oldName = $subkriteria->subkriteria_nama;

    try {
        // Update data
        $subkriteria->update($data);

        // Catat aktivitas
        ActivityLogService::log(
            'update',
            'subkriteria',
            $subkriteria->subkriteria_id,
            $oldData,
            $subkriteria->fresh()->toArray(),
            'Mengubah subkriteria dari "' . $oldName . '" menjadi "' . $subkriteria->subkriteria_nama . '"'
        );
        
        return redirect()->route('subkriteria.index')
                ->with('success', 'Subkriteria berhasil diperbarui');
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
        $subkriteriaData = $subkriteria->toArray();
        
        // Cek semua relasi yang menggunakan subkriteria ini
        $frameRelationCount = $subkriteria->frameSubkriterias()->count();

        // Hanya tampilkan error jika benar-benar ada relasi
        if ($frameRelationCount > 0) {
            $message = "Subkriteria '{$subkriteriaName}' (Kriteria: {$kriteriaName}) tidak dapat dihapus karena: ";
            $message .= "digunakan dalam {$frameRelationCount} frame";
            
            return redirect()->back()
                ->with('error', $message);
        }

        // Jika tidak ada relasi, hapus subkriteria
        $subkriteria->delete();

        // Catat aktivitas
        ActivityLogService::log(
            'delete',
            'subkriteria',
            $subkriteria->subkriteria_id,
            $subkriteriaData,
            null,
            'Menghapus subkriteria: ' . $subkriteriaName . ' (Kriteria: ' . $kriteriaName . ')'
        );
        
        return redirect()->route('subkriteria.index')
            ->with('success', 'Subkriteria "' . $subkriteriaName . '" berhasil dihapus');
    } catch (\Exception $e) {
        return redirect()->route('subkriteria.index')
            ->with('error', 'Gagal menghapus subkriteria: ' . $e->getMessage());
    }
}

public function resetSubkriteria($kriteria_id)
{
    try {
        $kriteria = Kriteria::findOrFail($kriteria_id);
        
        // Cari subkriteria yang terkait dengan frame
        $restrictedSubkriterias = Subkriteria::where('kriteria_id', $kriteria_id)
            ->whereHas('frameSubkriterias', function($query) use ($kriteria_id) {
                $query->where('kriteria_id', $kriteria_id);
            })
            ->get();

        if ($restrictedSubkriterias->isNotEmpty()) {
            $errorDetails = '<ul>'; // Awal list
            foreach ($restrictedSubkriterias as $subkriteria) {
                $frameCount = $subkriteria->frameSubkriterias()
                    ->where('kriteria_id', $kriteria_id)
                    ->count();
                
                $errorDetails .= "<li>Subkriteria <strong>'{$subkriteria->subkriteria_nama}'</strong> tidak dapat dihapus karena digunakan dalam <strong>{$frameCount} frame</strong>.</li>";
            }
            $errorDetails .= '</ul>'; // Tutup list

            session()->flash('error', "Tidak dapat mereset subkriteria. Beberapa subkriteria sedang digunakan: <br>" . $errorDetails);
            return redirect()->back();
        }

        // Ambil data subkriteria sebelum dihapus untuk aktivitas log
        $subkriterias = Subkriteria::where('kriteria_id', $kriteria_id)->get();
        $subkriteriasData = $subkriterias->toArray();
        $subkriteriaCount = $subkriterias->count();
        
        // Hapus subkriteria yang tidak digunakan
        Subkriteria::where('kriteria_id', $kriteria_id)->delete();
        
        // Catat aktivitas log
        ActivityLogService::log(
            'delete',
            'subkriteria',
            $kriteria_id, // Menggunakan kriteria_id sebagai identifier
            $subkriteriasData,
            null,
            'Mereset ' . $subkriteriaCount . ' subkriteria untuk kriteria: ' . $kriteria->kriteria_nama
        );

        return redirect()->route('subkriteria.index')
            ->with('success', "Semua subkriteria untuk kriteria {$kriteria->kriteria_nama} berhasil dihapus.");

    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Gagal mereset subkriteria: ' . $e->getMessage());
    }
}

}