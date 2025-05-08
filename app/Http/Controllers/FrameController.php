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

    /**
 * Find frames with similar data (merek, lokasi, and kriteria must all match exactly)
 * But merek and lokasi comparison is case-insensitive
 *
 * @param Request $request
 * @param int|null $excludeFrameId Frame ID to exclude from similarity check (for updates)
 * @return array
 */
private function findFramesWithSimilarData(Request $request, $excludeFrameId = null)
{
    // Step 1: Find frames with similar merek and lokasi (case-insensitive)
    $query = Frame::whereRaw('LOWER(frame_merek) = ?', [strtolower($request->frame_merek)])
                  ->whereRaw('LOWER(frame_lokasi) = ?', [strtolower($request->frame_lokasi)]);
    
    // Exclude the current frame if we're updating
    if ($excludeFrameId) {
        $query->where('frame_id', '!=', $excludeFrameId);
    }
    
    $similarMerekAndLokasi = $query->get();
    
    if ($similarMerekAndLokasi->isEmpty()) {
        return []; // No matches on basic data
    }
    
    // Step 2: Check for similar criteria combinations
    $requestedCriteria = [];
    $input_types = $request->input('input_type', []);
    
    // Process checkbox criteria values
    if ($request->has('nilai')) {
        foreach ($request->nilai as $kriteria_id => $subkriteria_ids) {
            // Only process if input type is checkbox or not specified
            if (!isset($input_types[$kriteria_id]) || $input_types[$kriteria_id] == 'checkbox') {
                foreach ($subkriteria_ids as $subkriteria_id) {
                    $requestedCriteria[] = [
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria_id,
                        'input_type' => 'checkbox',
                        'value' => null
                    ];
                }
            }
        }
    }
    
    // Process manual input values
    if ($request->has('nilai_manual')) {
        foreach ($request->nilai_manual as $kriteria_id => $value) {
            if (isset($input_types[$kriteria_id]) && $input_types[$kriteria_id] == 'manual' && !empty($value)) {
                // Find the corresponding subkriteria for this value
                $subkriteria = $this->findSubkriteriaForValue($kriteria_id, $value);
                if ($subkriteria) {
                    $requestedCriteria[] = [
                        'kriteria_id' => $kriteria_id,
                        'subkriteria_id' => $subkriteria->subkriteria_id,
                        'input_type' => 'manual',
                        'value' => $value
                    ];
                }
            }
        }
    }
    
    // If no criteria provided, just return frames with similar basic info
    if (empty($requestedCriteria)) {
        return $similarMerekAndLokasi->pluck('frame_id')->toArray();
    }
    
    // Filter frames with exactly matching criteria
    $framesWithExactCriteria = [];
    
    foreach ($similarMerekAndLokasi as $frameItem) {
        // Load frame subkriterias if not already loaded
        if (!$frameItem->relationLoaded('frameSubkriterias')) {
            $frameItem->load('frameSubkriterias.subkriteria');
        }
        
        // Group the frame's criteria by input type
        $frameCriteria = [];
        foreach ($frameItem->frameSubkriterias as $fs) {
            $input_type = $fs->manual_value !== null ? 'manual' : 'checkbox';
            $frameCriteria[] = [
                'kriteria_id' => $fs->kriteria_id,
                'subkriteria_id' => $fs->subkriteria_id,
                'input_type' => $input_type,
                'value' => $fs->manual_value
            ];
        }
        
        // Compare requested criteria with frame criteria
        // For exact match, we need the same number of criteria with the same values
        if (count($requestedCriteria) != count($frameCriteria)) {
            continue; // Different number of criteria, not an exact match
        }
        
        // Sort both arrays to make comparison easier
        $sortedRequestedCriteria = $this->sortCriteriaForComparison($requestedCriteria);
        $sortedFrameCriteria = $this->sortCriteriaForComparison($frameCriteria);
        
        // Check for exact match
        $isExactMatch = true;
        
        // Create criteria comparison lookups for easier matching
        $requestedLookup = [];
        foreach ($sortedRequestedCriteria as $reqCriteria) {
            $key = $reqCriteria['kriteria_id'] . '_' . $reqCriteria['input_type'];
            $requestedLookup[$key][] = [
                'subkriteria_id' => $reqCriteria['subkriteria_id'],
                'value' => $reqCriteria['value']
            ];
        }
        
        $frameLookup = [];
        foreach ($sortedFrameCriteria as $frmCriteria) {
            $key = $frmCriteria['kriteria_id'] . '_' . $frmCriteria['input_type'];
            $frameLookup[$key][] = [
                'subkriteria_id' => $frmCriteria['subkriteria_id'],
                'value' => $frmCriteria['value']
            ];
        }
        
        // Check if criteria sets match
        foreach ($requestedLookup as $key => $reqItems) {
            // If this input type doesn't exist in the frame criteria, it's not a match
            if (!isset($frameLookup[$key])) {
                $isExactMatch = false;
                break;
            }
            
            // Check if the number of items for this criteria/input type matches
            if (count($reqItems) != count($frameLookup[$key])) {
                $isExactMatch = false;
                break;
            }
            
            // For each requested item, verify if it exists in the frame's criteria
            foreach ($reqItems as $reqItem) {
                $matchFound = false;
                
                foreach ($frameLookup[$key] as $frmItem) {
                    $inputType = explode('_', $key)[1];
                    
                    if ($inputType == 'checkbox') {
                        // For checkbox, just compare subkriteria_id
                        if ($reqItem['subkriteria_id'] == $frmItem['subkriteria_id']) {
                            $matchFound = true;
                            break;
                        }
                    } else if ($inputType == 'manual') {
                        // For manual inputs, compare subkriteria_id AND value must be exactly the same
                        if ($reqItem['subkriteria_id'] == $frmItem['subkriteria_id'] && 
                            $reqItem['value'] == $frmItem['value']) {
                            $matchFound = true;
                            break;
                        }
                    }
                }
                
                if (!$matchFound) {
                    $isExactMatch = false;
                    break;
                }
            }
            
            if (!$isExactMatch) {
                break;
            }
        }
        
        // Also check the reverse: all frame criteria must be in the request
        if ($isExactMatch) {
            foreach ($frameLookup as $key => $frmItems) {
                // If this input type doesn't exist in the requested criteria, it's not a match
                if (!isset($requestedLookup[$key])) {
                    $isExactMatch = false;
                    break;
                }
                
                // We already checked count above, so no need to do it again
                
                // For each frame item, verify if it exists in the requested criteria
                foreach ($frmItems as $frmItem) {
                    $matchFound = false;
                    
                    foreach ($requestedLookup[$key] as $reqItem) {
                        $inputType = explode('_', $key)[1];
                        
                        if ($inputType == 'checkbox') {
                            // For checkbox, just compare subkriteria_id
                            if ($frmItem['subkriteria_id'] == $reqItem['subkriteria_id']) {
                                $matchFound = true;
                                break;
                            }
                        } else if ($inputType == 'manual') {
                            // For manual inputs, compare subkriteria_id AND value must be exactly the same
                            if ($frmItem['subkriteria_id'] == $reqItem['subkriteria_id'] && 
                                $frmItem['value'] == $reqItem['value']) {
                                $matchFound = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$matchFound) {
                        $isExactMatch = false;
                        break;
                    }
                }
                
                if (!$isExactMatch) {
                    break;
                }
            }
        }
        
        if ($isExactMatch) {
            $framesWithExactCriteria[] = $frameItem->frame_id;
            
            // Log the similarity match for debugging
            Log::info('Frame exact similarity match', [
                'frame_id' => $frameItem->frame_id,
                'similarity' => '100% (exact match)',
                'requested_criteria' => $requestedCriteria,
                'frame_criteria' => $frameCriteria
            ]);
        }
    }
    
    return $framesWithExactCriteria;
}

/**
 * Enhanced version of checkForSimilarFrames with better handling of image comparison
 * Modified to make merek comparison case-insensitive
 * 
 * @param Request $request
 * @param int|null $excludeFrameId Frame ID to exclude from similarity check (for updates)
 * @return array
 */
private function checkForSimilarFrames(Request $request, $excludeFrameId = null)
{
    // Initialize result array
    $result = [
        'similarFrame' => null,
        'similarityDetails' => []
    ];
    
    // 1. Check for similar images if a new image is uploaded
    if ($request->hasFile('frame_foto')) {
        $similarFrame = $this->imageComparisonService->findSimilarFrame($request->file('frame_foto'));
        
        // Only consider image similarity if the brand (merek) also matches (case-insensitive) and it's not the current frame
        if ($similarFrame && 
            strtolower($similarFrame->frame_merek) === strtolower($request->frame_merek) && 
            (!$excludeFrameId || $similarFrame->frame_id != $excludeFrameId)) {
            
            $result['similarFrame'] = $similarFrame;
            $result['similarityDetails']['image'] = [
                'similar' => true,
                'message' => 'Foto frame serupa dengan frame yang sudah ada dengan merek yang sama',
                'frame_id' => $similarFrame->frame_id
            ];
            
            Log::info('Detected similar image', [
                'frame_id' => $similarFrame->frame_id,
                'frame_merek' => $similarFrame->frame_merek,
                'requested_merek' => $request->frame_merek
            ]);
        }
    } elseif ($request->has('existing_frame_foto')) {
        // If using existing frame photo, we need to use a different approach
        // We'll skip image comparison for existing images or implement a special case
        // in the imageComparisonService if needed
        Log::info('Using existing frame photo for comparison', [
            'existing_frame_foto' => $request->existing_frame_foto
        ]);
        
        // Option 1: Skip image comparison for existing images
        // Do nothing here
        
        // Option 2: If you want to compare existing images, implement this:
        // $existingPhotoPath = public_path('storage/' . $request->existing_frame_foto);
        // if (file_exists($existingPhotoPath)) {
        //     // You would need to modify imageComparisonService to accept a path instead of UploadedFile
        //     // $similarFrame = $this->imageComparisonService->findSimilarFrameByPath($existingPhotoPath);
        //     // Then handle similarFrame the same way as above
        // }
    }
    
    // 2. Find frames with similar data (merek, lokasi, and kriteria must all match)
    $framesWithSimilarData = $this->findFramesWithSimilarData($request, $excludeFrameId);
    
    if (!empty($framesWithSimilarData)) {
        // Always record data similarity info
        $result['similarityDetails']['data'] = [
            'similar' => true,
            'message' => 'Data frame (merek, lokasi, kriteria) persis sama dengan ' . count($framesWithSimilarData) . ' frame lain',
            'frames' => $framesWithSimilarData
        ];
        
        Log::info('Detected similar data', [
            'similar_frames' => $framesWithSimilarData,
            'count' => count($framesWithSimilarData)
        ]);
        
        // If no similar frame by image found yet, use the first one with similar data
        if (!$result['similarFrame'] && !empty($framesWithSimilarData)) {
            $result['similarFrame'] = Frame::find($framesWithSimilarData[0]);
        }
    }
    
    return $result;
}

/**
 * Extract all similar frame IDs from similarity details
 * Modified to make brand comparison case-insensitive
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
                'similarity_details' => $similarityResults['similarityDetails']
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

    // Log activity
    ActivityLogService::log(
        'create',
        'frame',
        $frame->frame_id,
        null,
        $frame->toArray(),
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
    
    // Get other similar frames if available
    $otherSimilarFrameIds = $this->getAllSimilarFrameIds($similarityDetails);
    $otherSimilarFrames = [];
    
    // Get the frame_merek from the session data
    $formData = session('frame_form_data', []);
    $brand = $formData['frame_merek'] ?? null;
    
    if (!empty($otherSimilarFrameIds)) {
        // Fetch frames with the same ID and filter by brand (case-insensitive)
        $query = Frame::whereIn('frame_id', $otherSimilarFrameIds)
                    ->where('frame_id', '!=', $similar_frame_id);
                    
        // Add brand filter if brand is provided (case-insensitive)   
        if ($brand) {
            $query->whereRaw('LOWER(frame_merek) = ?', [strtolower($brand)]);
        }
        
        $otherSimilarFrames = $query->limit(5)->get();
    }
    
    return view('frame.confirm-duplicate', compact(
        'similarFrame', 
        'tempImagePath', 
        'similarityDetails', 
        'otherSimilarFrames'
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
            // User chose to cancel - redirect back to create page with old input
            $formData = session('frame_form_data', []);
            $tempImagePath = session('temp_image');
            
            // Prepare old input for form repopulation
            $oldInput = [
                'frame_merek' => $formData['frame_merek'] ?? null,
                'frame_lokasi' => $formData['frame_lokasi'] ?? null,
                'temp_image_path' => $tempImagePath // Tambahkan path gambar ke old input
            ];
            
            // Prepare nilai (criteria) data
            if (isset($formData['nilai']) && is_array($formData['nilai'])) {
                foreach ($formData['nilai'] as $kriteriaId => $subkriteriaIds) {
                    $oldInput["nilai.{$kriteriaId}"] = $subkriteriaIds;
                }
            }
            
            // Prepare manual input values
            if (isset($formData['nilai_manual']) && is_array($formData['nilai_manual'])) {
                foreach ($formData['nilai_manual'] as $kriteriaId => $value) {
                    $oldInput["nilai_manual.{$kriteriaId}"] = $value;
                }
            }
            
            // Prepare input type selection
            if (isset($formData['input_type']) && is_array($formData['input_type'])) {
                foreach ($formData['input_type'] as $kriteriaId => $type) {
                    $oldInput["input_type.{$kriteriaId}"] = $type;
                }
            }
            
            // Clear session
            session()->forget(['temp_image', 'frame_form_data', 'similarity_details']);
            
            return redirect()->route('frame.create')
                ->withInput($oldInput)
                ->with('info', 'Penambahan frame dibatalkan karena kesamaan dengan frame yang sudah ada.')
                ->with('temp_image', $tempImagePath); // Kirim path gambar ke view
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

    // Store old data for logging
    $oldData = $frame->toArray();
    $oldFrameMerek = $frame->frame_merek;

    // Check if there's a confirmed similarity from session
    $confirmedSimilarity = session('confirmed_similarity', false);

    // Check for similarities only if not already confirmed
    if (!$confirmedSimilarity) {
        // Create a complete request object for similarity checking
        $similarityRequest = clone $request;
        
        // If no new image uploaded, we need to pass the existing image path
        if (!$request->hasFile('frame_foto') && $frame->frame_foto) {
            // Instead of trying to create an UploadedFile object, which can cause issues,
            // we'll just pass the existing image path to the similarity checker
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
                'similarity_details' => $similarityResults['similarityDetails']
            ]);
            
            // Redirect to confirmation page
            return redirect()->route('frame.confirm-update-duplicate', $frame->frame_id);
        }
    } else {
        // Clear the confirmation flag for future requests
        session(['confirmed_similarity' => false]);
    }

    // Update frame basic data
    $frame->frame_merek = $request->frame_merek;
    $frame->frame_lokasi = $request->frame_lokasi;

    // Handle image upload if a new image is provided
    if ($request->hasFile('frame_foto')) {
        // Delete old image if it exists
        if ($frame->frame_foto && Storage::disk('public')->exists($frame->frame_foto)) {
            Storage::disk('public')->delete($frame->frame_foto);
        }
        
        // Check if we have a temp image from the confirmation flow
        $tempEditImage = session('temp_edit_image');
        if ($tempEditImage && Storage::disk('public')->exists($tempEditImage)) {
            // Use temp image from session
            $imageName = time() . '.jpg';
            Storage::disk('public')->move($tempEditImage, 'frames/' . $imageName);
            $frame->frame_foto = 'frames/' . $imageName;
        } else {
            // Upload new image directly
            $imageName = time() . '.' . $request->frame_foto->extension();
            $request->frame_foto->move(public_path('storage/frames'), $imageName);
            $frame->frame_foto = 'frames/' . $imageName;
        }
    }

    $frame->save();

    // Delete all existing frameSubkriterias for this frame
    FrameSubkriteria::where('frame_id', $frame->frame_id)->delete();

    // Process regular checkbox subkriteria
    $nilai = $request->input('nilai', []);
    $input_types = $request->input('input_type', []);
    
    // Debugging
    Log::info('Processing checkbox criteria', [
        'frame_id' => $frame->frame_id,
        'nilai' => $nilai,
        'input_types' => $input_types
    ]);
    
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
    
    // Debugging
    Log::info('Processing manual criteria', [
        'frame_id' => $frame->frame_id,
        'nilai_manual' => $nilai_manual
    ]);
    
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

    // Log activity
    ActivityLogService::log(
        'update',
        'frame',
        $frame->frame_id,
        $oldData,
        $frame->toArray(),
        'Memperbarui frame: ' . $oldFrameMerek . ' menjadi ' . $frame->frame_merek
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
    
    // Get other similar frames if available
    $otherSimilarFrameIds = [];
    
    if (isset($similarityResults['similarityDetails'])) {
        $otherSimilarFrameIds = $this->getAllSimilarFrameIds($similarityResults['similarityDetails']);
    }
    
    $otherSimilarFrames = [];
    
    if (!empty($otherSimilarFrameIds) && $similarFrameId) {
        $otherSimilarFrames = Frame::whereIn('frame_id', $otherSimilarFrameIds)
                                   ->where('frame_id', '!=', $similarFrameId)  
                                   ->where('frame_id', '!=', $frame_id)
                                   ->limit(5)
                                   ->get();
    }
    
    // Get temp image from session
    $tempImagePath = session('temp_edit_image');
    
    // Retrieve and log the form data from session to help with debugging
    $formData = session('frame_edit_data', []);
    Log::info('Form data in confirmUpdateDuplicate', [
        'frame_id' => $frame_id,
        'nilai' => $formData['nilai'] ?? [],
        'nilai_manual' => $formData['nilai_manual'] ?? [],
        'input_type' => $formData['input_type'] ?? []
    ]);
    
    return view('frame.confirm-update-duplicate', compact(
        'frame',
        'similarFrame',
        'otherSimilarFrames',
        'tempImagePath',
        'similarityResults'
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

    public function destroy(Frame $frame)
    {

        $frameData = $frame->toArray();
        $frameId = $frame->frame_id;
        $frameMerek = $frame->frame_merek;
        $frameHarga = $frame->frame_harga;

        if($frame->frame_foto) {
            $path = public_path('storage/' . $frame->frame_foto);
            if(file_exists($path)) {
                unlink($path);
            }
        }
        
        $frame->frameSubkriterias()->delete();
        $frame->delete();

         // Log activity
         ActivityLogService::log(
            'delete',
            'frame',
            $frameId,
            $frameData,
            null,
            'Menghapus frame: ' . $frameMerek
        );

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