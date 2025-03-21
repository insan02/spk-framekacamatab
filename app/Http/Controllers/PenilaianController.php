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

class PenilaianController extends Controller
{
    public function index()
    {
        $kriterias = Kriteria::with('subkriterias')->get();
        return view('penilaian.index', compact('kriterias'));
    }

    public function store(Request $request)
    {
        // Start transaction
        DB::beginTransaction();
        
        try {
            // Validate request
            $request->validate([
                'nama_pelanggan' => 'required|string|max:255',
                'nohp_pelanggan' => 'required|string|max:20',
                'alamat_pelanggan' => 'required|string|max:255',
                'subkriteria' => 'required|array',
                'bobot_kriteria' => 'required|array',
            ]);
            
            // Validate bobot kriteria total adalah 100%
            $totalBobot = array_sum($request->bobot_kriteria);
            if ($totalBobot != 100) {
                throw new \Exception("Total bobot kriteria harus 100%, saat ini: {$totalBobot}%");
            }
            
            // Create penilaian
            $penilaian = Penilaian::create([
                'tgl_penilaian' => now(),
                'nama_pelanggan' => $request->nama_pelanggan,
                'nohp_pelanggan' => $request->nohp_pelanggan,
                'alamat_pelanggan' => $request->alamat_pelanggan,
            ]);
        
            // Simpan bobot kriteria
            foreach ($request->bobot_kriteria as $kriteria_id => $bobot) {
                BobotKriteria::create([
                    'penilaian_id' => $penilaian->penilaian_id,
                    'kriteria_id' => $kriteria_id,
                    'nilai_bobot' => $bobot,
                ]);
            }
            
            // Save detail penilaian
            foreach ($request->subkriteria as $kriteria_id => $subkriteria_id) {
                DetailPenilaian::create([
                    'penilaian_id' => $penilaian->penilaian_id,
                    'kriteria_id' => $kriteria_id,
                    'subkriteria_id' => $subkriteria_id
                ]);
            }
        
            // Log subkriteria selection
            Log::info('Subkriteria Selection:', $request->subkriteria);
            Log::info('Bobot Kriteria:', $request->bobot_kriteria);
            
            // Proses Profile Matching & SMART
            $rekomendasi = $this->hitungProfileMatching($request->subkriteria, $request->bobot_kriteria);
        
            // Save rekomendasi
            foreach ($rekomendasi as $index => $frame) {
                Rekomendasi::create([
                    'penilaian_id' => $penilaian->penilaian_id,
                    'frame_id' => $frame['frame']->frame_id,
                    'nilai_akhir' => (float)$frame['score'], // Ensure value is cast to float
                    'rangking' => $index + 1
                ]);
            }
        
            Log::info('Penilaian Store Request:', $request->all());
        
            // Commit transaction
            DB::commit();
            return redirect()->route('rekomendasi.show', $penilaian->penilaian_id);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Rollback on error
            DB::rollBack();
            
            // Log detail error validasi
            Log::error('Validation Errors:', $e->errors());
            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            // Rollback on error
            DB::rollBack();
            
            Log::error('Store Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()])->withInput();
        }
    }

    private function hitungProfileMatching($subkriteriaUser, $bobotKriteriaUser)
    {
        // Log subkriteria pilihan pengguna
        Log::info('User Subkriteria Selection:', $subkriteriaUser);
        Log::info('User Bobot Kriteria:', $bobotKriteriaUser);
        
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

        // 2. Gunakan bobot kriteria dari inputan user
        $bobotKriteria = [];
        foreach ($kriterias as $kriteria) {
            // Pastikan kriteria_id ada di bobotKriteriaUser
            if (isset($bobotKriteriaUser[$kriteria->kriteria_id])) {
                $bobotKriteria[$kriteria->kriteria_id] = $bobotKriteriaUser[$kriteria->kriteria_id] / 100; // Nilai dinormalisasi
            } else {
                // Fallback jika tidak ada, gunakan nilai default
                $bobotKriteria[$kriteria->kriteria_id] = $kriteria->bobot_kriteria / 100;
            }
        }
        
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

        // Return hasil rekomendasi dengan informasi skor
        return $sortedFrames->map(function ($frame) use ($finalScores, $gapValues, $gapBobot) {
            return [
                'frame' => $frame,
                'score' => round((float)$finalScores[$frame->frame_id], 2), // Ensure score is properly rounded and cast to float
                'gap_values' => $gapValues[$frame->frame_id],
                'gap_bobot' => $gapBobot[$frame->frame_id]
            ];
        })->values();
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