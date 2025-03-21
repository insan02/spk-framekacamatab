<?php

namespace App\Http\Controllers;

use App\Models\Frame;
use App\Models\Kriteria;
use App\Models\Subkriteria;
use App\Models\FrameSubkriteria;
use Illuminate\Http\Request;

class FrameController extends Controller
{
    public function index()
    {
        $frames = Frame::with(['frameSubkriterias.kriteria', 'frameSubkriterias.subkriteria'])->get();
        return view('frame.index', compact('frames'));
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
        'nilai.*' => 'required|array',
        'nilai.*.*' => 'required|exists:subkriterias,subkriteria_id'
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

    // Update frameSubkriterias
    $frame->frameSubkriterias()->delete();
    
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
}