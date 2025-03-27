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
use Illuminate\Pagination\LengthAwarePaginator;

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
        // Validate the request 
        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => 'required|string|max:255',
            'nohp_pelanggan' => 'required|string|max:20',
            'alamat_pelanggan' => 'required|string|max:255',
            'subkriteria' => 'required|array',
            'bobot_kriteria' => 'required|array',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

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

        try {
            // Perform profile matching calculation
            $hasilPerhitungan = $this->hitungProfileMatching($request->subkriteria, $request->bobot_kriteria, $totalBobot);

            // Prepare kriteria yang dipilih
            $kriteria_dipilih = [];
            foreach ($request->subkriteria as $kriteria_id => $subkriteria_id) {
                $kriteria = Kriteria::findOrFail($kriteria_id);
                $subkriteria = Subkriteria::findOrFail($subkriteria_id);
                $kriteria_dipilih[$kriteria->kriteria_nama] = $subkriteria->subkriteria_nama;
            }

            // Render the results view
            $html = view('penilaian.result', [
                'nama_pelanggan' => $request->nama_pelanggan,
                'nohp_pelanggan' => $request->nohp_pelanggan,
                'alamat_pelanggan' => $request->alamat_pelanggan,
                'rekomendasi' => $hasilPerhitungan['rekomendasi'],
                'kriteria_dipilih' => $kriteria_dipilih,
                'perhitungan' => $hasilPerhitungan
            ])->render();

            return response()->json([
                'html' => $html,
                'rekomendasi' => $hasilPerhitungan['rekomendasi']
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error in penilaian process: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'error' => 'Terjadi kesalahan dalam memproses penilaian: ' . $e->getMessage()
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

    // 2. Normalisasi bobot kriteria
    $bobotKriteria = [];
    foreach ($kriterias as $kriteria) {
        $bobotKriteria[$kriteria->kriteria_id] = isset($bobotKriteriaUser[$kriteria->kriteria_id]) 
            ? $bobotKriteriaUser[$kriteria->kriteria_id] / $totalBobot 
            : $kriteria->bobot_kriteria / 100;
    }
    
    // 3. Hitung GAP untuk setiap frame
    $gapValues = [];
    $gapBobot = [];
    $detailedGapInfo = []; // Tambahkan variabel untuk menyimpan informasi gap terperinci
    
    foreach ($frames as $frame) {
        $gapValues[$frame->frame_id] = [];
        $gapBobot[$frame->frame_id] = [];
        $detailedGapInfo[$frame->frame_id] = []; // Inisialisasi array untuk frame ini
        
        foreach ($kriterias as $kriteria) {
            // Ambil semua subkriteria frame untuk kriteria ini
            $frameSubkriterias = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id);
            
            // Ambil subkriteria pilihan user untuk kriteria ini
            $userSubkriteria = Subkriteria::findOrFail($subkriteriaUser[$kriteria->kriteria_id]);
            
            // Temukan GAP terkecil di antara semua kombinasi subkriteria
            $minGap = PHP_FLOAT_MAX;
            $selectedFrameSubkriteria = null;
            $selectedGapDetails = null;
            
            foreach ($frameSubkriterias as $frameSubkriteria) {
                // Hitung selisih (GAP)
                $gap = abs($frameSubkriteria->subkriteria->subkriteria_bobot - $userSubkriteria->subkriteria_bobot);
                
                // Update jika gap lebih kecil
                if ($gap < $minGap) {
                    $minGap = $gap;
                    $selectedFrameSubkriteria = $frameSubkriteria->subkriteria;
                    $selectedGapDetails = [
                        'user_subkriteria' => $userSubkriteria,
                        'frame_subkriteria' => $frameSubkriteria->subkriteria,
                        'gap' => $gap
                    ];
                }
            }
            
            // Simpan informasi gap
            $gapValue = $selectedFrameSubkriteria->subkriteria_bobot - $userSubkriteria->subkriteria_bobot;
            $gapValues[$frame->frame_id][$kriteria->kriteria_id] = $gapValue;
            
            // Konversi GAP ke nilai bobot
            $bobotGap = $this->convertGapToBobot($gapValue);
            $gapBobot[$frame->frame_id][$kriteria->kriteria_id] = $bobotGap;
            
            // Simpan detail gap terperinci
            $detailedGapInfo[$frame->frame_id][$kriteria->kriteria_id] = $selectedGapDetails;
        }
    }
    
    // 4. Implementasi metode SMART untuk perangkingan
    $finalScores = [];
    
    foreach ($frames as $frame) {
        $finalScores[$frame->frame_id] = 0;
        
        foreach ($kriterias as $kriteria) {
            // Kalikan bobot kriteria dengan nilai bobot GAP
            $finalScores[$frame->frame_id] += $bobotKriteria[$kriteria->kriteria_id] * $gapBobot[$frame->frame_id][$kriteria->kriteria_id];
        }
    }
    
    // 5. Urutkan frame berdasarkan skor akhir
    $sortedFrames = $frames->sortByDesc(function ($frame) use ($finalScores) {
        return $finalScores[$frame->frame_id];
    });

    // Kumpulkan semua data yang diperlukan
    return [
        'rekomendasi' => $sortedFrames->map(function ($frame) use (
            $finalScores, 
            $gapValues, 
            $gapBobot, 
            $subkriteriaUser, 
            $kriterias,
            $detailedGapInfo
        ) {
            // Kumpulkan detail per kriteria
            $details = [];
            foreach ($kriterias as $kriteria) {
                $gapDetail = $detailedGapInfo[$frame->frame_id][$kriteria->kriteria_id];
                
                $details[] = [
                    'kriteria' => $kriteria,
                    'frame_subkriteria' => $gapDetail['frame_subkriteria'],
                    'user_subkriteria' => $gapDetail['user_subkriteria'],
                    'gap_detail' => $gapDetail
                ];
            }
            
            return [
                'frame' => $frame,
                'score' => round((float)$finalScores[$frame->frame_id], 2),
                'gap_values' => $gapValues[$frame->frame_id],
                'gap_bobot' => $gapBobot[$frame->frame_id],
                'details' => $details,
                'detailed_gap_info' => $detailedGapInfo[$frame->frame_id]
            ];
        })->values(),
        'kriterias' => $kriterias,
        'bobotKriteria' => $bobotKriteria,
        'totalBobot' => $totalBobot,
        'bobotKriteriaUser' => $bobotKriteriaUser,
        'detailedGapInfo' => $detailedGapInfo
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