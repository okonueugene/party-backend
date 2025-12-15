<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Constituency;
use App\Models\Ward;
use Illuminate\Http\Request;

class GeographicController extends Controller
{
    /**
     * Get all counties.
     */
    public function counties()
    {
        $counties = County::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $counties,
        ]);
    }

    /**
     * Get constituencies for a county.
     */
    public function constituencies(Request $request, $countyId = null)
    {
        $countyId = $countyId ?? $request->get('county_id');

        if (!$countyId) {
            return response()->json([
                'success' => false,
                'message' => 'County ID is required.',
            ], 400);
        }

        $constituencies = Constituency::where('county_id', $countyId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $constituencies,
        ]);
    }

    /**
     * Get wards for a constituency.
     */
    public function wards(Request $request, $constituencyId = null)
    {
        $constituencyId = $constituencyId ?? $request->get('constituency_id');

        if (!$constituencyId) {
            return response()->json([
                'success' => false,
                'message' => 'Constituency ID is required.',
            ], 400);
        }

        $wards = Ward::where('constituency_id', $constituencyId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wards,
        ]);
    }
}

