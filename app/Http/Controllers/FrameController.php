<?php

namespace App\Http\Controllers;

use App\Models\Frame;
use App\Models\Kriteria;
use App\Models\Subkriteria;
use App\Models\FrameSubkriteria;
use App\Services\ImageComparisonService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;



class FrameController extends Controller
{
    protected $imageComparisonService;
    
    public function __construct(ImageComparisonService $imageComparisonService)
    {
        $this->imageComparisonService = $imageComparisonService;
    }
    
    // FrameController.php (index method)
    public function index(Request $request)
    {
        // Mendapatkan query pencarian
        $search = $request->input('search');
        
        // Mendapatkan semua frame untuk mengecek total update yang diperlukan
        $allFrames = Frame::with(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria'])
                          ->get();
        
        // Mendapatkan semua kriteria
        $allKriterias = Kriteria::all()->pluck('kriteria_id')->toArray();
        
        // Cek frame mana yang butuh update
        $totalNeedsUpdate = 0;
        foreach ($allFrames as $frame) {
            // Dapatkan ID kriteria yang frame ini memiliki subkriteria
            $frameKriterias = $frame->frameSubkriterias->pluck('kriteria_id')->toArray();
            
            // Cek apakah ada kriteria yang hilang
            $missingKriterias = array_diff($allKriterias, $frameKriterias);
            
            if (count($missingKriterias) > 0) {
                $totalNeedsUpdate++;
            }
        }
        
        // Query builder untuk frame dengan fitur pencarian
        $framesQuery = Frame::with(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria']);
        
        // Terapakan filter pencarian jika disediakan
        if ($search) {
            $framesQuery->where(function($query) use ($search) {
                $query->where('frame_merek', 'like', '%' . $search . '%')
                      ->orWhere('frame_lokasi', 'like', '%' . $search . '%');
            });
        }
        
        // Dapatkan frame dengan paginasi
        $frames = $framesQuery->orderBy('frame_id', 'asc')
                              ->paginate(20)
                              ->appends(['search' => $search]); // Tetap menyimpan parameter pencarian di link paginasi
        
        // Cek frame mana di halaman saat ini yang butuh update
        $frameNeedsUpdate = [];
        foreach ($frames as $frame) {
            // Dapatkan ID kriteria yang frame ini memiliki subkriteria
            $frameKriterias = $frame->frameSubkriterias->pluck('kriteria_id')->toArray();
            
            // Cek apakah ada kriteria yang hilang
            $missingKriterias = array_diff($allKriterias, $frameKriterias);
            
            if (count($missingKriterias) > 0) {
                $frameNeedsUpdate[$frame->frame_id] = $missingKriterias;
            }
        }
        
    
        return view('frame.index', compact('frames', 'frameNeedsUpdate', 'totalNeedsUpdate', 'search'));
    }

    // Menampilkan informasi pembaruan untuk frame tertentu
    public function checkUpdates(Frame $frame)
    {
        // Dapatkan semua kriteria
        $allKriterias = Kriteria::with('subkriterias')->get();
        $frameKriterias = $frame->frameSubkriterias->pluck('kriteria_id')->unique()->toArray();
        
        $missingKriterias = [];
        foreach ($allKriterias as $kriteria) {
            if (!in_array($kriteria->kriteria_id, $frameKriterias)) {
                $missingKriterias[] = $kriteria;
            }
        }
        
        // Periksa subkriteria yang tidak valid
        $outdatedSubkriterias = [];
        foreach ($frame->frameSubkriterias as $frameSubkriteria) {
            if (!$frameSubkriteria->subkriteria) {
                $outdatedSubkriterias[] = [
                    'kriteria' => $frameSubkriteria->kriteria,
                    'message' => 'Subkriteria telah dihapus dan perlu diperbarui'
                ];
            }
        }
        
        return view('frame.check-updates', compact('frame', 'missingKriterias', 'outdatedSubkriterias'));
    }

    public function show(Frame $frame)
    {
        $kriterias = Kriteria::with('subkriterias')->get();
        $frame->load('frameSubkriterias');
        return view('frame.show', compact('frame', 'kriterias'));
    }

    // FrameController.php
    public function needsUpdate()
    {
        $allKriterias = Kriteria::all();
        
        // Retrieve frames with pagination
        $frames = Frame::with(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria'])
                    ->get();
        
        // Filter frames that need updates
        $framesNeedingUpdate = $frames->filter(function($frame) use ($allKriterias) {
            // Get all kriteria IDs
            $allKriteriaIds = $allKriterias->pluck('kriteria_id');
            
            // Get kriteria IDs for this frame's subkriterias
            $frameKriteriaIds = $frame->frameSubkriterias->pluck('kriteria_id')->unique();
            
            // Check for missing kriteria
            $missingCount = $allKriteriaIds->diff($frameKriteriaIds)->count();
            
            // Check for invalid or deleted subkriterias
            $invalidCount = $frame->frameSubkriterias->filter(function($fs) {
                // Check if the related subkriteria exists
                return $fs->kriteria_id && (!$fs->subkriteria || !$fs->kriteria);
            })->count();
            
            return $missingCount > 0 || $invalidCount > 0;
        });

        // Total frames needing update (before pagination)
        $totalFramesNeedingUpdate = $framesNeedingUpdate->count();

        // Manually paginate the filtered results
        $perPage = 20;
        $currentPage = request()->get('page', 1);
        $pagedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $framesNeedingUpdate->forPage($currentPage, $perPage),
            $framesNeedingUpdate->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        return view('frame.needs-update', [
            'framesNeedingUpdate' => $pagedData, 
            'allKriterias' => $allKriterias,
            'totalFramesNeedingUpdate' => $totalFramesNeedingUpdate
        ]);
    }

    public function searchByImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        try {
            $similarFrames = $this->imageComparisonService->findSimilarFrame($request->file('image'), 5);
            
            // Jika hasil berupa array (multiple frames)
            if (is_array($similarFrames) && !empty($similarFrames)) {
                return view('frame.image-search-results', [
                    'frames' => $similarFrames,
                    'imageSearch' => true
                ]);
            } 
            // Jika hasil single frame (backward compatibility)
            elseif ($similarFrames) {
                return view('frame.image-search-results', [
                    'frames' => [$similarFrames], // Masukkan ke array
                    'imageSearch' => true
                ]);
            }
            
            // Jika tidak ada hasil
            return view('frame.image-search-results', [
                'frames' => [],
                'imageSearch' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error in image search: ' . $e->getMessage());
            return redirect()->route('frame.index')
                            ->with('error', 'Terjadi kesalahan saat memproses gambar.');
        }
    }
private function backupImageForLogs($originalPath)
{
    // Skip if no image
    if (!$originalPath) {
        return null;
    }
    
    // Make sure logs_archive directory exists
    $logsArchiveDir = 'logs_archive';
    if (!Storage::disk('public')->exists($logsArchiveDir)) {
        Storage::disk('public')->makeDirectory($logsArchiveDir);
    }
    
    // Generate a unique name for the archived file
    $fileName = 'log_' . time() . '_' . basename($originalPath);
    $archivePath = $logsArchiveDir . '/' . $fileName;
    
    // Only proceed if original file exists
    if (Storage::disk('public')->exists($originalPath)) {
        // Copy the file to the logs archive
        Storage::disk('public')->copy($originalPath, $archivePath);
        
        // Return the archive path
        return $archivePath;
    }
    
    return null;
}
/**
 * Get all similar frame IDs from similarity details with improved filtering
 * Modified to properly handle multiple subkriteria selections
 */
private function getAllSimilarFrameIds($similarityDetails)
{
    $allIds = [];
    
    foreach ($similarityDetails as $type => $details) {
        if (isset($details['frames']) && is_array($details['frames'])) {
            $allIds = array_merge($allIds, $details['frames']);
        }
        if (isset($details['frame_id'])) {
            $allIds[] = $details['frame_id'];
        }
    }
    
    // Get the frames with the same brand as the new frame (case-insensitive)
    if (!empty($allIds)) {
        $formData = session('frame_form_data', []);
        $brand = $formData['frame_merek'] ?? null;
        
        if ($brand) {
            // Filter IDs to include only frames with the same brand (case-insensitive)
            $sameFrameIds = Frame::whereIn('frame_id', $allIds)
                ->whereRaw('LOWER(frame_merek) = ?', [strtolower($brand)])
                ->pluck('frame_id')
                ->toArray();
                
            return $sameFrameIds;
        }
    }
    
    return array_unique($allIds);
}

/**
 * Find frames with similar data with improved multiple subkriteria handling
 * This version identifies frames with overlapping criteria rather than only exact matches
 */
private function findFramesWithSimilarData(Request $request, $excludeFrameId = null)
{
    // Step 1: Find frames with similar merek and lokasi (case-insensitive)
    $query = Frame::whereRaw('LOWER(frame_merek) = ?', [strtolower($request->frame_merek)])
                  ->whereRaw('LOWER(frame_lokasi) = ?', [strtolower($request->frame_lokasi)]);
    
    if ($excludeFrameId) {
        $query->where('frame_id', '!=', $excludeFrameId);
    }
    
    $similarMerekAndLokasi = $query->get();
    
    if ($similarMerekAndLokasi->isEmpty()) {
        return [];
    }

    // Step 2: Extract requested criteria with validation
    $requestedCriteriaByType = [];
    $input_types = $request->input('input_type', []);

    // Process checkbox criteria
    foreach ($request->input('nilai', []) as $kriteria_id => $subkriteria_ids) {
        // Default to checkbox if not specified
        if (($input_types[$kriteria_id] ?? 'checkbox') == 'checkbox') {
            $requestedCriteriaByType[$kriteria_id . '_checkbox'] = array_unique((array)$subkriteria_ids);
        }
    }

    // Process manual criteria
    foreach ($request->input('nilai_manual', []) as $kriteria_id => $value) {
        if (($input_types[$kriteria_id] ?? 'manual') == 'manual' && !empty($value)) {
            if ($subkriteria = $this->findSubkriteriaForValue($kriteria_id, $value)) {
                $requestedCriteriaByType[$kriteria_id . '_manual'] = [
                    ['subkriteria_id' => $subkriteria->subkriteria_id, 'value' => $value]
                ];
            }
        }
    }

    if (empty($requestedCriteriaByType)) {
        return [];
    }

    // Step 3: Strict comparison
    $similarFrames = [];
    
    foreach ($similarMerekAndLokasi as $frame) {
        $frame->load('frameSubkriterias.subkriteria');
        
        $frameCriteria = [
            'checkbox' => [],
            'manual' => []
        ];
        
        foreach ($frame->frameSubkriterias as $fs) {
            $type = $fs->manual_value !== null ? 'manual' : 'checkbox';
            $frameCriteria[$type][$fs->kriteria_id][] = $fs->manual_value ?? $fs->subkriteria_id;
        }

        $allMatch = true;
        
        foreach ($requestedCriteriaByType as $key => $requested) {
            list($kriteria_id, $type) = explode('_', $key);
            
            // Check if kriteria exists in frame
            if (!isset($frameCriteria[$type][$kriteria_id])) {
                $allMatch = false;
                break;
            }
            
            $frameValues = $frameCriteria[$type][$kriteria_id];
            
            // Check values
            if ($type == 'checkbox') {
                if (count(array_intersect($requested, $frameValues)) == 0) {
                    $allMatch = false;
                    break;
                }
            } else {
                $found = false;
                foreach ($frameValues as $fv) {
                    if (in_array($fv, array_column($requested, 'value'))) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $allMatch = false;
                    break;
                }
            }
        }
        
        if ($allMatch) {
            $similarFrames[] = $frame->frame_id;
        }
    }

    return $similarFrames;
}

/**
 * Enhanced version of checkForSimilarFrames with better handling for multiple subkriteria
 * 
 * @param Request $request
 * @param int|null $excludeFrameId Frame ID to exclude from similarity check (for updates)
 * @return array
 */
private function checkForSimilarFrames(Request $request, $excludeFrameId = null)
{
    $result = [
        'similarFrame' => null,
        'allSimilarFrames' => [],
        'similarityDetails' => [
            'exactMatches' => [],
            'partialMatches' => []
        ]
    ];

    // 1. Check for similar images (both new upload and existing)
    $imageProcessed = false;

    // Case 1: New image uploaded
    if ($request->hasFile('frame_foto')) {
        $similarFrames = $this->imageComparisonService->findSimilarFrame($request->file('frame_foto'), true);
        $imageProcessed = true;
    }
    // Case 2: Existing image provided
    elseif ($request->has('existing_frame_foto')) {
        $existingFotoPath = $request->input('existing_frame_foto');
        
        if (Storage::disk('public')->exists($existingFotoPath)) {
            // Create UploadedFile instance from existing image
            $filePath = Storage::disk('public')->path($existingFotoPath);
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $filePath,
                basename($filePath),
                mime_content_type($filePath),
                null,
                true // Test mode
            );
            
            $similarFrames = $this->imageComparisonService->findSimilarFrame($uploadedFile, true);
            $imageProcessed = true;
            
            Log::info('Performed image comparison using existing photo', [
                'path' => $existingFotoPath
            ]);
        } else {
            Log::warning('Existing frame photo not found', ['path' => $existingFotoPath]);
        }
    }

    // Process filtered similar frames if image was processed
    if ($imageProcessed && isset($similarFrames)) {
        $filteredSimilarFrames = collect($similarFrames)->filter(function($frame) use ($request, $excludeFrameId) {
            return strtolower($frame->frame_merek) === strtolower($request->frame_merek) && 
                   (!$excludeFrameId || $frame->frame_id != $excludeFrameId);
        })->values()->all();

        if (!empty($filteredSimilarFrames)) {
            $result['allSimilarFrames'] = $filteredSimilarFrames;
            $result['similarFrame'] = $filteredSimilarFrames[0];
            
            $frameIds = array_map(function($frame) {
                return $frame->frame_id;
            }, $filteredSimilarFrames);
            
            $result['similarityDetails']['image'] = [
                'similar' => true,
                'message' => 'Foto frame serupa dengan ' . count($filteredSimilarFrames) . 
                             ' frame yang sudah ada dengan merek yang sama',
                'frame_ids' => $frameIds
            ];
        }
    }

    // 2. Find frames with similar data (existing logic)
    $framesWithSimilarData = $this->findFramesWithSimilarData($request, $excludeFrameId);
    
    if (!empty($framesWithSimilarData)) {
        $result['similarityDetails']['data'] = [
            'similar' => true,
            'message' => 'Data frame (merek, lokasi, kriteria) serupa dengan ' . count($framesWithSimilarData) . ' frame lain',
            'frames' => $framesWithSimilarData
        ];

        if (!$result['similarFrame'] && !empty($framesWithSimilarData)) {
            $result['similarFrame'] = Frame::find($framesWithSimilarData[0]);
            $result['allSimilarFrames'] = Frame::whereIn('frame_id', $framesWithSimilarData)->get()->all();
        }
    }

    return $result;
}

/**
 * Helper function to sort criteria array for easier comparison
 *
 * @param array $criteria
 * @return array
 */
private function sortCriteriaForComparison(array $criteria)
{
    // First sort by kriteria_id
    usort($criteria, function($a, $b) {
        if ($a['kriteria_id'] != $b['kriteria_id']) {
            return $a['kriteria_id'] - $b['kriteria_id'];
        }
        
        // Then by input_type
        if ($a['input_type'] != $b['input_type']) {
            return strcmp($a['input_type'], $b['input_type']);
        }
        
        // Then by subkriteria_id
        return $a['subkriteria_id'] - $b['subkriteria_id'];
    });
    
    return $criteria;
}

    
    /**
 * Find the appropriate subkriteria for a given value
 * 
 * @param int $kriteria_id
 * @param mixed $value
 * @return Subkriteria|null
 */
private function findSubkriteriaForValue($kriteria_id, $value)
{
    if (empty($value) && $value !== '0' && $value !== 0) {
        Log::warning('Empty value provided for kriteria', [
            'kriteria_id' => $kriteria_id,
            'value' => $value
        ]);
        return null;
    }

    $value = (float) $value;
    $subkriterias = Subkriteria::where('kriteria_id', $kriteria_id)
                              ->where('tipe_subkriteria', 'rentang nilai')
                              ->get();
    
    if ($subkriterias->isEmpty()) {
        Log::warning('No range-type subkriteria found for kriteria', [
            'kriteria_id' => $kriteria_id
        ]);
        return null;
    }
    
    Log::info('Searching for matching subkriteria', [
        'kriteria_id' => $kriteria_id,
        'value' => $value,
        'available_subkriterias' => $subkriterias->count()
    ]);
    
    $matchedSubkriteria = null;
    
    foreach ($subkriterias as $subkriteria) {
        $minimum = is_null($subkriteria->nilai_minimum) ? -INF : (float) $subkriteria->nilai_minimum;
        $maximum = is_null($subkriteria->nilai_maksimum) ? INF : (float) $subkriteria->nilai_maksimum;
        
        // Handle different operators
        switch ($subkriteria->operator) {
            case '<':
                if ($value < $maximum) {
                    $matchedSubkriteria = $subkriteria;
                    Log::info('Found matching subkriteria with < operator', [
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'value' => $value,
                        'maximum' => $maximum
                    ]);
                }
                break;
                
            case '<=':
                if ($value <= $maximum) {
                    $matchedSubkriteria = $subkriteria;
                    Log::info('Found matching subkriteria with <= operator', [
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'value' => $value,
                        'maximum' => $maximum
                    ]);
                }
                break;
                
            case '>':
                if ($value > $minimum) {
                    $matchedSubkriteria = $subkriteria;
                    Log::info('Found matching subkriteria with > operator', [
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'value' => $value,
                        'minimum' => $minimum
                    ]);
                }
                break;
                
            case '>=':
                if ($value >= $minimum) {
                    $matchedSubkriteria = $subkriteria;
                    Log::info('Found matching subkriteria with >= operator', [
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'value' => $value,
                        'minimum' => $minimum
                    ]);
                }
                break;
                
            case 'between':
            default:
                if ($value >= $minimum && $value <= $maximum) {
                    $matchedSubkriteria = $subkriteria;
                    Log::info('Found matching subkriteria with between operator', [
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'value' => $value,
                        'minimum' => $minimum,
                        'maximum' => $maximum
                    ]);
                }
                break;
        }
        
        // If we found a match, return it immediately
        if ($matchedSubkriteria) {
            return $matchedSubkriteria;
        }
    }
    
    Log::warning('No matching subkriteria found for value', [
        'kriteria_id' => $kriteria_id,
        'value' => $value
    ]);
    
    return null;
}



/**
 * Helper method to prepare comparison data between the frame and form data
 * This helps visualize the differences in the confirmation view
 * 
 * @param Frame|Collection $similarFrame - Single Frame model or Collection of Frame models
 * @param array $formData
 * @return array
 */
private function prepareComparisonData($similarFrame, $formData)
{
    // Handle case when a Collection is passed instead of a single Frame
    if ($similarFrame instanceof Collection) {
        // Use the first frame from the collection
        if ($similarFrame->isEmpty()) {
            return []; // Return empty comparison if collection is empty
        }
        $similarFrame = $similarFrame->first();
    }
    
    // Now continue with the original code
    $comparison = [];
    
    // Extract existing frame's criteria
    $existingCriteria = [];
    foreach ($similarFrame->frameSubkriterias as $fs) {
        $kriteria_id = $fs->kriteria_id;
        $subkriteria_id = $fs->subkriteria_id;
        $kriteria_name = $fs->kriteria->nama_kriteria ?? 'Unknown';
        $subkriteria_name = $fs->subkriteria->nama_subkriteria ?? 'Unknown';
        $input_type = $fs->manual_value !== null ? 'manual' : 'checkbox';
        $value = $fs->manual_value;
        
        if (!isset($existingCriteria[$kriteria_id])) {
            $existingCriteria[$kriteria_id] = [
                'name' => $kriteria_name,
                'input_type' => $input_type,
                'values' => []
            ];
        }
        
        if ($input_type === 'manual') {
            $existingCriteria[$kriteria_id]['values'][$subkriteria_id] = [
                'name' => $subkriteria_name,
                'value' => $value
            ];
        } else {
            $existingCriteria[$kriteria_id]['values'][$subkriteria_id] = [
                'name' => $subkriteria_name
            ];
        }
    }
    
    // Extract form data criteria
    $formCriteria = [];
    
    // Process checkbox values
    if (isset($formData['nilai']) && is_array($formData['nilai'])) {
        foreach ($formData['nilai'] as $kriteria_id => $subkriteria_ids) {
            if (!isset($formCriteria[$kriteria_id])) {
                // Get kriteria name (would need to be fetched from DB in real implementation)
                $kriteria_name = Kriteria::find($kriteria_id)->nama_kriteria ?? 'Unknown';
                
                $formCriteria[$kriteria_id] = [
                    'name' => $kriteria_name,
                    'input_type' => 'checkbox',
                    'values' => []
                ];
            }
            
            foreach ($subkriteria_ids as $subkriteria_id) {
                // Get subkriteria name
                $subkriteria_name = Subkriteria::find($subkriteria_id)->nama_subkriteria ?? 'Unknown';
                
                $formCriteria[$kriteria_id]['values'][$subkriteria_id] = [
                    'name' => $subkriteria_name
                ];
            }
        }
    }
    
    // Process manual input values
    if (isset($formData['nilai_manual']) && is_array($formData['nilai_manual'])) {
        foreach ($formData['nilai_manual'] as $kriteria_id => $value) {
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }
            
            // Find the corresponding subkriteria for this value
            $subkriteria = $this->findSubkriteriaForValue($kriteria_id, $value);
            if (!$subkriteria) {
                continue;
            }
            
            if (!isset($formCriteria[$kriteria_id])) {
                // Get kriteria name
                $kriteria_name = Kriteria::find($kriteria_id)->nama_kriteria ?? 'Unknown';
                
                $formCriteria[$kriteria_id] = [
                    'name' => $kriteria_name,
                    'input_type' => 'manual',
                    'values' => []
                ];
            }
            
            $formCriteria[$kriteria_id]['values'][$subkriteria->subkriteria_id] = [
                'name' => $subkriteria->nama_subkriteria,
                'value' => $value
            ];
        }
    }
    
    // Combine all criteria for comparison
    $allKriteriaIds = array_unique(array_merge(array_keys($existingCriteria), array_keys($formCriteria)));
    
    foreach ($allKriteriaIds as $kriteria_id) {
        $comparison[$kriteria_id] = [
            'name' => $existingCriteria[$kriteria_id]['name'] ?? $formCriteria[$kriteria_id]['name'] ?? 'Unknown',
            'existing' => $existingCriteria[$kriteria_id]['values'] ?? null,
            'new' => $formCriteria[$kriteria_id]['values'] ?? null,
            'input_type' => $existingCriteria[$kriteria_id]['input_type'] ?? $formCriteria[$kriteria_id]['input_type'] ?? 'checkbox',
            'status' => $this->determineCriteriaStatus(
                $existingCriteria[$kriteria_id]['values'] ?? [], 
                $formCriteria[$kriteria_id]['values'] ?? [],
                $existingCriteria[$kriteria_id]['input_type'] ?? $formCriteria[$kriteria_id]['input_type'] ?? 'checkbox'
            )
        ];
    }
    
    return $comparison;
}

/**
 * Determine the status of a criteria comparison
 * 
 * @param array $existingValues
 * @param array $newValues
 * @param string $inputType
 * @return string 'identical', 'subset', 'superset', 'partial-overlap', or 'different'
 */
private function determineCriteriaStatus($existingValues, $newValues, $inputType)
{
    if (empty($existingValues) && empty($newValues)) {
        return 'identical'; // Both empty
    }
    
    if (empty($existingValues)) {
        return 'new-only'; // Only in new frame
    }
    
    if (empty($newValues)) {
        return 'existing-only'; // Only in existing frame
    }
    
    // For manual input type - direct value comparison
    if ($inputType === 'manual') {
        $existingKeys = array_keys($existingValues);
        $newKeys = array_keys($newValues);
        
        // Usually there's only one value for manual inputs
        if (count($existingKeys) === 1 && count($newKeys) === 1) {
            $existingKey = reset($existingKeys);
            $newKey = reset($newKeys);
            
            if ($existingKey === $newKey && 
                $existingValues[$existingKey]['value'] === $newValues[$newKey]['value']) {
                return 'identical';
            }
        }
        
        return 'different';
    }
    
    // For checkbox type - set comparison
    $existingKeys = array_keys($existingValues);
    $newKeys = array_keys($newValues);
    
    // Check for identical sets
    if (count($existingKeys) === count($newKeys) &&
        empty(array_diff($existingKeys, $newKeys)) &&
        empty(array_diff($newKeys, $existingKeys))) {
        return 'identical';
    }
    
    // Check for subset (new is subset of existing)
    if (empty(array_diff($newKeys, $existingKeys)) && 
        !empty(array_diff($existingKeys, $newKeys))) {
        return 'subset';
    }
    
    // Check for superset (new contains all of existing plus more)
    if (empty(array_diff($existingKeys, $newKeys)) && 
        !empty(array_diff($newKeys, $existingKeys))) {
        return 'superset';
    }
    
    // Check for partial overlap
    if (!empty(array_intersect($existingKeys, $newKeys))) {
        return 'partial-overlap';
    }
    
    // Completely different sets
    return 'different';
}
    
    public function create()
    {
        $kriterias = Kriteria::with('subkriterias')->get();
        return view('frame.create', compact('kriterias'));
    }

    // In FrameController.php
    public function store(Request $request)
{
    $request->validate([
        'frame_merek' => 'required|string|max:255',
        'frame_foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        'frame_lokasi' => 'required|string|max:255',
        'nilai.*' => 'nullable|array',
        'nilai.*.*' => 'nullable|exists:subkriterias,subkriteria_id',
        'nilai_manual.*' => 'nullable',
        'input_type.*' => 'nullable|in:checkbox,manual'
    ]);
    
    // Check for similar frames based on both image and data
    $similarityResults = $this->checkForSimilarFrames($request);
    
    // If similar frames found, redirect to confirmation page
    if ($similarityResults['similarFrame']) {
        // Store the uploaded image temporarily
        $tempImageName = 'temp_' . time() . '.' . $request->frame_foto->extension();
        $request->frame_foto->move(public_path('storage/temp'), $tempImageName);
        
        // Store all the form data (exclude the file)
        $formData = $request->except(['frame_foto', '_token']);
        
        // Store in session
        session([
            'frame_form_data' => $formData,
            'temp_image' => 'temp/' . $tempImageName,
            'similarity_details' => $similarityResults['similarityDetails'],
            'all_similar_frames' => $similarityResults['allSimilarFrames'] ?? []
        ]);
        
        return redirect()->route('frame.confirm-duplicate', ['similar_frame_id' => $similarityResults['similarFrame']->frame_id]);
    }

    // No similar frame found, proceed with saving
    return $this->saveNewFrame($request);
}

private function saveNewFrame(Request $request, $tempImagePath = null)
{
    // If we have a temp image path from the confirmation flow
    if ($tempImagePath) {
        $imageName = time() . '.jpg';
        
        // Move from temp to permanent location
        Storage::disk('public')->move($tempImagePath, 'frames/' . $imageName);
        $imagePath = 'frames/' . $imageName;
    } else {
        // Regular flow - upload new image
        $imageName = time().'.'.$request->frame_foto->extension();  
        $request->frame_foto->move(public_path('storage/frames'), $imageName);
        $imagePath = 'frames/' . $imageName;
    }

    // Make sure we have all the required data
    $frame_merek = $request->input('frame_merek');
    $frame_lokasi = $request->input('frame_lokasi');
    
    // Verify we have all required data before proceeding
    if (!$frame_merek || !$frame_lokasi) {
        // Log the error and throw an exception
        Log::error('Missing required frame data', [
            'frame_merek' => $frame_merek,
            'frame_lokasi' => $frame_lokasi
        ]);
        
        // Clean up the image we just saved
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
        
        return redirect()->route('frame.create')
            ->with('error', 'Gagal menyimpan frame karena data tidak lengkap. Silakan coba lagi.')
            ->withInput();
    }

    $frame = Frame::create([
        'frame_merek' => $frame_merek,
        'frame_foto' => $imagePath,
        'frame_lokasi' => $frame_lokasi
    ]);

    // Debug log
    Log::info('Created new frame', [
        'frame_id' => $frame->frame_id,
        'input_types' => $request->input('input_type', []),
        'nilai' => $request->input('nilai', []),
        'nilai_manual' => $request->input('nilai_manual', [])
    ]);

    // Process regular checkbox subkriteria
    $nilai = $request->input('nilai', []);
    $input_types = $request->input('input_type', []);
    
    if (is_array($nilai)) {
        foreach ($nilai as $kriteria_id => $subkriteria_ids) {
            // Check if input type is checkbox or not specified
            if (!isset($input_types[$kriteria_id]) || $input_types[$kriteria_id] == 'checkbox') {
                foreach ($subkriteria_ids as $subkriteria_id) {
                    FrameSubkriteria::create([
                        'frame_id' => $frame->frame_id,
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria_id,
                    ]);
                    
                    Log::info('Created checkbox subkriteria', [
                        'frame_id' => $frame->frame_id,
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria_id
                    ]);
                }
            }
        }
    }

    // Process manual input values for range-type subkriteria
    $nilai_manual = $request->input('nilai_manual', []);
    if (is_array($nilai_manual)) {
        foreach ($nilai_manual as $kriteria_id => $value) {
            // Only process if input type is manual and value is not empty
            if (isset($input_types[$kriteria_id]) && $input_types[$kriteria_id] == 'manual' && !empty($value)) {
                // Konversi nilai string ke decimal untuk memastikan format yang benar
                $decimalValue = (float) str_replace(',', '.', $value);
                
                $subkriteria = $this->findSubkriteriaForValue($kriteria_id, $decimalValue);
                
                if ($subkriteria) {
                    FrameSubkriteria::create([
                        'frame_id' => $frame->frame_id,
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'manual_value' => $decimalValue // Simpan nilai decimal yang sudah dikonversi
                    ]);
                    
                    Log::info('Created manual subkriteria', [
                        'frame_id' => $frame->frame_id,
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'manual_value' => $decimalValue
                    ]);
                } else {
                    Log::warning('Could not find matching subkriteria for manual value', [
                        'kriteria_id' => $kriteria_id,
                        'value' => $decimalValue
                    ]);
                }
            }
        }
    }

    $frameData = $frame->toArray();
    $frameData['subkriterias'] = $this->getFrameSubkriteriaData($frame->frame_id);
    
    // Add image backup for logging
    if ($frame->frame_foto && Storage::disk('public')->exists($frame->frame_foto)) {
        $frameData['log_image_backup'] = $this->backupImageForLogs($frame->frame_foto);
    }
    
    // Log activity with complete data
    ActivityLogService::log(
        'create',
        'frame',
        $frame->frame_id,
        null,
        $frameData,
        'Membuat frame baru: ' . $frame->frame_merek
    );

    return redirect()->route('frame.index')->with('success', 'Frame berhasil ditambahkan');
}

    // New method to show confirmation page
    public function confirmDuplicate(Request $request, $similar_frame_id) 
{
    $similarFrame = Frame::with(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria'])
                       ->findOrFail($similar_frame_id);
    
    // Get temp image from session
    $tempImagePath = session('temp_image');
    
    // Get similarity details
    $similarityDetails = session('similarity_details', []);
    
    // Get all similar frames langsung dari session
    $allSimilarFrames = session('all_similar_frames', []);
    
    // Jika tidak ada di session (compatibility), coba dapatkan dari similarity details
    if (empty($allSimilarFrames)) {
        $otherSimilarFrameIds = $this->getAllSimilarFrameIds($similarityDetails);
        
        // Get the frame_merek from the session data
        $formData = session('frame_form_data', []);
        $brand = $formData['frame_merek'] ?? null;
        
        if (!empty($otherSimilarFrameIds)) {
            // Fetch frames with the same ID and filter by brand (case-insensitive)
            $query = Frame::whereIn('frame_id', $otherSimilarFrameIds);
                        
            // Add brand filter if brand is provided (case-insensitive)   
            if ($brand) {
                $query->whereRaw('LOWER(frame_merek) = ?', [strtolower($brand)]);
            }
            
            $allSimilarFrames = $query->get()->all();
        }
    }
    
    // Pastikan frame yang sedang ditampilkan selalu muncul pertama
    usort($allSimilarFrames, function($a, $b) use ($similar_frame_id) {
        if ($a->frame_id == $similar_frame_id) return -1;
        if ($b->frame_id == $similar_frame_id) return 1;
        return 0;
    });
    
    // Get the frame_merek from the session data
    $formData = session('frame_form_data', []);
    
    // Load the criteria data for comparison display in the view
    $criteriaComparison = $this->prepareComparisonData($similarFrame, $formData);
    
    return view('frame.confirm-duplicate', compact(
        'similarFrame', 
        'tempImagePath', 
        'similarityDetails', 
        'allSimilarFrames',
        'criteriaComparison'
    ));
}

    // Update the processDuplicateConfirmation method
    public function processDuplicateConfirmation(Request $request)
{
    if ($request->input('action') === 'continue') {
        // User wants to continue with saving despite similarity
        $tempImagePath = session('temp_image');
        $formData = session('frame_form_data', []);
        
        if (empty($formData)) {
            return redirect()->route('frame.create')
                ->with('error', 'Data frame tidak ditemukan. Silakan coba lagi.');
        }
        
        // Create a new request with the stored data
        $reconstructedRequest = new Request($formData);
        
        // Process the save with the temp image path to handle the file properly
        return $this->saveNewFrame($reconstructedRequest, $tempImagePath);
    } else {
        // User chose to cancel - redirect back to create page WITHOUT old input
        
        // Hapus file gambar temporary jika ada
        $tempImagePath = session('temp_image');
        if ($tempImagePath && Storage::disk('public')->exists($tempImagePath)) {
            Storage::disk('public')->delete($tempImagePath);
        }
        
        // Clear session completely
        session()->forget(['temp_image', 'frame_form_data', 'similarity_details', 'all_similar_frames']);
        
        // Redirect ke halaman create tanpa membawa data input lama
        return redirect()->route('frame.create')
            ->with('info', 'Penambahan frame dibatalkan karena kesamaan dengan frame yang sudah ada.');
    }
}

    public function edit(Frame $frame)
{
    $kriterias = Kriteria::with('subkriterias')->get();
    $frame->load('frameSubkriterias');
    return view('frame.edit', compact('frame', 'kriterias'));
}


public function update(Request $request, Frame $frame)
{
    $request->validate([
        'frame_merek' => 'required|string|max:255',
        'frame_foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'frame_lokasi' => 'required|string|max:255',
        'nilai.*' => 'nullable|array',
        'nilai.*.*' => 'nullable|exists:subkriterias,subkriteria_id',
        'nilai_manual.*' => 'nullable',
        'input_type.*' => 'nullable|in:checkbox,manual'
    ]);

    // Store original frame data for comparison
    $originalFrame = Frame::with('frameSubkriterias')->find($frame->frame_id);
    $originalData = $originalFrame->toArray();
    $originalSubkriterias = $this->getFrameSubkriteriaData($frame->frame_id);
    $oldFrameMerek = $frame->frame_merek;

    // Check if there's a confirmed similarity from session
    $confirmedSimilarity = session('confirmed_similarity', false);

    // Check for similarities only if not already confirmed
    if (!$confirmedSimilarity) {
        // Create a complete request object for similarity checking
        $similarityRequest = clone $request;
        
        // If no new image uploaded, we need to pass the existing image path
        if (!$request->hasFile('frame_foto') && $frame->frame_foto) {
            $similarityRequest->merge(['existing_frame_foto' => $frame->frame_foto]);
        }
        
        // Check for similarities with complete data
        $similarityResults = $this->checkForSimilarFrames($similarityRequest, $frame->frame_id);
        
        // If similar frame found (that's not the current frame)
        if ($similarityResults['similarFrame'] && $similarityResults['similarFrame']->frame_id != $frame->frame_id) {
            // Store the temporary image if one was uploaded
            if ($request->hasFile('frame_foto')) {
                $tempImageName = 'temp_edit_' . time() . '.' . $request->frame_foto->extension();
                $request->frame_foto->move(public_path('storage/temp'), $tempImageName);
                session(['temp_edit_image' => 'temp/' . $tempImageName]);
            }
            
            // Store form data in session
            $formData = $request->except(['frame_foto', '_token', '_method']);
            session(['frame_edit_data' => $formData]);
            
            // Store similarity results in session for display in the view
            session(['similarity_results' => $similarityResults]);
            
            // Set confirmed similarity flag for the next request
            session(['confirmed_similarity' => true]);
            
            // Log that we're redirecting to confirmation page
            Log::info('Redirecting to confirmation page due to similarity', [
                'frame_id' => $frame->frame_id,
                'similar_frame_id' => $similarityResults['similarFrame']->frame_id,
                'similarity_details' => $similarityResults['similarityDetails'],
                'all_similar_frames_count' => count($similarityResults['allSimilarFrames'] ?? [])
            ]);
            
            // Redirect to confirmation page
            return redirect()->route('frame.confirm-update-duplicate', $frame->frame_id);
        }
    } else {
        // Clear the confirmation flag for future requests
        session(['confirmed_similarity' => false]);
    }

    // Flag to track if any data has changed
    $dataChanged = false;
    
    // Check if basic frame data has changed
    if ($frame->frame_merek !== $request->frame_merek || 
        $frame->frame_lokasi !== $request->frame_lokasi) {
        $dataChanged = true;
    }
    
    // Variable to track new image path if uploaded
    $newImagePath = null;
    $newImageName = null;

    // Handle image upload if a new image is provided
    if ($request->hasFile('frame_foto')) {
        $dataChanged = true;
        
        // Check if we have a temp image from the confirmation flow
        $tempEditImage = session('temp_edit_image');
        if ($tempEditImage && Storage::disk('public')->exists($tempEditImage)) {
            // Use temp image from session
            $newImageName = time() . '.jpg';
            $newImagePath = 'frames/' . $newImageName;
        } else {
            // Prepare new image path
            $newImageName = time() . '.' . $request->frame_foto->extension();
            $newImagePath = 'frames/' . $newImageName;
        }
    }
    
    // Extract new subkriterias data from request
    $newSubkriterias = [];
    $nilai = $request->input('nilai', []);
    $nilai_manual = $request->input('nilai_manual', []);
    $input_types = $request->input('input_type', []);
    
    // Process checkbox type
    if (is_array($nilai)) {
        foreach ($nilai as $kriteria_id => $subkriteria_ids) {
            if (!isset($input_types[$kriteria_id]) || $input_types[$kriteria_id] == 'checkbox') {
                foreach ($subkriteria_ids as $subkriteria_id) {
                    $newSubkriterias[] = [
                        'kriteria_id' => (int)$kriteria_id,
                        'subkriteria_id' => (int)$subkriteria_id,
                        'manual_value' => null
                    ];
                }
            }
        }
    }
    
    // Process manual input values
    if (is_array($nilai_manual)) {
        foreach ($nilai_manual as $kriteria_id => $value) {
            if (isset($input_types[$kriteria_id]) && $input_types[$kriteria_id] == 'manual' && !empty($value)) {
                $decimalValue = (float) str_replace(',', '.', $value);
                $subkriteria = $this->findSubkriteriaForValue($kriteria_id, $decimalValue);
                
                if ($subkriteria) {
                    $newSubkriterias[] = [
                        'kriteria_id' => (int)$kriteria_id,
                        'subkriteria_id' => (int)$subkriteria->subkriteria_id,
                        'manual_value' => $decimalValue
                    ];
                }
            }
        }
    }
    
    // Convert original subkriterias for comparison
    $normalizedOriginalSubkriterias = [];
    foreach ($originalSubkriterias as $sub) {
        $normalizedOriginalSubkriterias[] = [
            'kriteria_id' => (int)$sub['kriteria_id'],
            'subkriteria_id' => (int)$sub['subkriteria_id'],
            'manual_value' => $sub['manual_value'] !== null ? (float)$sub['manual_value'] : null
        ];
    }
    
    // Compare subkriterias to detect changes
    if (count($normalizedOriginalSubkriterias) != count($newSubkriterias)) {
        $dataChanged = true;
        Log::debug('Subkriteria count changed', [
            'original' => count($normalizedOriginalSubkriterias),
            'new' => count($newSubkriterias)
        ]);
    } else {
        // Sort arrays for proper comparison
        $sortFunc = function($a, $b) {
            if ($a['kriteria_id'] == $b['kriteria_id']) {
                return $a['subkriteria_id'] <=> $b['subkriteria_id'];
            }
            return $a['kriteria_id'] <=> $b['kriteria_id'];
        };
        
        usort($normalizedOriginalSubkriterias, $sortFunc);
        usort($newSubkriterias, $sortFunc);
        
        // Deep comparison for subkriterias
        for ($i = 0; $i < count($normalizedOriginalSubkriterias); $i++) {
            $orig = $normalizedOriginalSubkriterias[$i];
            $new = $newSubkriterias[$i];
            
            if ($orig['kriteria_id'] != $new['kriteria_id'] ||
                $orig['subkriteria_id'] != $new['subkriteria_id'] ||
                $orig['manual_value'] != $new['manual_value']) {
                
                Log::debug('Subkriteria difference detected', [
                    'index' => $i,
                    'original' => $orig,
                    'new' => $new
                ]);
                
                $dataChanged = true;
                break;
            }
        }
    }
    
    // If no data has changed, clean up and return early
    if (!$dataChanged) {
        Log::info('No frame data changed, skipping update', ['frame_id' => $frame->frame_id]);
        
        // Clean up any temporary images that might have been stored
        $tempEditImage = session('temp_edit_image');
        if ($tempEditImage && Storage::disk('public')->exists($tempEditImage)) {
            Storage::disk('public')->delete($tempEditImage);
            Log::info('Deleted unused temp image', ['path' => $tempEditImage]);
        }
        
        // Clear any temp session data
        session()->forget(['temp_edit_image', 'frame_edit_data', 'similarity_results', 'confirmed_similarity']);
        
        return redirect()->route('frame.index')->with('info', 'Tidak ada data yang diperbarui');
    }
    
    // At this point, we know changes are needed
    // Create a backup of old data for logging
    $oldData = $originalFrame->toArray();
    if ($frame->frame_foto && Storage::disk('public')->exists($frame->frame_foto)) {
        $oldData['log_image_backup'] = $this->backupImageForLogs($frame->frame_foto);
    }
    $oldData['subkriterias'] = $originalSubkriterias;
    
    // Begin actual updates
    Log::info('Updating frame data', ['frame_id' => $frame->frame_id]);
    
    // Update basic frame data
    $frame->frame_merek = $request->frame_merek;
    $frame->frame_lokasi = $request->frame_lokasi;

    // Update image if needed
    if ($newImagePath) {
        // Delete old image if it exists
        if ($frame->frame_foto && Storage::disk('public')->exists($frame->frame_foto)) {
            Storage::disk('public')->delete($frame->frame_foto);
            Log::info('Deleted old frame image', ['path' => $frame->frame_foto]);
        }
        
        if (session('temp_edit_image') && Storage::disk('public')->exists(session('temp_edit_image'))) {
            // Move temp image
            Storage::disk('public')->move(session('temp_edit_image'), $newImagePath);
            Log::info('Moved temp image to permanent location', [
                'from' => session('temp_edit_image'),
                'to' => $newImagePath
            ]);
        } else {
            // Upload new image
            $request->frame_foto->move(public_path('storage/frames'), $newImageName);
            Log::info('Uploaded new image', ['path' => 'frames/' . $newImageName]);
        }
        
        $frame->frame_foto = $newImagePath;
    }

    // Save basic frame data changes
    $frame->save();

    // Update subkriterias
    
    // First, delete all existing frameSubkriterias for this frame
    FrameSubkriteria::where('frame_id', $frame->frame_id)->delete();
    Log::info('Deleted existing subkriterias', ['frame_id' => $frame->frame_id]);

    // Process regular checkbox subkriteria
    if (is_array($nilai)) {
        foreach ($nilai as $kriteria_id => $subkriteria_ids) {
            // Check if input type is checkbox or not specified
            if (!isset($input_types[$kriteria_id]) || $input_types[$kriteria_id] == 'checkbox') {
                foreach ($subkriteria_ids as $subkriteria_id) {
                    FrameSubkriteria::create([
                        'frame_id' => $frame->frame_id,
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria_id,
                    ]);
                    
                    Log::debug('Created checkbox subkriteria', [
                        'frame_id' => $frame->frame_id,
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria_id
                    ]);
                }
            }
        }
    }

    // Process manual input values for range-type subkriteria
    if (is_array($nilai_manual)) {
        foreach ($nilai_manual as $kriteria_id => $value) {
            // Only process if input type is manual and value is not empty
            if (isset($input_types[$kriteria_id]) && $input_types[$kriteria_id] == 'manual' && !empty($value)) {
                // Konversi nilai string ke decimal untuk memastikan format yang benar
                $decimalValue = (float) str_replace(',', '.', $value);
                
                $subkriteria = $this->findSubkriteriaForValue($kriteria_id, $decimalValue);
                
                if ($subkriteria) {
                    FrameSubkriteria::create([
                        'frame_id' => $frame->frame_id,
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'manual_value' => $decimalValue // Simpan nilai decimal yang sudah dikonversi
                    ]);
                    
                    Log::debug('Created manual subkriteria', [
                        'frame_id' => $frame->frame_id,
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'manual_value' => $decimalValue
                    ]);
                } else {
                    Log::warning('Could not find matching subkriteria for manual value', [
                        'kriteria_id' => $kriteria_id,
                        'value' => $decimalValue
                    ]);
                }
            }
        }
    }

    // Prepare new data for logging after all changes
    $updatedFrame = Frame::find($frame->frame_id);
    $newData = $updatedFrame->toArray();
    $newData['subkriterias'] = $this->getFrameSubkriteriaData($frame->frame_id);

    // Log the activity (only happens if we got to this point, which means data changed)
    ActivityLogService::log(
        'update',
        'frame',
        $frame->frame_id,
        $oldData,
        $newData,
        'Memperbarui frame: ' . $oldFrameMerek 
    );

    // Clear any temp session data
    session()->forget(['temp_edit_image', 'frame_edit_data', 'similarity_results', 'confirmed_similarity']);

    return redirect()->route('frame.index')->with('success', 'Frame berhasil diperbarui');
}

/**
 * Show confirmation for duplicate frames during update
 */
public function confirmUpdateDuplicate($frame_id)
{
    $frame = Frame::findOrFail($frame_id);
    $similarityResults = session('similarity_results', []);
    
    // Get the primary similar frame
    $similarFrameId = $similarityResults['similarFrame']->frame_id ?? null;
    $similarFrame = $similarFrameId ? Frame::with(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria'])->find($similarFrameId) : null;
    
    // Get all similar frames
    $allSimilarFrames = $similarityResults['allSimilarFrames'] ?? [];
    
    // Jika tidak ada di similarityResults, coba dapatkan dari similarity details
    if (empty($allSimilarFrames) && isset($similarityResults['similarityDetails'])) {
        $otherSimilarFrameIds = $this->getAllSimilarFrameIds($similarityResults['similarityDetails']);
        
        if (!empty($otherSimilarFrameIds)) {
            $allSimilarFrames = Frame::whereIn('frame_id', $otherSimilarFrameIds)
                                     ->where('frame_id', '!=', $frame_id)
                                     ->get()
                                     ->all();
        }
    }
    
    // Pastikan frame yang paling mirip muncul pertama jika ada
    if ($similarFrameId) {
        usort($allSimilarFrames, function($a, $b) use ($similarFrameId) {
            if ($a->frame_id == $similarFrameId) return -1;
            if ($b->frame_id == $similarFrameId) return 1;
            return 0;
        });
    }
    
    // Get temp image from session
    $tempImagePath = session('temp_edit_image');
    
    // Retrieve the form data from session for comparison
    $formData = session('frame_edit_data', []);
    
    // Prepare comparison data to show differences in criteria
    $criteriaComparison = null;
    if ($similarFrame) {
        $criteriaComparison = $this->prepareComparisonData($similarFrame, $formData);
    }
    
    Log::info('Form data in confirmUpdateDuplicate', [
        'frame_id' => $frame_id,
        'nilai' => $formData['nilai'] ?? [],
        'nilai_manual' => $formData['nilai_manual'] ?? [],
        'input_type' => $formData['input_type'] ?? [],
        'similar_frames_count' => count($allSimilarFrames)
    ]);
    
    return view('frame.confirm-update-duplicate', compact(
        'frame',
        'similarFrame',
        'allSimilarFrames',
        'tempImagePath',
        'similarityResults',
        'criteriaComparison'
    ));
}
/**
 * Process the confirmation of duplicate frame during update
 */
/**
 * Process the confirmation of duplicate frame during update
 */
public function processUpdateDuplicate(Request $request, $frame_id)
{
    $frame = Frame::findOrFail($frame_id);

    if (!($frame instanceof Frame)) {
        return redirect()->route('frame.index')
            ->with('error', 'Error retrieving frame data. Please try again.');
    }
    
    if ($request->input('action') === 'continue') {
        // User wants to continue with updating despite similarity
        $formData = session('frame_edit_data', []);
        $tempImagePath = session('temp_edit_image');
        
        if (empty($formData)) {
            return redirect()->route('frame.edit', $frame_id)
                ->with('error', 'Data frame tidak ditemukan. Silakan coba lagi.');
        }
        
        // Create a new request with the stored data
        $formData['_method'] = 'PUT'; // Force method to be PUT
        
        // Explicitly preserve and process the criteria values
        $nilai = $formData['nilai'] ?? [];
        $nilai_manual = $formData['nilai_manual'] ?? [];
        $input_type = $formData['input_type'] ?? [];
        
        // Log the criteria values to verify they're being passed
        Log::info('Reconstructing request with criteria values', [
            'frame_id' => $frame_id,
            'nilai' => $nilai,
            'nilai_manual' => $nilai_manual,
            'input_type' => $input_type,
            'temp_image_path' => $tempImagePath
        ]);
        
        // Create a new request with all data
        $reconstructedRequest = new Request($formData);
        $reconstructedRequest->setMethod('PUT');
        
        // Update frame basic data
        $frame->frame_merek = $formData['frame_merek'] ?? $frame->frame_merek;
        $frame->frame_lokasi = $formData['frame_lokasi'] ?? $frame->frame_lokasi;

        // Handle the temp image if one exists
        if ($tempImagePath && Storage::disk('public')->exists($tempImagePath)) {
            // Delete old image if it exists
            if ($frame->frame_foto && Storage::disk('public')->exists($frame->frame_foto)) {
                Storage::disk('public')->delete($frame->frame_foto);
            }
            
            // Move temp image to permanent location
            $imageName = time() . '.jpg';
            Storage::disk('public')->move($tempImagePath, 'frames/' . $imageName);
            $frame->frame_foto = 'frames/' . $imageName;
        }

        $frame->save();

        // Delete all existing frameSubkriterias for this frame
        FrameSubkriteria::where('frame_id', $frame->frame_id)->delete();

        // Process regular checkbox subkriteria
        if (is_array($nilai)) {
            foreach ($nilai as $kriteria_id => $subkriteria_ids) {
                // Check if input type is checkbox or not specified
                if (!isset($input_type[$kriteria_id]) || $input_type[$kriteria_id] == 'checkbox') {
                    foreach ($subkriteria_ids as $subkriteria_id) {
                        FrameSubkriteria::create([
                            'frame_id' => $frame->frame_id,
                            'kriteria_id' => $kriteria_id,
                            'subkriteria_id' => $subkriteria_id,
                        ]);
                        
                        Log::info('Created checkbox subkriteria', [
                            'frame_id' => $frame->frame_id,
                            'kriteria_id' => $kriteria_id,
                            'subkriteria_id' => $subkriteria_id
                        ]);
                    }
                }
            }
        }

        // Process manual input values for range-type subkriteria
        if (is_array($nilai_manual)) {
            foreach ($nilai_manual as $kriteria_id => $value) {
                if (isset($input_type[$kriteria_id]) && $input_type[$kriteria_id] == 'manual' && !empty($value)) {
                    $subkriteria = $this->findSubkriteriaForValue($kriteria_id, $value);
                    
                    if ($subkriteria) {
                        FrameSubkriteria::create([
                            'frame_id' => $frame->frame_id,
                            'kriteria_id' => $kriteria_id,
                            'subkriteria_id' => $subkriteria->subkriteria_id,
                            'manual_value' => $value // Store the actual value for reference
                        ]);
                        
                        Log::info('Created manual subkriteria', [
                            'frame_id' => $frame->frame_id,
                            'kriteria_id' => $kriteria_id,
                            'subkriteria_id' => $subkriteria->subkriteria_id,
                            'manual_value' => $value
                        ]);
                    }
                }
            }
        }
        
        // Log activity
        ActivityLogService::log(
            'update',
            'frame',
            $frame->frame_id,
            [], // We don't have old data here, could retrieve it if needed
            $frame->toArray(),
            'Memperbarui frame: ' . $frame->frame_merek
        );

        // Clear any temp session data
        session()->forget(['temp_edit_image', 'frame_edit_data', 'similarity_results', 'confirmed_similarity']);

        return redirect()->route('frame.index')->with('success', 'Frame berhasil diperbarui');
    } else {
        // User chose to cancel - redirect back to edit page with old input
        $formData = session('frame_edit_data', []);
        
        // Clear session data
        session()->forget(['temp_edit_image', 'frame_edit_data', 'similarity_results', 'confirmed_similarity']);
        
        return redirect()->route('frame.edit', $frame_id)
            ->withInput($formData)
            ->with('info', 'Pembaruan frame dibatalkan karena kesamaan dengan frame yang sudah ada.');
    }
}

private function getFrameSubkriteriaData($frameId)
{
    $subkriterias = FrameSubkriteria::where('frame_id', $frameId)
        ->with(['kriteria', 'subkriteria'])
        ->get()
        ->map(function($item) {
            $kriteriaName = $item->kriteria ? $item->kriteria->kriteria_nama : 'Unknown';
            $subkriteriaName = $item->subkriteria ? $item->subkriteria->subkriteria_nama : 'Unknown';
            
            return [
                'kriteria_id' => $item->kriteria_id,
                'kriteria_nama' => $kriteriaName,
                'subkriteria_id' => $item->subkriteria_id,
                'subkriteria_nama' => $subkriteriaName,
                'manual_value' => $item->manual_value
            ];
        })
        ->toArray();
        
    return $subkriterias;
}
    public function destroy(Frame $frame)
{
    // Ambil data frame sebelum dihapus, termasuk informasi subkriteria
    $frameData = $frame->toArray();
    // Backup image for logging before deletion
    if ($frame->frame_foto && Storage::disk('public')->exists($frame->frame_foto)) {
        $frameData['log_image_backup'] = $this->backupImageForLogs($frame->frame_foto);
    }
    $frameData['subkriterias'] = $this->getFrameSubkriteriaData($frame->frame_id);
    
    $frameId = $frame->frame_id;
    $frameMerek = $frame->frame_merek;
    $frameImagePath = $frame->frame_foto; // Simpan path foto untuk logging
    
    // Log activity sebelum menghapus file fisik dan data
    ActivityLogService::log(
        'delete',
        'frame',
        $frameId,
        $frameData,
        null,
        'Menghapus frame: ' . $frameMerek
    );
    
    // Hapus file fisik jika ada
    if($frameImagePath) {
        // Pastikan path lengkap untuk penghapusan file
        // Jika path tidak dimulai dengan storage/, tambahkan
        if (strpos($frameImagePath, 'storage/') !== 0) {
            $fullPath = public_path('storage/' . $frameImagePath);
        } else {
            $fullPath = public_path($frameImagePath);
        }
        
        // Log info tentang penghapusan file
        Log::info('Mencoba menghapus file frame', [
            'frame_id' => $frameId,
            'frame_foto' => $frameImagePath,
            'full_path' => $fullPath,
            'file_exists' => file_exists($fullPath)
        ]);
        
        if(file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    
    // Hapus data subkriteria terkait
    $frame->frameSubkriterias()->delete();
    
    // Hapus data frame
    $frame->delete();

    return redirect()->route('frame.index')->with('success', 'Frame berhasil dihapus');
}

    public function resetFrameKriteria(Request $request)
{
    // Check if there are any frame subkriteria records
    $hasSubkriteria = FrameSubkriteria::count() > 0;
    
    if (!$hasSubkriteria) {
        return redirect()->route('frame.index')->with('warning', 'Tidak ada kriteria yang dapat direset karena semua kriteria sudah kosong.');
    }
    
    // Validate request
    $request->validate([
        'reset_all' => 'nullable|boolean',
        'kriteria_ids' => 'required_without:reset_all|array',
        'kriteria_ids.*' => 'exists:kriterias,kriteria_id',
    ]);

    try {
        // Check if reset all is selected
        if ($request->has('reset_all') && $request->reset_all == 1) {
            // Reset all frame subkriterias
            FrameSubkriteria::truncate();
            
            // Log activity with system ID 0 as reference_id to avoid null constraint
            ActivityLogService::log(
                'reset',
                'frame',
                0, // Use 0 as a system reference ID instead of null
                null,
                null,
                'Reset semua kriteria frame'
            );
            
            return redirect()->route('frame.index')->with('success', 'Semua kriteria untuk semua frame berhasil direset');
        }
        
        // Otherwise, reset only the selected kriteria
        if ($request->has('kriteria_ids') && !empty($request->kriteria_ids)) {
            // Get selected kriteria names for log
            $selectedKriterias = Kriteria::whereIn('kriteria_id', $request->kriteria_ids)->pluck('kriteria_nama')->toArray();
            
            // Delete frame subkriterias matching the selected kriteria IDs
            FrameSubkriteria::whereIn('kriteria_id', $request->kriteria_ids)->delete();
            
            // Log activity with system ID 0 as reference_id to avoid null constraint
            ActivityLogService::log(
                'reset',
                'frame',
                0, // Use 0 as a system reference ID instead of null
                null,
                null,
                'Reset kriteria frame: ' . implode(', ', $selectedKriterias)
            );
            
            return redirect()->route('frame.index')->with('success', 'Kriteria yang dipilih untuk semua frame berhasil direset');
        }
        
        // If no option selected
        return redirect()->route('frame.index')->with('error', 'Tidak ada kriteria yang dipilih untuk direset');
    } catch (\Exception $e) {
        // Log the error
        Log::error('Error resetting frame criteria: ' . $e->getMessage());
        
        // Return with error message
        return redirect()->route('frame.index')->with('error', 'Terjadi kesalahan saat mereset kriteria: ' . $e->getMessage());
    }
}

}