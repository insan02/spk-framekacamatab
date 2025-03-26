<?php

namespace App\Http\Controllers;

use App\Models\Frame;
use App\Models\Kriteria;
use App\Models\Subkriteria;
use App\Models\FrameSubkriteria;
use Illuminate\Http\Request;

class FrameController extends Controller
{
    // FrameController.php (index method)
    public function index()
{
    // Get all frames to check total updates needed
    $allFrames = Frame::with(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria'])
                      ->get();
    
    // Get all kriteria
    $allKriterias = Kriteria::all()->pluck('kriteria_id')->toArray();
    
    // Check which frames need updates
    $totalNeedsUpdate = 0;
    foreach ($allFrames as $frame) {
        // Get kriteria IDs that this frame has subkriterias for
        $frameKriterias = $frame->frameSubkriterias->pluck('kriteria_id')->toArray();
        
        // Check if any kriteria is missing
        $missingKriterias = array_diff($allKriterias, $frameKriterias);
        
        if (count($missingKriterias) > 0) {
            $totalNeedsUpdate++;
        }
    }

    // Get frames with pagination
    $frames = Frame::with(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria'])
                   ->orderBy('frame_id', 'asc')
                   ->paginate(20);
    
    // Check which frames on current page need updates
    $frameNeedsUpdate = [];
    foreach ($frames as $frame) {
        // Get kriteria IDs that this frame has subkriterias for
        $frameKriterias = $frame->frameSubkriterias->pluck('kriteria_id')->toArray();
        
        // Check if any kriteria is missing
        $missingKriterias = array_diff($allKriterias, $frameKriterias);
        
        if (count($missingKriterias) > 0) {
            $frameNeedsUpdate[$frame->frame_id] = $missingKriterias;
        }
    }
    
    return view('frame.index', compact('frames', 'frameNeedsUpdate', 'totalNeedsUpdate'));
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

    public function store(Request $request)
{
    $request->validate([
        'frame_merek' => 'required|string|max:255',
        'frame_foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'frame_harga' => 'required|numeric',
        'nilai.*' => 'required|array',
        'nilai.*.*' => 'required|exists:subkriterias,subkriteria_id'
    ]);

    // Save frame
    $imageName = time().'.'.$request->frame_foto->extension();  
    $request->frame_foto->move(public_path('storage/frames'), $imageName);

    $frame = Frame::create([
        'frame_merek' => $request->frame_merek,
        'frame_foto' => 'frames/' . $imageName,
        'frame_harga' => $request->frame_harga
    ]);

    // Find the price kriteria
    $priceKriteria = Kriteria::where('kriteria_nama', 'like', '%harga%')->first();

    // Save multiple subkriteria for each kriteria
    foreach ($request->nilai as $kriteria_id => $subkriteria_ids) {
        // Skip price kriteria as we'll handle it separately
        if ($kriteria_id == $priceKriteria?->kriteria_id) {
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

    // If we found a price kriteria, assign the appropriate subkriteria based on price
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

    return redirect()->route('frame.index')->with('success', 'Frame berhasil ditambahkan');
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
        'nilai.*' => 'nullable|array',
        'nilai.*.*' => 'nullable|exists:subkriterias,subkriteria_id'
    ]);

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
    }

    $frame->frame_merek = $request->frame_merek;
    $frame->frame_harga = $request->frame_harga;
    $frame->save();

    // Find the price kriteria
    $priceKriteria = Kriteria::where('kriteria_nama', 'like', '%harga%')->first();

    // Hapus semua subkriteria sebelumnya
    $frame->frameSubkriterias()->delete();
    
    // Proses input kriteria baru
    if ($request->has('nilai')) {
        foreach ($request->nilai as $kriteria_id => $subkriteria_ids) {
            // Hapus elemen array kosong
            $subkriteria_ids = array_filter($subkriteria_ids);
            
            // Skip price kriteria as we'll handle it separately
            if ($kriteria_id == $priceKriteria?->kriteria_id) {
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

    // If we found a price kriteria, assign the appropriate subkriteria based on price
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