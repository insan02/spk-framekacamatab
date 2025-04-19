<?php

namespace App\Http\Controllers;

use App\Models\Frame;
use App\Models\Kriteria;
use App\Models\Subkriteria;
use App\Models\FrameSubkriteria;
use App\Services\ImageComparisonService;
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
                      ->orWhere('frame_harga', 'like', '%' . $search . '%')
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
        'frame_harga' => 'required|numeric',
        'frame_lokasi' => 'required|string|max:255',
        'nilai.*' => 'required|array',
        'nilai.*.*' => 'required|exists:subkriterias,subkriteria_id'
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

/**
 * Check for similar frames based on both image and data
 * 
 * @param Request $request
 * @return array
 */
private function checkForSimilarFrames(Request $request)
{
    // Initialize result array
    $result = [
        'similarFrame' => null,
        'similarityDetails' => []
    ];
    
    // 1. Check for similar images only if a new image is uploaded
    if ($request->hasFile('frame_foto')) {
        $similarFrame = $this->imageComparisonService->findSimilarFrame($request->file('frame_foto'));
        
        if ($similarFrame) {
            $result['similarFrame'] = $similarFrame;
            $result['similarityDetails']['image'] = [
                'similar' => true,
                'message' => 'Foto frame serupa dengan frame yang sudah ada',
                'frame_id' => $similarFrame->frame_id
            ];
        }
    }
    
    // Find frames with similar data (merek, lokasi, and kriteria must all match)
    $framesWithSimilarData = $this->findFramesWithSimilarData($request);
    
    if (!empty($framesWithSimilarData)) {
        // If we don't have a similar frame yet, use the first one with similar data
        if (!$result['similarFrame']) {
            $result['similarFrame'] = Frame::find($framesWithSimilarData[0]);
        }
        
        $result['similarityDetails']['data'] = [
            'similar' => true,
            'message' => 'Data frame (merek, lokasi, kriteria) mirip dengan ' . count($framesWithSimilarData) . ' frame lain',
            'frames' => $framesWithSimilarData
        ];
    }
    
    return $result;
}

/**
 * Find frames with similar data (merek, lokasi, and kriteria must all match)
 *
 * @param Request $request
 * @return array
 */
private function findFramesWithSimilarData(Request $request)
{
    // Step 1: Find frames with similar merek and lokasi
    $similarMerekAndLokasi = Frame::where('frame_merek', $request->frame_merek)
                                  ->where('frame_lokasi', $request->frame_lokasi)
                                  ->get();
    
    if ($similarMerekAndLokasi->isEmpty()) {
        return []; // No matches on basic data
    }
    
    // Step 2: Filter for similar price range
    $priceRange = 10000; // Define a price range threshold (e.g., ±10,000)
    $framesWithSimilarPrice = $similarMerekAndLokasi->filter(function($frame) use ($request, $priceRange) {
        return $frame->frame_harga >= ($request->frame_harga - $priceRange) && 
               $frame->frame_harga <= ($request->frame_harga + $priceRange);
    });
    
    if ($framesWithSimilarPrice->isEmpty()) {
        return []; // No matches after price filter
    }
    
    // Step 3: Check for similar criteria combinations (only if criteria values provided)
    if ($request->has('nilai')) {
        $requestedCriteria = [];
        foreach ($request->nilai as $kriteria_id => $subkriteria_ids) {
            foreach ($subkriteria_ids as $subkriteria_id) {
                $requestedCriteria[] = [
                    'kriteria_id' => $kriteria_id,
                    'subkriteria_id' => $subkriteria_id
                ];
            }
        }
        
        // Filter frames with similar criteria
        $framesWithSimilarCriteria = [];
        $criteriaMatchThreshold = 70; // 70% similarity threshold
        
        foreach ($framesWithSimilarPrice as $frameItem) {
            // Load frame subkriterias if not already loaded
            if (!$frameItem->relationLoaded('frameSubkriterias')) {
                $frameItem->load('frameSubkriterias');
            }
            
            $frameCriteria = $frameItem->frameSubkriterias->map(function($item) {
                return [
                    'kriteria_id' => $item->kriteria_id,
                    'subkriteria_id' => $item->subkriteria_id
                ];
            })->toArray();
            
            // Calculate similarity percentage
            $matchCount = 0;
            foreach ($requestedCriteria as $reqCriteria) {
                foreach ($frameCriteria as $frmCriteria) {
                    if ($reqCriteria['kriteria_id'] == $frmCriteria['kriteria_id'] && 
                        $reqCriteria['subkriteria_id'] == $frmCriteria['subkriteria_id']) {
                        $matchCount++;
                        break;
                    }
                }
            }
            
            $totalCriteria = max(count($requestedCriteria), count($frameCriteria));
            if ($totalCriteria > 0) {
                $similarityPercentage = ($matchCount / $totalCriteria) * 100;
                
                // Consider frames with >70% criteria match as similar
                if ($similarityPercentage >= $criteriaMatchThreshold) {
                    $framesWithSimilarCriteria[] = $frameItem->frame_id;
                }
            }
        }
        
        return $framesWithSimilarCriteria;
    }
    
    // If no criteria to compare, just return the frames with similar merek, lokasi, and price
    return $framesWithSimilarPrice->pluck('frame_id')->toArray();
}

// Add this new method to handle the actual saving logic
// Update the saveNewFrame method in FrameController
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
    $frame_harga = $request->input('frame_harga');
    $frame_lokasi = $request->input('frame_lokasi');
    
    // Verify we have all required data before proceeding
    if (!$frame_merek || !$frame_harga || !$frame_lokasi) {
        // Log the error and throw an exception
        Log::error('Missing required frame data', [
            'frame_merek' => $frame_merek,
            'frame_harga' => $frame_harga,
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
        'frame_harga' => $frame_harga,
        'frame_lokasi' => $frame_lokasi
    ]);

    // Temukan kriteria harga
    $priceKriteria = Kriteria::where('kriteria_nama', 'like', '%harga%')->first();

    // Simpan beberapa subkriteria untuk setiap kriteria
    $nilai = $request->input('nilai', []);
    if (is_array($nilai)) {
        foreach ($nilai as $kriteria_id => $subkriteria_ids) {
            // Lewati kriteria harga karena akan ditangani secara terpisah
            if ($priceKriteria && $kriteria_id == $priceKriteria->kriteria_id) {
                continue;
            }
            
            foreach ($subkriteria_ids as $subkriteria_id) {
                FrameSubkriteria::create([
                    'frame_id' => $frame->frame_id,
                    'kriteria_id' => $kriteria_id,
                    'subkriteria_id' => $subkriteria_id,
                ]);
            }
        }
    }

    // Jika kita menemukan kriteria harga, tetapkan subkriteria yang sesuai berdasarkan harga
    if ($priceKriteria) {
        $priceSubkriteria = $this->getPriceSubkriteria($priceKriteria->kriteria_id, $frame_harga);
        
        if ($priceSubkriteria) {
            FrameSubkriteria::create([
                'frame_id' => $frame->frame_id,
                'kriteria_id' => $priceKriteria->kriteria_id,
                'subkriteria_id' => $priceSubkriteria->subkriteria_id,
            ]);
        }
    }

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
    
    if (!empty($otherSimilarFrameIds)) {
        $otherSimilarFrames = Frame::whereIn('frame_id', $otherSimilarFrameIds)
                                 ->where('frame_id', '!=', $similar_frame_id)
                                 ->limit(5) // Limit to prevent too many frames displayed
                                 ->get();
    }
    
    return view('frame.confirm-duplicate', compact(
        'similarFrame', 
        'tempImagePath', 
        'similarityDetails', 
        'otherSimilarFrames'
    ));
}

/**
 * Extract all similar frame IDs from similarity details
 */
private function getAllSimilarFrameIds($similarityDetails)
{
    $allIds = [];
    
    foreach ($similarityDetails as $type => $details) {
        if (isset($details['frames']) && is_array($details['frames'])) {
            $allIds = array_merge($allIds, $details['frames']);
        }
    }
    
    return array_unique($allIds);
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
        
        // Process the save
        return $this->saveNewFrame($reconstructedRequest, $tempImagePath);
    } else {
        // User chose to cancel
        // Clean up temporary image
        $tempImagePath = session('temp_image');
        if ($tempImagePath && Storage::disk('public')->exists($tempImagePath)) {
            Storage::disk('public')->delete($tempImagePath);
        }
        
        // Clear session
        session()->forget(['temp_image', 'frame_form_data', 'similarity_details']);
        
        return redirect()->route('frame.create')
                ->with('info', 'Penambahan frame dibatalkan karena kesamaan dengan frame yang sudah ada.');
    }
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

// New method to get the appropriate price subkriteria
private function getPriceSubkriteria($kriteria_id, $price)
{
    $subkriterias = Subkriteria::where('kriteria_id', $kriteria_id)->get();
    
    // This assumes your subkriteria names contain price ranges like "< 100000" or "100000 - 200000"
    foreach ($subkriterias as $subkriteria) {
        $name = strtolower($subkriteria->subkriteria_nama);
        
        if (strpos($name, '<') !== false) {
            // Handle "less than" case
            $max = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
            if ($price < $max) {
                return $subkriteria;
            }
        } 
        elseif (strpos($name, '-') !== false) {
            // Handle range case
            $parts = explode('-', $name);
            $min = (int) filter_var(trim($parts[0]), FILTER_SANITIZE_NUMBER_INT);
            $max = (int) filter_var(trim($parts[1]), FILTER_SANITIZE_NUMBER_INT);
            
            if ($price >= $min && $price <= $max) {
                return $subkriteria;
            }
        }
        elseif (strpos($name, '>') !== false) {
            // Handle "greater than" case
            $min = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
            if ($price > $min) {
                return $subkriteria;
            }
        }
    }
    
    return null; // Return null if no matching subkriteria found
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
        'frame_harga' => 'required|numeric',
        'frame_lokasi' => 'required|string|max:255',
        'nilai.*' => 'nullable|array',
        'nilai.*.*' => 'nullable|exists:subkriterias,subkriteria_id'
    ]);

    // Check if there's a confirmed similarity from session
    $confirmedSimilarity = session('confirmed_similarity', false);

    // Check for similarities only if not already confirmed and data has changed
    if (!$confirmedSimilarity) {
        $dataChanged = $frame->frame_merek != $request->frame_merek || 
                    $frame->frame_harga != $request->frame_harga ||
                    $frame->frame_lokasi != $request->frame_lokasi ||
                    $request->hasFile('frame_foto');
                    
        if ($dataChanged) {
            $similarityResults = $this->checkForSimilarFrames($request);
            
            // If similar frame found (that's not the current frame)
            if ($similarityResults['similarFrame'] && $similarityResults['similarFrame']->frame_id != $frame->frame_id) {
                // Store the temporary image if one was uploaded
                if ($request->hasFile('frame_foto')) {
                    $tempImageName = 'temp_edit_' . time() . '.' . $request->frame_foto->extension();
                    $request->frame_foto->move(public_path('storage/temp'), $tempImageName);
                    session(['temp_edit_image' => 'temp/' . $tempImageName]);
                }
                
                // Include the similar frame's data with its subkriterias
                $similarityResults['similarFrame']->load(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria']);
                
                // Store form data in session
                $formData = $request->except(['frame_foto', '_token', '_method']);
                session(['frame_edit_data' => $formData]);
                
                // Store similarity results in session for display in the view
                session(['similarity_results' => $similarityResults]);
                
                // Set confirmed similarity flag for the next request
                session(['confirmed_similarity' => true]);
                
                // Pass back the input and show similarity alert
                return redirect()->back()
                    ->withInput()
                    ->with('similarity_results', $similarityResults);
            }
        }
    } else {
        // Clear the confirmation flag for future requests
        session()->forget('confirmed_similarity');
    }

    // Continue with the update process
    if ($request->hasFile('frame_foto')) {
        if($frame->frame_foto) {
            $oldPath = public_path('storage/' . $frame->frame_foto);
            if(file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        $imageName = time().'.'.$request->frame_foto->extension();  
        $request->frame_foto->move(public_path('storage/frames'), $imageName);
        $frame->frame_foto = 'frames/' . $imageName;
    } else {
        // Check if we have a temporary image from the similarity check
        $tempEditImage = session('temp_edit_image');
        if ($tempEditImage && Storage::disk('public')->exists($tempEditImage)) {
            if($frame->frame_foto) {
                $oldPath = public_path('storage/' . $frame->frame_foto);
                if(file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            $imageName = time().'.jpg';
            Storage::disk('public')->move($tempEditImage, 'frames/' . $imageName);
            $frame->frame_foto = 'frames/' . $imageName;
            
            // Clear the temporary image from session
            session()->forget('temp_edit_image');
        }
    }

    $frame->frame_merek = $request->frame_merek;
    $frame->frame_harga = $request->frame_harga;
    $frame->frame_lokasi = $request->frame_lokasi;
    $frame->save();

    // Find price criteria
    $priceKriteria = Kriteria::where('kriteria_nama', 'like', '%harga%')->first();

    // Remove all previous subkriteria
    $frame->frameSubkriterias()->delete();
    
    // Process new criteria inputs
    if ($request->has('nilai')) {
        foreach ($request->nilai as $kriteria_id => $subkriteria_ids) {
            // Filter out empty array elements
            $subkriteria_ids = array_filter($subkriteria_ids);
            
            // Skip price criteria as it will be handled separately
            if ($priceKriteria && $kriteria_id == $priceKriteria->kriteria_id) {
                continue;
            }
            
            foreach ($subkriteria_ids as $subkriteria_id) {
                FrameSubkriteria::create([
                    'frame_id' => $frame->frame_id,
                    'kriteria_id' => $kriteria_id,
                    'subkriteria_id' => $subkriteria_id,
                ]);
            }
        }
    }

    // If we found price criteria, assign appropriate subkriteria based on price
    if ($priceKriteria) {
        $priceSubkriteria = $this->getPriceSubkriteria($priceKriteria->kriteria_id, $request->frame_harga);
        
        if ($priceSubkriteria) {
            FrameSubkriteria::create([
                'frame_id' => $frame->frame_id,
                'kriteria_id' => $priceKriteria->kriteria_id,
                'subkriteria_id' => $priceSubkriteria->subkriteria_id,
            ]);
        }
    }

    // Clear any remaining session data
    session()->forget(['frame_edit_data', 'temp_edit_image', 'confirmed_similarity']);

    return redirect()->route('frame.index')->with('success', 'Frame berhasil diperbarui');
}

    public function destroy(Frame $frame)
    {
        if($frame->frame_foto) {
            $path = public_path('storage/' . $frame->frame_foto);
            if(file_exists($path)) {
                unlink($path);
            }
        }
        
        $frame->frameSubkriterias()->delete();
        $frame->delete();

        return redirect()->route('frame.index')->with('success', 'Frame berhasil dihapus');
    }

    public function resetFrameKriteria()
{
    // Hapus semua subkriteria untuk semua frame
    FrameSubkriteria::truncate();

    return redirect()->route('frame.index')->with('success', 'Kriteria untuk semua frame berhasil direset');
}
}