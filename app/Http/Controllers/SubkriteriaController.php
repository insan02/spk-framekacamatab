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
        'tipe_subkriteria' => 'required|in:teks,angka,rentang nilai',
        'subkriteria_bobot' => 'required|integer|min:1|max:5',
        'subkriteria_keterangan' => 'required|regex:/^[A-Za-z\s\,\.\-]+$/', // Validasi untuk keterangan
    ], [
        'kriteria_id.required' => 'Kriteria harus dipilih',
        'kriteria_id.exists' => 'Kriteria yang dipilih tidak valid',
        'tipe_subkriteria.required' => 'Tipe subkriteria harus dipilih',
        'tipe_subkriteria.in' => 'Tipe subkriteria tidak valid',
        'subkriteria_bobot.required' => 'Bobot subkriteria harus dipilih',
        'subkriteria_keterangan.required' => 'Keterangan bobot subkriteria tidak boleh kosong',
        'subkriteria_keterangan.regex' => 'Keterangan bobot tidak valid.',
        'subkriteria_bobot.min' => 'Bobot subkriteria minimal 1',
        'subkriteria_bobot.max' => 'Bobot subkriteria maksimal 5',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    // Data dasar subkriteria
    $subkriteriaData = [
        'kriteria_id' => $request->kriteria_id,
        'tipe_subkriteria' => $request->tipe_subkriteria,
        'subkriteria_bobot' => $request->subkriteria_bobot,
        'subkriteria_keterangan' => $request->subkriteria_keterangan,
    ];

    // Proses berdasarkan tipe subkriteria
    if ($request->tipe_subkriteria == 'teks') {
        // Validasi tambahan untuk subkriteria teks
        $validator = Validator::make($request->all(), [
            'subkriteria_nama_teks' => 'required|regex:/^[A-Za-z\s\-]+$/',
        ], [
            'subkriteria_nama_teks.required' => 'Nama subkriteria tidak boleh kosong',
            'subkriteria_nama_teks.regex' => 'Nama subkriteria tidak valid',
            'subkriteria_nama_teks.max' => 'Nama subkriteria maksimal 255 karakter',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $subkriteriaData['subkriteria_nama'] = $request->subkriteria_nama_teks;
    } 
    elseif ($request->tipe_subkriteria == 'angka') {
        // Validasi tambahan untuk subkriteria angka
        $validator = Validator::make($request->all(), [
            'subkriteria_nilai_angka' => 'required|numeric',
            'subkriteria_satuan' => 'nullable|string|max:20',
        ], [
            'subkriteria_nilai_angka.required' => 'Nilai angka tidak boleh kosong',
            'subkriteria_nilai_angka.numeric' => 'Nilai harus berupa angka',
            'subkriteria_satuan.max' => 'Satuan maksimal 20 karakter',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Format angka dan tambahkan satuan jika ada
        $angka = $request->subkriteria_nilai_angka;
        // Cek apakah angka memiliki desimal yang signifikan
        if (floor($angka) == $angka) {
            // Jika tidak ada desimal atau desimalnya 0, tampilkan sebagai integer
            $formattedAngka = number_format($angka, 0, ',', '.');
        } else {
            // Jika ada desimal, tampilkan dengan format desimal
            $formattedAngka = number_format($angka, 2, ',', '.');
            // Hapus trailing zeros
            $formattedAngka = rtrim(rtrim($formattedAngka, '0'), ',');
        }
        $subkriteriaData['subkriteria_nama'] = $formattedAngka;
        
        if (!empty($request->subkriteria_satuan)) {
            $subkriteriaData['subkriteria_nama'] .= ' ' . $request->subkriteria_satuan;
        }
        
        // Simpan nilai angka asli untuk keperluan komparasi
        $subkriteriaData['nilai_minimum'] = $request->subkriteria_nilai_angka;
    } 
    else { // rentang nilai
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
            
            // Format nilai minimum dengan penanganan desimal
            $min = $request->nilai_minimum;
            if (floor($min) == $min) {
                $formattedMin = number_format($min, 0, ',', '.');
            } else {
                $formattedMin = number_format($min, 2, ',', '.');
                $formattedMin = rtrim(rtrim($formattedMin, '0'), ',');
            }
            
            // Format nilai maksimum dengan penanganan desimal
            $max = $request->nilai_maksimum;
            if (floor($max) == $max) {
                $formattedMax = number_format($max, 0, ',', '.');
            } else {
                $formattedMax = number_format($max, 2, ',', '.');
                $formattedMax = rtrim(rtrim($formattedMax, '0'), ',');
            }
            
            $subkriteriaData['subkriteria_nama'] = $formattedMin . ' - ' . $formattedMax;
            
        } elseif ($request->operator == '<' || $request->operator == '<=') {
            $subkriteriaData['nilai_maksimum'] = $request->nilai_maksimum;
            
            // Format nilai maksimum dengan penanganan desimal
            $max = $request->nilai_maksimum;
            if (floor($max) == $max) {
                $formattedMax = number_format($max, 0, ',', '.');
            } else {
                $formattedMax = number_format($max, 2, ',', '.');
                $formattedMax = rtrim(rtrim($formattedMax, '0'), ',');
            }
            
            $subkriteriaData['subkriteria_nama'] = $request->operator . ' ' . $formattedMax;
            
        } else { // > atau >=
            $subkriteriaData['nilai_minimum'] = $request->nilai_minimum;
            
            // Format nilai minimum dengan penanganan desimal
            $min = $request->nilai_minimum;
            if (floor($min) == $min) {
                $formattedMin = number_format($min, 0, ',', '.');
            } else {
                $formattedMin = number_format($min, 2, ',', '.');
                $formattedMin = rtrim(rtrim($formattedMin, '0'), ',');
            }
            
            $subkriteriaData['subkriteria_nama'] = $request->operator . ' ' . $formattedMin;
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
        'tipe_subkriteria' => 'required|in:teks,angka,rentang nilai',
        'subkriteria_bobot' => 'required|integer|min:1|max:5',
        'subkriteria_keterangan' => 'required|regex:/^[A-Za-z\s\,\.\-]+$/',
    ], [
        'kriteria_id.required' => 'Kriteria harus dipilih',
        'kriteria_id.exists' => 'Kriteria yang dipilih tidak valid',
        'tipe_subkriteria.required' => 'Tipe subkriteria harus dipilih',
        'tipe_subkriteria.in' => 'Tipe subkriteria tidak valid',
        'subkriteria_bobot.required' => 'Bobot subkriteria tidak boleh kosong',
        'subkriteria_keterangan.required' => 'Keterangan bobot subkriteria tidak boleh kosong',
        'subkriteria_keterangan.regex' => 'Keterangan bobot subkriteria tidak valid.',
        'subkriteria_bobot.min' => 'Bobot subkriteria minimal 1',
        'subkriteria_bobot.max' => 'Bobot subkriteria maksimal 5',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    $data = [
        'kriteria_id' => $request->kriteria_id,
        'tipe_subkriteria' => $request->tipe_subkriteria,
        'subkriteria_bobot' => $request->subkriteria_bobot,
        'subkriteria_keterangan' => $request->subkriteria_keterangan,
    ];
    
    // Handle berdasarkan tipe subkriteria
    if ($request->tipe_subkriteria == 'teks') {
        // Validasi untuk teks
        $validator = Validator::make($request->all(), [
            'subkriteria_nama_teks' => 'required|regex:/^[A-Za-z\s\-]+$/',
        ], [
            'subkriteria_nama_teks.required' => 'Nama subkriteria tidak boleh kosong',
            'subkriteria_nama_teks.regex' => 'Nama subkriteria tidak valid',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data['subkriteria_nama'] = $request->subkriteria_nama_teks;
        
        // Reset field numerik
        $data['operator'] = null;
        $data['nilai_minimum'] = null;
        $data['nilai_maksimum'] = null;
    } 
    elseif ($request->tipe_subkriteria == 'angka') {
        // Validasi untuk angka
        $validator = Validator::make($request->all(), [
            'subkriteria_nilai_angka' => 'required|numeric',
            'subkriteria_satuan' => 'nullable|string|max:20',
        ], [
            'subkriteria_nilai_angka.required' => 'Nilai angka tidak boleh kosong',
            'subkriteria_nilai_angka.numeric' => 'Nilai harus berupa angka',
            'subkriteria_satuan.max' => 'Satuan maksimal 20 karakter',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Reset field operator dan rentang nilai
        $data['operator'] = null;
        
        // Format angka dan tambahkan satuan jika ada
        $angka = $request->subkriteria_nilai_angka;
        
        // Cek apakah angka memiliki desimal yang signifikan
        if (floor($angka) == $angka) {
            // Jika tidak ada desimal atau desimalnya 0, tampilkan sebagai integer
            $formattedAngka = number_format($angka, 0, ',', '.');
        } else {
            // Jika ada desimal, tampilkan dengan format desimal
            $formattedAngka = number_format($angka, 2, ',', '.');
            // Hapus trailing zeros
            $formattedAngka = rtrim(rtrim($formattedAngka, '0'), ',');
        }
        
        $data['subkriteria_nama'] = $formattedAngka;
        
        if (!empty($request->subkriteria_satuan)) {
            $data['subkriteria_nama'] .= ' ' . $request->subkriteria_satuan;
        }
        
        // Simpan nilai angka asli untuk keperluan komparasi
        $data['nilai_minimum'] = $request->subkriteria_nilai_angka;
        $data['nilai_maksimum'] = null;
    }
    else { // rentang nilai
        // Validasi operator
        $validator = Validator::make($request->all(), [
            'operator' => 'required|in:<,<=,>,>=,between',
        ], [
            'operator.required' => 'Operator perbandingan harus dipilih',
            'operator.in' => 'Operator perbandingan tidak valid',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data['operator'] = $request->operator;
        
        // Validasi dan set data berdasarkan operator
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
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            
            $data['nilai_minimum'] = $request->nilai_minimum;
            $data['nilai_maksimum'] = $request->nilai_maksimum;
            
            // Format nilai minimum dengan penanganan desimal
            $min = $request->nilai_minimum;
            if (floor($min) == $min) {
                $formattedMin = number_format($min, 0, ',', '.');
            } else {
                $formattedMin = number_format($min, 2, ',', '.');
                $formattedMin = rtrim(rtrim($formattedMin, '0'), ',');
            }
            
            // Format nilai maksimum dengan penanganan desimal
            $max = $request->nilai_maksimum;
            if (floor($max) == $max) {
                $formattedMax = number_format($max, 0, ',', '.');
            } else {
                $formattedMax = number_format($max, 2, ',', '.');
                $formattedMax = rtrim(rtrim($formattedMax, '0'), ',');
            }
            
            $data['subkriteria_nama'] = $formattedMin . ' - ' . $formattedMax;
            
        } 
        elseif ($request->operator == '<' || $request->operator == '<=') {
            $validator = Validator::make($request->all(), [
                'nilai_maksimum' => 'required|numeric',
            ], [
                'nilai_maksimum.required' => 'Nilai maksimum harus diisi',
                'nilai_maksimum.numeric' => 'Nilai maksimum harus berupa angka',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            
            $data['nilai_minimum'] = null;
            $data['nilai_maksimum'] = $request->nilai_maksimum;
            
            // Format nilai maksimum dengan penanganan desimal
            $max = $request->nilai_maksimum;
            if (floor($max) == $max) {
                $formattedMax = number_format($max, 0, ',', '.');
            } else {
                $formattedMax = number_format($max, 2, ',', '.');
                $formattedMax = rtrim(rtrim($formattedMax, '0'), ',');
            }
            
            $data['subkriteria_nama'] = $request->operator . ' ' . $formattedMax;
            
        } 
        else { // > atau >=
            $validator = Validator::make($request->all(), [
                'nilai_minimum' => 'required|numeric',
            ], [
                'nilai_minimum.required' => 'Nilai minimum harus diisi',
                'nilai_minimum.numeric' => 'Nilai minimum harus berupa angka',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            
            $data['nilai_minimum'] = $request->nilai_minimum;
            $data['nilai_maksimum'] = null;
            
            // Format nilai minimum dengan penanganan desimal
            $min = $request->nilai_minimum;
            if (floor($min) == $min) {
                $formattedMin = number_format($min, 0, ',', '.');
            } else {
                $formattedMin = number_format($min, 2, ',', '.');
                $formattedMin = rtrim(rtrim($formattedMin, '0'), ',');
            }
            
            $data['subkriteria_nama'] = $request->operator . ' ' . $formattedMin;
        }
        
        // Jika ada input nama subkriteria numerik dari form, prioritaskan itu
        if (isset($request->subkriteria_nama_numerik) && !empty($request->subkriteria_nama_numerik)) {
            $data['subkriteria_nama'] = $request->subkriteria_nama_numerik;
        }
    }

    // Cek duplikat pada kriteria yang sama (kecuali subkriteria ini sendiri)
    $existingSubkriteria = Subkriteria::where('kriteria_id', $data['kriteria_id'])
        ->where('subkriteria_nama', $data['subkriteria_nama'])
        ->where('subkriteria_id', '!=', $subkriteria->subkriteria_id)
        ->first();

    if ($existingSubkriteria) {
        return redirect()->back()
            ->withInput()
            ->withErrors(['subkriteria_nama' => 'Subkriteria dengan nama "' . $data['subkriteria_nama'] . '" sudah ada untuk kriteria ini. Silakan gunakan nama yang berbeda.']);
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
        
        // Cek jika perlu update frame
        $kriteria = Kriteria::find($request->kriteria_id);
        $frameCount = FrameSubkriteria::where('kriteria_id', $kriteria->kriteria_id)
            ->distinct('frame_id')
            ->count('frame_id');
        
        if ($frameCount > 0) {
            Session::flash('update_needed', true);
        }
        
        return redirect()->route('subkriteria.index')
                ->with('success', 'Subkriteria "' . $subkriteria->subkriteria_nama . '" berhasil diperbarui');
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