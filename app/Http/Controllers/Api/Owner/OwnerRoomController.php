<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Location;
use App\Models\Room;
use Illuminate\Http\Request;

class OwnerRoomController extends BaseApiController
{
    // GET /owner/locations/{location}/rooms?floor_id=
    public function byLocation(Request $request, Location $location)
    {
        $request->validate([
            'floor_id' => 'nullable|integer|exists:floors,id',
        ]);

        $query = $location->rooms()
            ->with(['floor:id,name,number'])
            ->withCount('acUnits');

        if ($request->filled('floor_id')) {
            $query->where('floor_id', $request->floor_id);
        }

        $rooms = $query
            ->orderBy('floor_id')
            ->orderBy('name')
            ->get();

        return $this->ok($rooms);
    }

    // POST /owner/locations/{location}/rooms
    public function store(Request $request, Location $location)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'floor_id' => 'required|integer|exists:floors,id',
        ]);

        $exists = Room::where('location_id', $location->id)
            ->where('floor_id', $data['floor_id'])
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return $this->error('Nama ruangan sudah ada di lantai ini', 422);
        }

        $room = $location->rooms()->create($data);

        return $this->ok(
            $room->load(['floor:id,name,number'])->loadCount('acUnits'),
            'Ruangan dibuat',
            201
        );
    }

    // GET /owner/rooms/{room}
    public function show(Room $room)
    {
        $room->load([
            'location:id,name',
            'floor:id,name,number',
            'acUnits',
        ]);

        return $this->ok($room);
    }

    // PUT /owner/rooms/{room}
    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|nullable|string|max:100',
            'floor_id' => 'sometimes|required|integer|exists:floors,id',
        ]);

        $floorId = $data['floor_id'] ?? $room->floor_id;
        $name = $data['name'] ?? $room->name;

        $exists = Room::where('location_id', $room->location_id)
            ->where('floor_id', $floorId)
            ->where('name', $name)
            ->where('id', '!=', $room->id)
            ->exists();

        if ($exists) {
            return $this->error('Nama ruangan sudah ada di lantai ini', 422);
        }

        $room->update($data);

        return $this->ok(
            $room->load(['floor:id,name,number'])->loadCount('acUnits'),
            'Ruangan diupdate'
        );
    }

    // DELETE /owner/rooms/{room}
    public function destroy(Room $room)
    {
        if ($room->acUnits()->count() > 0) {
            return $this->error('Tidak dapat menghapus ruangan yang masih memiliki AC', 400);
        }

        $room->delete();

        return $this->ok(null, 'Ruangan dihapus');
    }
}