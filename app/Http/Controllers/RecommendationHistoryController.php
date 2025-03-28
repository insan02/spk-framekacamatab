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

    public function destroy($id)
    {
        $history = RecommendationHistory::findOrFail($id);
        $history->delete();

        return redirect()->route('rekomendasi.index')
            ->with('success', 'Riwayat rekomendasi berhasil dihapus.');
    }
}