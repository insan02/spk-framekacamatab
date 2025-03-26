<?php

namespace App\Http\Controllers;

use App\Models\Penilaian;
use App\Models\Rekomendasi;
use App\Models\Kriteria;
use App\Models\Subkriteria;
use App\Models\Frame;
use App\Models\FrameSubkriteria;
use App\Models\BobotKriteria;
use App\Models\DetailPenilaian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RekomendasiController extends Controller
{
    public function index()
    {
        try {
            // Modified: Fetch all penilaian with recommendations ordered by nilai_akhir
            $rekomendasis = Penilaian::with([
                'rekomendasis' => function($query) {
                    $query->orderBy('nilai_akhir', 'desc')
                          ->with('frame');
                }, 
                'detailPenilaians.kriteria', 
                'detailPenilaians.subkriteria'
            ])
            ->latest()
            ->get();

            return view('rekomendasi.index', compact('rekomendasis'));
        } catch (\Exception $e) {
            Log::error('Rekomendasi index error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memuat riwayat rekomendasi');
        }
    }

    public function show($penilaianId)
    {
        try {
            $penilaian = Penilaian::with([
                'detailPenilaians.kriteria', 
                'detailPenilaians.subkriteria',
                'bobotKriterias',
                'rekomendasis' => function($query) {
                    $query->orderBy('nilai_akhir', 'desc') // Modified: sort by nilai_akhir instead of rangking
                          ->with('frame');
                }
            ])->findOrFail($penilaianId);

            // Tambahkan perhitungan Profile Matching & SMART di sini
            $detailPerhitungan = $this->hitungDetailProfileMatching($penilaian);

            return view('rekomendasi.show', compact('penilaian', 'detailPerhitungan'));
        } catch (\Exception $e) {
            Log::error('Rekomendasi show error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memuat detail rekomendasi');
        }
    }

    private function hitungDetailProfileMatching($penilaian)
    {
        // Ambil subkriteria yang dipilih user
        $subkriteriaUser = $penilaian->detailPenilaians->pluck('subkriteria_id', 'kriteria_id')->toArray();
        
        // Ambil bobot kriteria dari penilaian
        $bobotKriteriaUser = $penilaian->bobotKriterias->pluck('nilai_bobot', 'kriteria_id')->toArray();
        
        // Calculate total bobot for normalization - UPDATED to match PenilaianController
        $totalBobot = array_sum($bobotKriteriaUser);
        if ($totalBobot <= 0) {
            throw new \Exception("Total bobot kriteria harus lebih dari 0");
        }
        
        // Log data yang digunakan
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

        // 2. Siapkan data untuk perhitungan
        $rawValues = [];
        $userValues = [];
        $userValuesByName = [];
        $frameValuesByName = [];
        
        // Siapkan nilai untuk tiap kriteria dari user
        foreach ($kriterias as $kriteria) {
            // Pastikan kriteria_id ada di subkriteriaUser
            if (isset($subkriteriaUser[$kriteria->kriteria_id])) {
                $userSubkriteria = Subkriteria::findOrFail($subkriteriaUser[$kriteria->kriteria_id]);
                $userValues[$kriteria->kriteria_id] = $userSubkriteria->subkriteria_bobot;
                $userValuesByName[$kriteria->kriteria_nama] = [
                    'bobot' => $userSubkriteria->subkriteria_bobot,
                    'nama' => $userSubkriteria->subkriteria_nama
                ];
            } else {
                // Fallback jika tidak ada, gunakan nilai pertama dari subkriteria
                $defaultSubkriteria = $kriteria->subkriterias->first();
                $userValues[$kriteria->kriteria_id] = $defaultSubkriteria->subkriteria_bobot;
                $userValuesByName[$kriteria->kriteria_nama] = [
                    'bobot' => $defaultSubkriteria->subkriteria_bobot,
                    'nama' => $defaultSubkriteria->subkriteria_nama
                ];
            }
        }
        
        // Siapkan nilai untuk tiap frame dan kriteria
        foreach ($frames as $frame) {
            $rawValues[$frame->frame_id] = [];
            $frameValuesByName[$frame->frame_merek] = [];
            
            foreach ($kriterias as $kriteria) {
                $frameSubkriteria = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id)->first();
                $rawValues[$frame->frame_id][$kriteria->kriteria_id] = $frameSubkriteria->subkriteria->subkriteria_bobot;
                $frameValuesByName[$frame->frame_merek][$kriteria->kriteria_nama] = [
                    'bobot' => $frameSubkriteria->subkriteria->subkriteria_bobot,
                    'nama' => $frameSubkriteria->subkriteria->subkriteria_nama
                ];
            }
        }
        
        Log::info('Raw Values:', $rawValues);
        
        // 3. Hitung GAP untuk setiap frame
        $gapValues = [];
        $gapBobot = [];
        $gapValuesByName = [];
        $gapBobotByName = [];
        
        foreach ($frames as $frame) {
            $gapValues[$frame->frame_id] = [];
            $gapBobot[$frame->frame_id] = [];
            $gapValuesByName[$frame->frame_merek] = [];
            $gapBobotByName[$frame->frame_merek] = [];
            
            foreach ($kriterias as $kriteria) {
                // Hitung selisih (GAP)
                $gap = $rawValues[$frame->frame_id][$kriteria->kriteria_id] - $userValues[$kriteria->kriteria_id];
                $gapValues[$frame->frame_id][$kriteria->kriteria_id] = $gap;
                $gapValuesByName[$frame->frame_merek][$kriteria->kriteria_nama] = $gap;
                
                // Konversi GAP ke nilai bobot sesuai tabel
                $bobotGap = $this->convertGapToBobot($gap);
                $gapBobot[$frame->frame_id][$kriteria->kriteria_id] = $bobotGap;
                $gapBobotByName[$frame->frame_merek][$kriteria->kriteria_nama] = $bobotGap;
            }
        }
        
        Log::info('GAP Values:', $gapValues);
        Log::info('GAP Weights:', $gapBobot);
        
        // 4. Implementasi metode SMART untuk perangkingan - UPDATED to match PenilaianController
        $finalScores = [];
        $finalScoresByName = [];
        $weightedScores = [];
        $weightedScoresByName = [];
        $normalizedWeights = [];
        
        // 2. Normalisasi bobot kriteria (dibagi dengan total) - UPDATED to match PenilaianController
        foreach ($kriterias as $kriteria) {
            // Pastikan kriteria_id ada di bobotKriteriaUser
            if (isset($bobotKriteriaUser[$kriteria->kriteria_id])) {
                // Normalisasi: nilai bobot dibagi total bobot (tanpa dikali 100)
                $normalizedWeights[$kriteria->kriteria_id] = $bobotKriteriaUser[$kriteria->kriteria_id] / $totalBobot;
            } else {
                // Fallback jika tidak ada, gunakan nilai default
                $normalizedWeights[$kriteria->kriteria_id] = $kriteria->bobot_kriteria / 100;
            }
        }
        
        Log::info('Normalized Weights:', $normalizedWeights);
        
        foreach ($frames as $frame) {
            $finalScores[$frame->frame_id] = 0;
            $finalScoresByName[$frame->frame_merek] = 0;
            $weightedScores[$frame->frame_id] = [];
            $weightedScoresByName[$frame->frame_merek] = [];
            
            foreach ($kriterias as $kriteria) {
                // UPDATED: Use normalized weights instead of dividing by 100
                $weightedValue = $normalizedWeights[$kriteria->kriteria_id] * $gapBobot[$frame->frame_id][$kriteria->kriteria_id];
                
                // Simpan nilai terbobot per kriteria
                $weightedScores[$frame->frame_id][$kriteria->kriteria_id] = $weightedValue;
                $weightedScoresByName[$frame->frame_merek][$kriteria->kriteria_nama] = $weightedValue;
                
                // Akumulasi untuk nilai akhir
                $finalScores[$frame->frame_id] += $weightedValue;
                $finalScoresByName[$frame->frame_merek] += $weightedValue;
            }
            
            // Round final scores to 2 decimal places
            $finalScores[$frame->frame_id] = round((float)$finalScores[$frame->frame_id], 2);
            $finalScoresByName[$frame->frame_merek] = round((float)$finalScoresByName[$frame->frame_merek], 2);
        }
        
        Log::info('Weighted Scores:', $weightedScores);
        Log::info('Final Scores:', $finalScores);
        
        // Sort frames by final score (for display)
        $sortedFrames = collect($finalScoresByName)->sortDesc()->all();
        
        // Prepare normalized bobot kriteria for display
        $bobotKriteriaDisplay = [];
        foreach ($kriterias as $kriteria) {
            // Ensure kriteria_id exists in bobotKriteriaUser
            if (isset($bobotKriteriaUser[$kriteria->kriteria_id])) {
                $bobotKriteriaDisplay[$kriteria->kriteria_nama] = $bobotKriteriaUser[$kriteria->kriteria_id];
            } else {
                $bobotKriteriaDisplay[$kriteria->kriteria_nama] = 0;
            }
        }
        
        // Return combined data for display
        return [
            'rawValues' => $frameValuesByName,
            'userValues' => $userValuesByName,
            'gapValues' => $gapValuesByName,
            'gapBobot' => $gapBobotByName,
            'weightedScores' => $weightedScoresByName,
            'finalScores' => $finalScoresByName,
            'sortedScores' => $sortedFrames,
            'bobotKriteria' => $bobotKriteriaDisplay,
            'normalizedWeights' => $normalizedWeights // Added to show normalized weights
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

    public function destroy(Penilaian $penilaian)
    {
        DB::beginTransaction();
        try {
            // Delete associated recommendations first
            Rekomendasi::where('penilaian_id', $penilaian->penilaian_id)->delete();
            
            // Then delete the penilaian and related data
            $penilaian->bobotKriterias()->delete();
            $penilaian->detailPenilaians()->delete();
            $penilaian->delete();

            DB::commit();
            return redirect()->route('rekomendasi.index')
                ->with('success', 'Riwayat rekomendasi berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rekomendasi destroy error: ' . $e->getMessage());
            return redirect()->route('rekomendasi.index')
                ->with('error', 'Gagal menghapus riwayat rekomendasi: ' . $e->getMessage());
        }
    }

    public function print(Penilaian $penilaian)
    {
        try {
            // Load the penilaian with all related data
            $penilaian->load([
                'detailPenilaians.kriteria', 
                'detailPenilaians.subkriteria',
                'bobotKriterias.kriteria',
                'rekomendasis' => function($query) {
                    $query->orderBy('nilai_akhir', 'desc') // Modified: sort by nilai_akhir
                          ->with('frame');
                }
            ]);
            
            // Load calculation details
            $detailPerhitungan = $this->hitungDetailProfileMatching($penilaian);
            
            // Generate PDF
            $pdf = PDF::loadView('rekomendasi.print', [
                'penilaian' => $penilaian,
                'rekomendasis' => $penilaian->rekomendasis,
                'detailPerhitungan' => $detailPerhitungan
            ]);

            // Download PDF
            return $pdf->download("rekomendasi_{$penilaian->nama_pelanggan}_{$penilaian->tgl_penilaian->format('Ymd')}.pdf");
        } catch (\Exception $e) {
            Log::error('Rekomendasi print error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencetak rekomendasi');
        }
    }

    public function detail(Penilaian $penilaian)
    {
        try {
            // Load the penilaian with all related data
            $penilaian->load([
                'detailPenilaians.kriteria', 
                'detailPenilaians.subkriteria',
                'bobotKriterias.kriteria',
                'rekomendasis' => function($query) {
                    $query->orderBy('nilai_akhir', 'desc') // Modified: sort by nilai_akhir
                          ->with('frame');
                }
            ]);
            
            // Load calculation details
            $detailPerhitungan = $this->hitungDetailProfileMatching($penilaian);
            
            return view('rekomendasi.detail', [
                'penilaian' => $penilaian,
                'rekomendasis' => $penilaian->rekomendasis,
                'detailPerhitungan' => $detailPerhitungan
            ]);
        } catch (\Exception $e) {
            Log::error('Rekomendasi detail error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memuat detail rekomendasi');
        }
    }
}