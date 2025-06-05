<?php

namespace App\Http\Controllers;

use App\Models\Penilaian;
use App\Models\DetailPenilaian;
use App\Models\Frame;
use App\Models\Kriteria;
use App\Models\RecommendationHistory;
use App\Models\RecommendationCriteria;
use App\Models\RecommendationSubkriteria;
use App\Models\RecommendationFrame;
use App\Models\BobotKriteria;
use App\Models\Rekomendasi;
use App\Models\Subkriteria;
use App\Models\FrameSubkriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\Customer;
use App\Services\FileUploadService;

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
        if ($customer instanceof \Illuminate\Database\Eloquent\Collection) {
            $customer = $customer->first();
            
            // If still empty, throw an exception
            if (!$customer) {
                throw new \Exception("Customer with ID {$request->customer_id} not found");
            }
        }

        // Perform profile matching calculation
        $hasilPerhitungan = $this->hitungProfileMatching($request->subkriteria, $request->bobot_kriteria, $totalBobot);

        // Begin database transaction
        DB::beginTransaction();

        // Create recommendation history record
        $recommendationHistory = $this->createRecommendationHistory($customer, $request, $hasilPerhitungan, $totalBobot);
        
        // Store all detailed recommendation data
        $this->storeDetailedRecommendationData($recommendationHistory, $request, $hasilPerhitungan, $totalBobot);
        
        // Commit transaction
        DB::commit();

        // Prepare the response data
        $kriteria_dipilih = $this->prepareKriteriaDipilih($request->subkriteria);
        
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
            'recommendation_history_id' => $recommendationHistory->id,
            'success' => 'Rekomendasi berhasil disimpan'
        ]);
    } catch (\Exception $e) {
        // Rollback transaction in case of error
        DB::rollBack();
        
        // Log the error
        Log::error('Error in penilaian store: ' . $e->getMessage());
        Log::error($e->getTraceAsString());

        return response()->json([
            'error' => 'Terjadi kesalahan dalam memproses penilaian: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Create the main recommendation history record
 * 
 * @param Customer $customer
 * @param Request $request
 * @param array $hasilPerhitungan
 * @param float $totalBobot
 * @return RecommendationHistory
 */
private function createRecommendationHistory($customer, $request, $hasilPerhitungan, $totalBobot)
{
    // Handle if customer is a collection
    if ($customer instanceof \Illuminate\Database\Eloquent\Collection) {
        $customer = $customer->first();
        
        // If empty, throw exception
        if (!$customer) {
            throw new \Exception("Customer not found");
        }
    }
    
    // Rest of the method remains the same...
    $kriteria_dipilih = $this->prepareKriteriaDipilih($request->subkriteria);
    $rekomendasiData = $this->processImageFiles($hasilPerhitungan['rekomendasi']);
    
    return RecommendationHistory::create([
        'customer_id' => $customer->customer_id,
        'user_id' => Auth::id(),
        'customer_name' => $customer->name,
        'customer_phone' => $customer->phone,
        'customer_address' => $customer->address,
        'kriteria_dipilih' => $kriteria_dipilih,
        'bobot_kriteria' => $request->bobot_kriteria,
        'rekomendasi_data' => $rekomendasiData,
        'perhitungan_detail' => $hasilPerhitungan
    ]);
}

/**
 * Store detailed recommendation data in related tables
 * 
 * @param RecommendationHistory $recommendationHistory
 * @param Request $request
 * @param array $hasilPerhitungan
 * @param float $totalBobot
 * @return void
 */
private function storeDetailedRecommendationData($recommendationHistory, $request, $hasilPerhitungan, $totalBobot)
{
    // Store criteria data
    $this->storeRecommendationCriteria($recommendationHistory, $request->bobot_kriteria, $totalBobot);
    
    // Store subkriteria data
    $this->storeRecommendationSubkriteria($recommendationHistory, $request->subkriteria);
    
    // Store frame data
    $this->storeRecommendationFrames($recommendationHistory, $hasilPerhitungan['rekomendasi']);
}

/**
 * Store recommendation criteria records
 * 
 * @param RecommendationHistory $recommendationHistory
 * @param array $bobotKriteria
 * @param float $totalBobot
 * @return void
 */
private function storeRecommendationCriteria($recommendationHistory, $bobotKriteria, $totalBobot)
{
    foreach ($bobotKriteria as $kriteria_id => $bobot) {
        $kriteria = Kriteria::find($kriteria_id);
        if ($kriteria) {
            RecommendationCriteria::create([
                'recommendation_history_id' => $recommendationHistory->recommendation_history_id,
                'kriteria_id' => $kriteria_id,
                'kriteria_nama' => $kriteria->kriteria_nama,
                'kriteria_bobot' => $bobot / $totalBobot, // Normalized weight
            ]);
        }
    }
}

/**
 * Store recommendation subkriteria records
 * 
 * @param RecommendationHistory $recommendationHistory
 * @param array $subkriteriaData
 * @return void
 */
private function storeRecommendationSubkriteria($recommendationHistory, $subkriteriaData)
{
    foreach ($subkriteriaData as $kriteria_id => $subkriteria_id) {
        $subkriteria = Subkriteria::find($subkriteria_id);
        if ($subkriteria) {
            RecommendationSubkriteria::create([
                'recommendation_history_id' => $recommendationHistory->recommendation_history_id,
                'kriteria_id' => $kriteria_id,
                'subkriteria_id' => $subkriteria_id,
                'subkriteria_nama' => $subkriteria->subkriteria_nama,
                'subkriteria_bobot' => $subkriteria->subkriteria_bobot,
            ]);
        }
    }
}

/**
 * Store recommendation frame records
 * 
 * @param RecommendationHistory $recommendationHistory
 * @param array $rekomendasiFrames
 * @return void
 */
private function storeRecommendationFrames($recommendationHistory, $rekomendasiFrames)
{
    $rank = 1;
    foreach ($rekomendasiFrames as $item) {
        RecommendationFrame::create([
            'recommendation_history_id' => $recommendationHistory->recommendation_history_id,
            'frame_id' => $item['frame']->frame_id,
            'frame_nama' => $item['frame']->frame_merek . ' ' . $item['frame']->frame_model,
            'skor_akhir' => $item['score'],
            'peringkat' => $rank++,
        ]);
    }
}

/**
 * Process image files for frames
 * 
 * @param array $rekomendasiData
 * @return array
 */
private function processImageFiles($rekomendasiData)
{
    $rekomendasiCopy = $rekomendasiData;
    
    foreach ($rekomendasiCopy as &$item) {
        // Process image copy
        $frameFoto = $item['frame']['frame_foto'] ?? null;
        
        if ($frameFoto) {
            // Check if file exists in public/storage
            if (FileUploadService::existsInPublicStorage($frameFoto)) {
                // Generate nama unik untuk file di folder history_images
                $newFilename = 'history_images/' . uniqid() . '_' . basename($frameFoto);
                
                // Backup/copy file ke direktori history menggunakan FileUploadService
                $backupResult = FileUploadService::backupFile($frameFoto, 'history_images');
                
                if ($backupResult) {
                    // Update path foto di data rekomendasi dengan hasil backup
                    $item['frame']['frame_foto'] = $backupResult;
                } else {
                    // Jika backup gagal, coba copy manual
                    try {
                        $sourcePath = public_path('storage/' . $frameFoto);
                        $destinationPath = public_path('storage/history_images');
                        
                        // Pastikan folder history_images ada
                        if (!File::exists($destinationPath)) {
                            File::makeDirectory($destinationPath, 0755, true);
                        }
                        
                        $newFilename = 'history_images/' . uniqid() . '_' . basename($frameFoto);
                        $destinationFile = public_path('storage/' . $newFilename);
                        
                        // Copy file
                        File::copy($sourcePath, $destinationFile);
                        $item['frame']['frame_foto'] = $newFilename;
                        
                    } catch (\Exception $e) {
                        Log::error('Error copying frame image: ' . $e->getMessage());
                        $item['frame']['frame_foto'] = null;
                    }
                }
            } else {
                Log::warning('Frame image not found: ' . $frameFoto);
                $item['frame']['frame_foto'] = null; // Jika foto tidak ada
            }
        } else {
            $item['frame']['frame_foto'] = null;
        }
        
        // Make sure frameSubkriterias are properly preserved with manual values
        if (!isset($item['frame']['all_subkriteria']) && isset($item['frame']['frameSubkriterias'])) {
            $allSubkriteria = [];
            foreach ($item['frame']['frameSubkriterias'] as $fsk) {
                // Capture manual_value if it exists
                $manualValue = isset($fsk['manual_value']) ? $fsk['manual_value'] : null;
                
                if (isset($fsk['subkriteria'])) {
                    $subk = $fsk['subkriteria'];
                    // Create a flattened structure that's easier to work with in the view
                    // Include manual_value in the structure
                    $allSubkriteria[] = [
                        'kriteria_id' => $fsk['kriteria_id'],
                        'subkriteria_id' => $subk['subkriteria_id'],
                        'subkriteria_nama' => $subk['subkriteria_nama'],
                        'subkriteria_bobot' => $subk['subkriteria_bobot'],
                        'manual_value' => $manualValue, // Store manual value
                        'tipe_subkriteria' => $subk['tipe_subkriteria'] ?? 'checkbox' // Default to checkbox if not specified
                    ];
                }
            }
            // Add this flattened structure to the frame data
            $item['frame']['all_subkriteria'] = $allSubkriteria;
        }
    }
    unset($item); // Hapus reference terakhir
    
    return $rekomendasiCopy;
}

/**
 * Prepare the kriteria_dipilih array from subkriteria data
 * 
 * @param array $subkriteriaData
 * @return array
 */
private function prepareKriteriaDipilih($subkriteriaData)
{
    $kriteria_dipilih = [];
    
    foreach ($subkriteriaData as $kriteria_id => $subkriteria_id) {
        $kriteria = Kriteria::find($kriteria_id);
        $subkriteria = Subkriteria::find($subkriteria_id);
        
        if ($kriteria && $subkriteria) {
            $kriteria_dipilih[$kriteria->kriteria_nama] = $subkriteria->subkriteria_nama;
        }
    }
    
    return $kriteria_dipilih;
}
private function hitungProfileMatching($subkriteriaUser, $bobotKriteriaUser, $totalBobot)
{
        try {
        // 1. Get all criteria and frames data
        $kriterias = Kriteria::with('subkriterias')->get();
        $frames = Frame::with(['frameSubkriterias' => function($query) use ($kriterias) {
            $query->whereIn('kriteria_id', $kriterias->pluck('kriteria_id'))
                  ->with('subkriteria');
        }])
        ->orderBy('created_at', 'asc') 
        ->get();

        // Ensure all frames have values for each criteria
        foreach ($frames as $frame) {
            foreach ($kriterias as $kriteria) {
                $frameSubkriterias = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id);
                if ($frameSubkriterias->isEmpty()) {
                    throw new \Exception("Frame {$frame->frame_merek} tidak memiliki nilai untuk kriteria {$kriteria->kriteria_nama}");
                }
            }
        }

        // 2. Normalize criteria weights
        $bobotKriteria = [];
        foreach ($kriterias as $kriteria) {
            $kriteriaId = $kriteria->kriteria_id;
            
            // Langsung gunakan bobot user tanpa fallback
            $normalizedWeight = $bobotKriteriaUser[$kriteriaId] / $totalBobot;
            
            // Format ke 4 angka di belakang koma dan convert kembali ke float
            $bobotKriteria[$kriteriaId] = (float) number_format($normalizedWeight, 4, '.', '');
        }
        
        Log::info('Normalized Weights:', $bobotKriteria);
        
        // 3. Calculate GAP for each frame
        $gapValues = [];
        $gapBobot = [];
        $bestSubkriteria = [];
        
        foreach ($frames as $frame) {
            $frameId = $frame->frame_id;
            $gapValues[$frameId] = [];
            $gapBobot[$frameId] = [];
            $bestSubkriteria[$frameId] = [];
            
            foreach ($kriterias as $kriteria) {
                $kriteriaId = $kriteria->kriteria_id;
                $frameSubkriterias = $frame->frameSubkriterias->where('kriteria_id', $kriteriaId);
                
                // Validate user subcriteria
                if (!isset($subkriteriaUser[$kriteriaId])) {
                    throw new \Exception("Tidak ada pilihan subkriteria untuk kriteria {$kriteria->kriteria_nama}");
                }
                
                $userSubkriteria = Subkriteria::findOrFail($subkriteriaUser[$kriteriaId]);
                
                // Handle null user subkriteria bobot
                $userBobot = $userSubkriteria->subkriteria_bobot;
                if ($userBobot === null) {
                    throw new \Exception("Bobot untuk subkriteria user '{$userSubkriteria->subkriteria_nama}' tidak valid");
                }
                
                $bestGapBobot = -1;
                $bestGap = PHP_INT_MAX;
                $selected = null;
                
                foreach ($frameSubkriterias as $frameSubkriteria) {
                    // Validate frame subkriteria bobot
                    $frameBobot = $frameSubkriteria->subkriteria->subkriteria_bobot;
                    if ($frameBobot === null) {
                        Log::warning("Bobot null for subkriteria '{$frameSubkriteria->subkriteria->subkriteria_nama}' in frame {$frame->frame_merek}");
                        continue; // Skip this subkriteria
                    }
                    
                    // Calculate GAP
                    $gap = $frameBobot - $userBobot;
                    $bobotGap = $this->convertGapToBobot($gap);
                    
                    // Select subcriteria with highest GAP weight (or smallest GAP if equal)
                    if ($bobotGap > $bestGapBobot || ($bobotGap == $bestGapBobot && abs($gap) < abs($bestGap))) {
                        $bestGapBobot = $bobotGap;
                        $bestGap = $gap;
                        $selected = $frameSubkriteria;
                    }
                }
                
                // Ensure we found a valid subkriteria
                if ($selected === null) {
                    throw new \Exception("Tidak dapat menemukan subkriteria yang valid untuk frame {$frame->frame_merek} dan kriteria {$kriteria->kriteria_nama}");
                }
                
                // Save best GAP values and weights
                $gapValues[$frameId][$kriteriaId] = $bestGap;
                $gapBobot[$frameId][$kriteriaId] = $bestGapBobot;
                $bestSubkriteria[$frameId][$kriteriaId] = $selected;
            }
        }
        
        Log::info('GAP Values:', $gapValues);
        Log::info('GAP Weights:', $gapBobot);
        
        // 4. Implement SMART ranking method - FIXED VERSION
        $finalScores = [];
        foreach ($frames as $frame) {
            $frameId = $frame->frame_id;
            $calculations = [];
            
            foreach ($kriterias as $kriteria) {
                $kriteriaId = $kriteria->kriteria_id;

                if (isset($bobotKriteria[$kriteriaId]) && isset($gapBobot[$frameId][$kriteriaId])) {
                    // Hitung dan bulatkan per item seperti di tampilan
                    $calculation = $bobotKriteria[$kriteriaId] * $gapBobot[$frameId][$kriteriaId];
                    $calculations[] = round($calculation, 4);
                }
            }
            
            // Jumlahkan hasil yang sudah dibulatkan
            $finalScores[$frameId] = round(array_sum($calculations), 4);
        }
        
        // 5. Create two collections of frames
        $sortedFrames = $frames->map(function($frame) use ($finalScores) {
            $frame->calculated_score = round((float)($finalScores[$frame->frame_id] ?? 0), 4);
            return $frame;
        })
        ->sortBy('created_at')           
        ->sortByDesc('calculated_score') 
        ->values();                     
        
        $orderedFrames = $frames->sortBy('created_at')->values(); 
        
        // 6. Map data for the view
        $mapFrameData = function ($collection) use ($finalScores, $gapValues, $gapBobot, $subkriteriaUser, $kriterias, $bestSubkriteria) {
            return $collection->map(function ($frame) use ($finalScores, $gapValues, $gapBobot, $subkriteriaUser, $kriterias, $bestSubkriteria) {
                $frameId = $frame->frame_id;
                $details = [];
                
                foreach ($kriterias as $kriteria) {
                    $kriteriaId = $kriteria->kriteria_id;
                    
                    // Ensure bestSubkriteria exists
                    if (!isset($bestSubkriteria[$frameId][$kriteriaId])) {
                        continue;
                    }
                    
                    $selectedSubkriteria = $bestSubkriteria[$frameId][$kriteriaId];
                    $userSubkriteria = Subkriteria::findOrFail($subkriteriaUser[$kriteriaId]);
                    
                    $details[] = [
                        'kriteria' => $kriteria,
                        'frame_subkriteria' => $selectedSubkriteria->subkriteria,
                        'user_subkriteria' => $userSubkriteria,
                    ];
                }
                
                return [
                    'frame' => $frame,
                    'score' => round((float)($finalScores[$frameId] ?? 0), 4), 
                    'gap_values' => $gapValues[$frameId] ?? [],
                    'gap_bobot' => $gapBobot[$frameId] ?? [],
                    'details' => $details,
                ];
            })->values(); // Reset array keys
        };
        
        // Collect all required data
        return [
            'rekomendasi' => $mapFrameData($sortedFrames),  
            'orderedRekomendasi' => $mapFrameData($orderedFrames), 
            'kriterias' => $kriterias,
            'bobotKriteria' => $bobotKriteria,
            'totalBobot' => $totalBobot,
            'bobotKriteriaUser' => $bobotKriteriaUser,
        ];
    } catch (\Exception $e) {
        Log::error('Error in hitungProfileMatching: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Convert GAP value to weight based on Profile Matching method
 * 
 * @param int|float $gap
 * @return float
 */
private function convertGapToBobot($gap)
{
    // Ensure gap is numeric
    if (!is_numeric($gap)) {
        Log::warning('Non-numeric GAP value: ' . var_export($gap, true));
        return 0;
    }
    
    // Convert GAP to weight according to Profile Matching rules
    switch (true) {
        case $gap == 0:
            return 5.0; // No difference (perfect)
        case $gap == 1:
            return 4.5; // Excess 1 level
        case $gap == -1:
            return 4.0; // Shortage 1 level
        case $gap == 2:
            return 3.5; // Excess 2 levels
        case $gap == -2:
            return 3.0; // Shortage 2 levels
        case $gap == 3:
            return 2.5; // Excess 3 levels
        case $gap == -3:
            return 2.0; // Shortage 3 levels
        case $gap == 4:
            return 1.5; // Excess 4 levels
        case $gap == -4:
            return 1.0; // Shortage 4 levels
        default:
            return 0.0; // Gap too large
    }
}
}