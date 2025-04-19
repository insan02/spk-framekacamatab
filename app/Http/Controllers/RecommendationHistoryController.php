<?php

namespace App\Http\Controllers;

use App\Models\RecommendationHistory;
use Illuminate\Http\Request;

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
}