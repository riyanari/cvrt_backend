<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcUnit;
use App\Models\Location;
use Illuminate\Http\Request;

class ClientMasterController extends BaseApiController
{
    public function lokasi(Request $request)
    {
        $user = $request->user();

        // Ambil lokasi yang terhubung ke user via pivot
        $data = $user->locations()
            ->withCount(['acUnits as ac_count'])
            ->orderByDesc('locations.id')
            ->get();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function ac(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'location_id' => 'required|exists:locations,id',
        ]);

        // Pastikan user punya akses ke lokasi via pivot
        $lokasi = $user->locations()
            ->where('locations.id', (int) $request->location_id)
            ->firstOrFail();

        $ac = AcUnit::where('location_id', $lokasi->id)
            ->orderByDesc('id')
            ->get();

        return $this->ok($ac);
    }
}
