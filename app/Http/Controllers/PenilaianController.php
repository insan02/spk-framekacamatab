<?php

namespace App\Http\Controllers;

use App\Models\Penilaian;
use App\Models\DetailPenilaian;
use App\Models\Frame;
use App\Models\Kriteria;
use App\Models\RecommendationHistory;
use App\Models\BobotKriteria;
use App\Models\Rekomendasi;
use App\Models\Subkriteria;
use App\Models\FrameSubkriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use App\Models\Customer;

class PenilaianController extends Controller
{
    public function index()
    {
        // Get all kriteria
        $kriterias = Kriteria::with('subkriterias')->get();
        
        // Check for incomplete frames
        $incompleteFrames = $this->checkIncompleteFrames();
        
        // Get all customers for display in table
        $customers = Customer::orderBy('name')->get();
        
        return view('penilaian.index', compact('kriterias', 'incompleteFrames', 'customers'));
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
            'customer_id' => 'required|exists:customers,customer_id',
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
            // Get customer data
            $customer = Customer::findOrFail($request->customer_id);

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
                'customer' => $customer,
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

    public function store(Request $request)
    {
        // Validate the request 
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,customer_id',
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
            // Get customer data
            $customer = Customer::findOrFail($request->customer_id);

            // Perform profile matching calculation
            $hasilPerhitungan = $this->hitungProfileMatching($request->subkriteria, $request->bobot_kriteria, $totalBobot);

            // Prepare kriteria yang dipilih
            $kriteria_dipilih = [];
            foreach ($request->subkriteria as $kriteria_id => $subkriteria_id) {
                $kriteria = Kriteria::findOrFail($kriteria_id);
                $subkriteria = Subkriteria::findOrFail($subkriteria_id);
                $kriteria_dipilih[$kriteria->kriteria_nama] = $subkriteria->subkriteria_nama;
            }

            // Salin gambar frame ke direktori history
            foreach ($hasilPerhitungan['rekomendasi'] as &$item) {
                $frameFoto = $item['frame']['frame_foto'] ?? null;
                if ($frameFoto && Storage::disk('public')->exists($frameFoto)) {
                    // Generate nama unik untuk file
                    $newFilename = 'history_images/' . uniqid() . '_' . basename($frameFoto);
                    
                    // Salin file ke direktori history
                    Storage::disk('public')->copy($frameFoto, $newFilename);
                    
                    // Update path foto di data rekomendasi
                    $item['frame']['frame_foto'] = $newFilename;
                } else {
                    $item['frame']['frame_foto'] = null; // Jika foto tidak ada
                }
            }
            unset($item); // Hapus reference terakhir

            // Create Recommendation History
            $recommendationHistory = RecommendationHistory::create([
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->name,      // Store customer name
                'customer_phone' => $customer->phone,    // Store customer phone
                'customer_address' => $customer->address, // Store customer address
                'kriteria_dipilih' => $kriteria_dipilih,
                'bobot_kriteria' => $request->bobot_kriteria,
                'rekomendasi_data' => $hasilPerhitungan['rekomendasi'],
                'perhitungan_detail' => $hasilPerhitungan
            ]);

            // Render the results view
            $html = view('penilaian.result', [
                'customer' => $customer,
                'rekomendasi' => $hasilPerhitungan['rekomendasi'],
                'kriteria_dipilih' => $kriteria_dipilih,
                'perhitungan' => $hasilPerhitungan
            ])->render();

            return response()->json([
                'html' => $html,
                'rekomendasi' => $hasilPerhitungan['rekomendasi'],
                'recommendation_history_id' => $recommendationHistory->recommendation_history_id,
                'redirect_url' => route('rekomendasi.show', ['id' => $recommendationHistory->recommendation_history_id]),
                'success' => 'Rekomendasi berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error in penilaian store: ' . $e->getMessage());
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