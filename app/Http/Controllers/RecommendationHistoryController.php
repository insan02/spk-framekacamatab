<?php

namespace App\Http\Controllers;

use App\Models\RecommendationHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RecommendationHistoryController extends Controller
{
    public function index()
    {
        $histories = RecommendationHistory::orderBy('created_at', 'asc')->paginate(10);
        return view('rekomendasi.index', compact('histories'));
    }

    public function show($id)
    {
        $history = RecommendationHistory::findOrFail($id);
        return view('rekomendasi.show', compact('history'));
    }

    public function print($id)
    {
        $history = RecommendationHistory::findOrFail($id);
        return view('rekomendasi.print', compact('history'));
    }

    public function destroy($id)
    {
        $history = RecommendationHistory::findOrFail($id);
        
        // Delete associated images before deleting the record
        $this->deleteHistoryImages($history);
        
        $history->delete();
        return redirect()->route('rekomendasi.index')
            ->with('success', 'Riwayat rekomendasi berhasil dihapus.');
    }

    public function printAll(Request $request)
    {
        // Get filter parameters and sanitize them
        $startDate = $request->input('start_date') ? date('Y-m-d', strtotime($request->input('start_date'))) : null;
        $endDate = $request->input('end_date') ? date('Y-m-d', strtotime($request->input('end_date'))) : null;
        
        // Base query
        $query = RecommendationHistory::query()->orderBy('created_at', 'asc');
        
        // Apply date filters if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59'
            ]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        
        // Get all histories for the report
        $histories = $query->get();
        
        // Return print view
        return view('rekomendasi.print-all', [
            'histories' => $histories,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
    
    public function resetData(Request $request)
    {
        // Validate request - removed confirm_reset validation
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);
        
        // Get filter parameters and sanitize them
        $startDate = $request->input('start_date') ? date('Y-m-d', strtotime($request->input('start_date'))) : null;
        $endDate = $request->input('end_date') ? date('Y-m-d', strtotime($request->input('end_date'))) : null;
        
        // Base query
        $query = RecommendationHistory::query();
        
        // Apply date filters if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59'
            ]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        
        // Get all histories that will be deleted to process their images
        $historiesToDelete = $query->get();
        
        // Initialize counters
        $count = $historiesToDelete->count();
        $imageCount = 0;
        
        // Process each history to delete associated images
        foreach ($historiesToDelete as $history) {
            $deletedImages = $this->deleteHistoryImages($history);
            $imageCount += $deletedImages;
        }
        
        // Delete the records from database
        $query->delete();
        
        return redirect()->route('rekomendasi.index')
            ->with('success', "Berhasil mereset $count data riwayat rekomendasi dan menghapus $imageCount file gambar terkait.");
    }
    
    /**
     * Delete all images associated with a history record
     * 
     * @param RecommendationHistory $history
     * @return int Number of images deleted
     */
    private function deleteHistoryImages($histories)
{
    $imageCount = 0;

    // Jika input berupa koleksi, iterasi setiap item
    if ($histories instanceof \Illuminate\Support\Collection) {
        foreach ($histories as $history) {
            $imageCount += $this->deleteHistoryImages($history);
        }
        return $imageCount;
    }

    // Jika input adalah satu instance RecommendationHistory
    try {
        if (isset($histories->rekomendasi_data) && is_array($histories->rekomendasi_data)) {
            foreach ($histories->rekomendasi_data as $recommendation) {
                if (isset($recommendation['frame']['frame_foto'])) {
                    $imagePath = $recommendation['frame']['frame_foto'];

                    if (empty($imagePath) || !is_string($imagePath)) {
                        continue;
                    }

                    $imagePath = str_replace('storage/', '', $imagePath);

                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                        $imageCount++;
                    }
                }
            }
        }
    } catch (\Exception $e) {
        Log::error('Error deleting history images: ' . $e->getMessage(), [
            'history_id' => $histories->id ?? null,
            'exception' => $e
        ]);
    }

    return $imageCount;
}

}