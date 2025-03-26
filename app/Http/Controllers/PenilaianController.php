<?php

namespace App\Http\Controllers;

use App\Models\Penilaian;
use App\Models\DetailPenilaian;
use App\Models\Frame;
use App\Models\Kriteria;
use App\Models\BobotKriteria;
use App\Models\Rekomendasi;
use App\Models\Subkriteria;
use App\Models\FrameSubkriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class PenilaianController extends Controller
{
    public function index()
    {
        // Get all kriteria
        $kriterias = Kriteria::with('subkriterias')->get();
        
        // Check for incomplete frames
        $incompleteFrames = $this->checkIncompleteFrames();
        
        return view('penilaian.index', compact('kriterias', 'incompleteFrames'));
    }

    private function checkIncompleteFrames()
    {
        // Get all kriteria
        $allKriterias = Kriteria::all()->pluck('kriteria_id')->toArray();
        
        // Get all frames
        $frames = Frame::with('frameSubkriterias')->get();
        
        $incompleteFrames = [];
        
        foreach ($frames as $frame) {
            // Get kriteria IDs that this frame has subkriterias for
            $frameKriterias = $frame->frameSubkriterias->pluck('kriteria_id')->toArray();
            
            // Check if any kriteria is missing
            $missingKriterias = array_diff($allKriterias, $frameKriterias);
            
            if (count($missingKriterias) > 0) {
                $incompleteFrames[] = $frame;
            }
        }
        
        return $incompleteFrames;
    }

    public function process(Request $request)
{
    // Validate the request (similar to store method)
    $request->validate([
        'nama_pelanggan' => 'required|string|max:255',
        'nohp_pelanggan' => 'required|string|max:20',
        'alamat_pelanggan' => 'required|string|max:255',
        'subkriteria' => 'required|array',
        'bobot_kriteria' => 'required|array',
    ]);

    // Check for incomplete frames
    $incompleteFrames = $this->checkIncompleteFrames();
    if (count($incompleteFrames) > 0) {
        return response()->json([
            'error' => 'Terdapat frame yang belum lengkap',
            'incomplete_frames' => $incompleteFrames
        ], 400);
    }

    // Calculate total bobot
    $totalBobot = array_sum($request->bobot_kriteria);
    if ($totalBobot <= 0) {
        return response()->json(['error' => 'Total bobot kriteria harus lebih dari 0'], 400);
    }

    // Perform profile matching calculation
    $rekomendasi = $this->hitungProfileMatching($request->subkriteria, $request->bobot_kriteria, $totalBobot);

    // Prepare kriteria yang dipilih
    $kriteria_dipilih = [];
    foreach ($request->subkriteria as $kriteria_id => $subkriteria_id) {
        $kriteria = Kriteria::findOrFail($kriteria_id);
        $subkriteria = Subkriteria::findOrFail($subkriteria_id);
        $kriteria_dipilih[$kriteria->kriteria_nama] = $subkriteria->subkriteria_nama;
    }

    // Dapatkan semua data perhitungan
    $hasilPerhitungan = $this->hitungProfileMatching($request->subkriteria, $request->bobot_kriteria, $totalBobot);
    
    // Render the results view dengan data tambahan
    $html = view('penilaian.result', [
        'nama_pelanggan' => $request->nama_pelanggan,
        'nohp_pelanggan' => $request->nohp_pelanggan,
        'alamat_pelanggan' => $request->alamat_pelanggan,
        'rekomendasi' => $hasilPerhitungan['rekomendasi'],
        'kriteria_dipilih' => $kriteria_dipilih,
        'perhitungan' => $hasilPerhitungan // Kirim semua data perhitungan
    ])->render();

    return response()->json([
        'html' => $html,
        'rekomendasi' => $hasilPerhitungan['rekomendasi']
    ]);
}

public function store(Request $request)
{
    // Validate request data with more comprehensive rules
    $validator = Validator::make($request->all(), [
        'nama_pelanggan' => 'required|string|max:255',
        'nohp_pelanggan' => 'required|string|max:20',
        'alamat_pelanggan' => 'required|string|max:255',
        'subkriteria' => 'required|array|min:1',
        'bobot_kriteria' => 'required|array|min:1',
    ], [
        'nama_pelanggan.required' => 'Nama pelanggan wajib diisi',
        'nohp_pelanggan.required' => 'Nomor HP wajib diisi',
        'alamat_pelanggan.required' => 'Alamat wajib diisi',
        'subkriteria.required' => 'Kriteria wajib dipilih',
        'bobot_kriteria.required' => 'Bobot kriteria wajib ditentukan',
    ]);

    // Check for validation errors
    if ($validator->fails()) {
        return response()->json([
            'error' => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422);
    }

    // Check for incomplete frames
    $incompleteFrames = $this->checkIncompleteFrames();
    if (count($incompleteFrames) > 0) {
        return response()->json([
            'error' => 'Terdapat ' . count($incompleteFrames) . ' frame yang belum lengkap',
            'incomplete_frames' => $incompleteFrames
        ], 400);
    }

    // Begin database transaction
    DB::beginTransaction();
    
    try {
        // Validate kriteria and subkriteria
        $kriterias = Kriteria::whereIn('kriteria_id', array_keys($request->subkriteria))->get();
        if ($kriterias->count() !== count($request->subkriteria)) {
            throw new \Exception('Beberapa kriteria tidak valid');
        }

        // Validate kriteria selections
        foreach ($kriterias as $kriteria) {
            $subkriteria = Subkriteria::find($request->subkriteria[$kriteria->kriteria_id]);
            if (!$subkriteria || $subkriteria->kriteria_id !== $kriteria->kriteria_id) {
                throw new \Exception("Subkriteria tidak valid untuk kriteria {$kriteria->kriteria_nama}");
            }
        }

        // Calculate total bobot
        $totalBobot = array_sum($request->bobot_kriteria);
        if ($totalBobot <= 0) {
            throw new \Exception("Total bobot kriteria harus lebih dari 0");
        }

        // Create penilaian record
        $penilaian = Penilaian::create([
            'tgl_penilaian' => now(),
            'nama_pelanggan' => $request->nama_pelanggan,
            'nohp_pelanggan' => $request->nohp_pelanggan,
            'alamat_pelanggan' => $request->alamat_pelanggan,
        ]);

        // Save bobot kriteria
        $bobotKriteriaRecords = [];
        foreach ($request->bobot_kriteria as $kriteria_id => $bobot) {
            $bobotKriteriaRecords[] = [
                'penilaian_id' => $penilaian->penilaian_id,
                'kriteria_id' => $kriteria_id,
                'nilai_bobot' => $bobot,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        BobotKriteria::insert($bobotKriteriaRecords);

        // Save detail penilaian
        $detailPenilaianRecords = [];
        foreach ($request->subkriteria as $kriteria_id => $subkriteria_id) {
            $detailPenilaianRecords[] = [
                'penilaian_id' => $penilaian->penilaian_id,
                'kriteria_id' => $kriteria_id,
                'subkriteria_id' => $subkriteria_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DetailPenilaian::insert($detailPenilaianRecords);

        // Proses Profile Matching & SMART
        $rekomendasi = $this->hitungProfileMatching($request->subkriteria, $request->bobot_kriteria, $totalBobot);

        // Save rekomendasi
        $rekomendasiRecords = [];
        foreach ($rekomendasi['rekomendasi'] as $index => $frame) {
            $rekomendasiRecords[] = [
                'penilaian_id' => $penilaian->penilaian_id,
                'frame_id' => $frame['frame']->frame_id,
                'nilai_akhir' => (float)$frame['score'],
                'rangking' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Rekomendasi::insert($rekomendasiRecords);

        // Commit transaction
        DB::commit();
        
        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi berhasil disimpan',
            'redirect_url' => route('rekomendasi.show', $penilaian->penilaian_id)
        ]);
    
    } catch (\Exception $e) {
        // Rollback transaction
        DB::rollBack();
        
        // Log detailed error
        Log::error('Penilaian Store Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        
        // Return error response
        return response()->json([
            'error' => 'Gagal menyimpan rekomendasi: ' . $e->getMessage()
        ], 500);
    }
}

    private function hitungProfileMatching($subkriteriaUser, $bobotKriteriaUser, $totalBobot)
{
    // Log subkriteria pilihan pengguna
    Log::info('User Subkriteria Selection:', $subkriteriaUser);
    Log::info('User Bobot Kriteria (Raw):', $bobotKriteriaUser);
    Log::info('Total Bobot:', ['value' => $totalBobot]);
    
    // 1. Ambil semua data kriteria dan frame
    $kriterias = Kriteria::with('subkriterias')->get();
    $frames = Frame::with(['frameSubkriterias' => function($query) use ($kriterias) {
        $query->whereIn('kriteria_id', $kriterias->pluck('kriteria_id'))
              ->with('subkriteria');
    }])->get();

    // Pastikan semua frame memiliki nilai untuk setiap kriteria
    foreach ($frames as $frame) {
        foreach ($kriterias as $kriteria) {
            $frameSubkriteria = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id)->first();
            if (!$frameSubkriteria) {
                throw new \Exception("Frame {$frame->frame_merek} tidak memiliki nilai untuk kriteria {$kriteria->kriteria_nama}");
            }
        }
    }

    // 2. Normalisasi bobot kriteria (dibagi dengan total)
    $bobotKriteria = [];
    foreach ($kriterias as $kriteria) {
        // Pastikan kriteria_id ada di bobotKriteriaUser
        if (isset($bobotKriteriaUser[$kriteria->kriteria_id])) {
            // Normalisasi: nilai bobot dibagi total bobot (tanpa dikali 100)
            $bobotKriteria[$kriteria->kriteria_id] = $bobotKriteriaUser[$kriteria->kriteria_id] / $totalBobot;
        } else {
            // Fallback jika tidak ada, gunakan nilai default
            $bobotKriteria[$kriteria->kriteria_id] = $kriteria->bobot_kriteria / 100;
        }
    }
    
    // Log normalized weights
    Log::info('Normalized Weights:', $bobotKriteria);
    
    // 3. Hitung GAP untuk setiap frame
    $gapValues = [];
    $gapBobot = [];
    
    foreach ($frames as $frame) {
        $gapValues[$frame->frame_id] = [];
        $gapBobot[$frame->frame_id] = [];
        
        foreach ($kriterias as $kriteria) {
            $frameSubkriteria = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id)->first();
            $userSubkriteria = Subkriteria::findOrFail($subkriteriaUser[$kriteria->kriteria_id]);
            
            // Hitung selisih (GAP)
            $gap = $frameSubkriteria->subkriteria->subkriteria_bobot - $userSubkriteria->subkriteria_bobot;
            $gapValues[$frame->frame_id][$kriteria->kriteria_id] = $gap;
            
            // Konversi GAP ke nilai bobot sesuai tabel
            $bobotGap = $this->convertGapToBobot($gap);
            $gapBobot[$frame->frame_id][$kriteria->kriteria_id] = $bobotGap;
        }
    }
    
    Log::info('GAP Values:', $gapValues);
    Log::info('GAP Weights:', $gapBobot);
    
    // 4. Implementasi metode SMART untuk perangkingan
    $finalScores = [];
    
    foreach ($frames as $frame) {
        $finalScores[$frame->frame_id] = 0;
        
        foreach ($kriterias as $kriteria) {
            // Kalikan bobot kriteria dengan nilai bobot GAP
            $finalScores[$frame->frame_id] += $bobotKriteria[$kriteria->kriteria_id] * $gapBobot[$frame->frame_id][$kriteria->kriteria_id];
        }
    }
    
    Log::info('Final Scores:', $finalScores);
    
    // 5. Urutkan frame berdasarkan skor akhir (nilai lebih tinggi = peringkat lebih baik)
    $sortedFrames = $frames->sortByDesc(function ($frame) use ($finalScores) {
        return $finalScores[$frame->frame_id];
    });

    // Kumpulkan semua data yang diperlukan
    return [
        'rekomendasi' => $sortedFrames->map(function ($frame) use ($finalScores, $gapValues, $gapBobot, $subkriteriaUser, $kriterias) {
            // Kumpulkan detail per kriteria untuk tampilan
            $details = [];
            foreach ($kriterias as $kriteria) {
                $frameSubkriteria = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id)->first();
                $userSubkriteria = Subkriteria::findOrFail($subkriteriaUser[$kriteria->kriteria_id]);
                
                $details[] = [
                    'kriteria' => $kriteria,
                    'frame_subkriteria' => $frameSubkriteria->subkriteria,
                    'user_subkriteria' => $userSubkriteria,
                ];
            }
            
            return [
                'frame' => $frame,
                'score' => round((float)$finalScores[$frame->frame_id], 2),
                'gap_values' => $gapValues[$frame->frame_id],
                'gap_bobot' => $gapBobot[$frame->frame_id],
                'details' => $details,
            ];
        })->values(),
        'kriterias' => $kriterias,
        'bobotKriteria' => $bobotKriteria,
        'totalBobot' => $totalBobot,
        'bobotKriteriaUser' => $bobotKriteriaUser,
    ];
}

    private function convertGapToBobot($gap)
    {
        // Konversi GAP ke bobot berdasarkan tabel
        switch ($gap) {
            case 0: return 5.0;    // Tidak ada selisih
            case 1: return 4.5;    // Kelebihan 1 tingkat
            case -1: return 4.0;   // Kekurangan 1 tingkat
            case 2: return 3.5;    // Kelebihan 2 tingkat
            case -2: return 3.0;   // Kekurangan 2 tingkat
            case 3: return 2.5;    // Kelebihan 3 tingkat
            case -3: return 2.0;   // Kekurangan 3 tingkat
            case 4: return 1.5;    // Kelebihan 4 tingkat
            case -4: return 1.0;   // Kekurangan 4 tingkat
            default:
                // Untuk gap lebih dari 4 atau kurang dari -4
                if ($gap > 4) return 1.5;
                if ($gap < -4) return 1.0;
                return 0.0; // Fallback jika ada kesalahan
        }
    }

    
}