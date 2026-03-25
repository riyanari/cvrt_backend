<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcUnit;
use Illuminate\Http\Request;

class ClientMasterController extends BaseApiController
{
    public function lokasi(Request $request)
    {
        $user = $request->user();

        $data = $user->locations()
            ->orderByDesc('locations.id')
            ->get();

        return $this->ok($data);
    }

    public function ac(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
        ]);

        // pastikan user memang punya akses ke lokasi ini
        $lokasi = $user->locations()
            ->where('locations.id', (int) $request->location_id)
            ->firstOrFail();

        $ac = AcUnit::with([
                'room:id,floor_id,name,code',
                'room.floor:id,location_id,name,number',
                'room.floor.location:id,name,address',
            ])
            ->whereHas('room.floor', function ($q) use ($lokasi) {
                $q->where('location_id', $lokasi->id);
            })
            ->orderByDesc('id')
            ->get();

        return $this->ok($ac);
    }
}