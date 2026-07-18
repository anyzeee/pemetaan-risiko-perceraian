<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Recommendation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $riskLevel  = $request->query('risk_level', 'all');
        $districtId = $request->query('district', 'all');
        $status     = $request->query('status', 'all'); // all | completed | pending

        $query = Recommendation::query();

        if ($riskLevel !== 'all') {
            $query->where('risk_level', $riskLevel);
        }

        if ($status === 'completed') {
            $query->where('is_completed', true);
        } elseif ($status === 'pending') {
            $query->where('is_completed', false);
        }

        $recommendations = $query->orderByRaw("CASE risk_level
            WHEN 'critical' THEN 1
            WHEN 'high'     THEN 2
            WHEN 'moderate' THEN 3
            WHEN 'low'      THEN 4
            ELSE 5 END")
            ->orderByRaw("CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high'   THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low'    THEN 4
            ELSE 5 END")
            ->get();

        $allDistricts = District::orderBy('name')->get();

        // Statistik keseluruhan (tanpa filter)
        $totalAll     = Recommendation::count();
        $totalDone    = Recommendation::where('is_completed', true)->count();
        $totalPending = $totalAll - $totalDone;
        $percentage   = $totalAll > 0 ? round(($totalDone / $totalAll) * 100) : 0;

        // Group rekomendasi per level untuk tampilan
        $grouped = [];
        foreach (['critical', 'high', 'moderate', 'low'] as $level) {
            $recs = $recommendations->where('risk_level', $level)->values();
            if ($recs->count() > 0) {
                $grouped[$level] = $recs;
            }
        }

        return view('recommendation.index', [
            'grouped'      => $grouped,
            'allDistricts' => $allDistricts,
            'selectedRisk' => $riskLevel,
            'selectedDist' => $districtId,
            'selectedStat' => $status,
            'totalAll'     => $totalAll,
            'totalDone'    => $totalDone,
            'totalPending' => $totalPending,
            'percentage'   => $percentage,
        ]);
    }

    /**
     * Toggle status selesai/belum selesai sebuah rekomendasi.
     */
    public function toggleComplete(Request $request, int $id): RedirectResponse
    {
        $rec = Recommendation::findOrFail($id);
        $rec->is_completed = !$rec->is_completed;
        $rec->completed_at = $rec->is_completed ? now()->toDateString() : null;
        $rec->save();

        return redirect()->back()->with('success',
            $rec->is_completed
                ? "Program \"{$rec->program}\" ditandai selesai."
                : "Program \"{$rec->program}\" dibatalkan."
        );
    }
}