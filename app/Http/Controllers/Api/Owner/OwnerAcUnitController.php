<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcUnit;
use App\Models\Location;
use Illuminate\Http\Request;

class OwnerAcUnitController extends BaseApiController
{
    // GET /owner/ac-units?room_id=&floor_id=&location_id=
    public function index(Request $request)
    {
        $request->validate([
            'room_id' => 'nullable|integer|exists:rooms,id',
            'floor_id' => 'nullable|integer|exists:floors,id',
            'location_id' => 'nullable|integer|exists:locations,id',
        ]);

        $q = AcUnit::with(['room.location', 'room.floor']);

        if ($request->filled('room_id')) {
            $q->where('room_id', $request->room_id);
        }

        if ($request->filled('floor_id')) {
            $q->whereHas('room', fn($r) => $r->where('floor_id', $request->floor_id));
        }

        if ($request->filled('location_id')) {
            $q->whereHas('room', fn($r) => $r->where('location_id', $request->location_id));
        }

        return $this->ok($q->orderByDesc('id')->get());
    }

    // POST /owner/ac-units  (WAJIB room_id)
    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'capacity' => 'nullable|string|max:50',
            'last_service' => 'nullable|date',
        ]);

        $ac = AcUnit::create([
            'room_id' => $data['room_id'],
            'name' => $data['name'],
            'brand' => $data['brand'] ?? 'Unknown',
            'type' => $data['type'] ?? 'Standard',
            'capacity' => $data['capacity'] ?? '1 PK',
            'last_service' => $data['last_service'] ?? now(),
        ]);

        $this->syncLocationCountersFromAc($ac);

        return $this->ok($ac->load(['room.location', 'room.floor']), 'AC dibuat', 201);
    }

    // GET /owner/ac-units/{acUnit}
    public function show(AcUnit $acUnit)
    {
        return $this->ok($acUnit->load(['room.location', 'room.floor']));
    }

    // PUT /owner/ac-units/{acUnit}
    public function update(Request $request, AcUnit $acUnit)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'brand' => 'sometimes|nullable|string|max:100',
            'type' => 'sometimes|nullable|string|max:100',
            'capacity' => 'sometimes|nullable|string|max:50',
            'last_service' => 'sometimes|nullable|date',

            // optional kalau mau pindah room:
            'room_id' => 'sometimes|required|exists:rooms,id',
        ]);

        $oldLocationId = optional($acUnit->room)->location_id;

        $acUnit->update($data);

        // sync lokasi lama & baru (kalau room_id berubah)
        $this->syncLocationCountersById($oldLocationId);
        $this->syncLocationCountersFromAc($acUnit);

        return $this->ok($acUnit->load(['room.location', 'room.floor']), 'AC diupdate');
    }

    // DELETE /owner/ac-units/{acUnit}
    public function destroy(AcUnit $acUnit)
    {
        $locationId = optional($acUnit->room)->location_id;

        $acUnit->delete();

        $this->syncLocationCountersById($locationId);

        return $this->ok(null, 'AC dihapus');
    }

    private function syncLocationCountersFromAc(AcUnit $acUnit): void
    {
        $locationId = optional($acUnit->room)->location_id;
        if (!$locationId) return;

        $this->syncLocationCountersById($locationId);
    }

    public function byRoom(\App\Models\Room $room)
    {
        $acs = AcUnit::with(['room.location', 'room.floor'])
            ->where('room_id', $room->id)
            ->orderByDesc('id')
            ->get();

        return $this->ok($acs);
    }

    private function syncLocationCountersById(?int $locationId): void
    {
        if (!$locationId) return;

        $total = AcUnit::whereHas('room', function ($q) use ($locationId) {
            $q->where('location_id', $locationId);
        })->count();

        $last = AcUnit::whereHas('room', function ($q) use ($locationId) {
            $q->where('location_id', $locationId);
        })->max('last_service');

        Location::where('id', $locationId)->update([
            'jumlah_ac' => $total,
            'last_service' => $last,
        ]);
    }
}