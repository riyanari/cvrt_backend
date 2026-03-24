<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Http\Request;

class OwnerRoomController extends BaseApiController
{
    // GET /owner/floors/{floor}/rooms
    public function index(Floor $floor)
    {
        $rooms = $floor->rooms()
            ->withCount('acUnits')
            ->orderBy('name')
            ->get();

        return $this->ok($rooms);
    }

    // POST /owner/floors/{floor}/rooms
    public function store(Request $request, Floor $floor)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
        ]);

        $exists = Room::where('floor_id', $floor->id)
            ->where('name', $data['name'])
            ->exists();
        if ($exists) return $this->error('Nama ruangan sudah ada di lantai ini', 422);

        $room = $floor->rooms()->create($data);

        return $this->ok($room, 'Ruangan dibuat', 201);
    }

    // GET /owner/rooms/{room}
    public function show(Room $room)
    {
        $room->load(['floor.location:id,name', 'acUnits']);
        return $this->ok($room);
    }

    // PUT /owner/rooms/{room}
    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|nullable|string|max:100',
        ]);

        if (isset($data['name'])) {
            $exists = Room::where('floor_id', $room->floor_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $room->id)
                ->exists();
            if ($exists) return $this->error('Nama ruangan sudah ada di lantai ini', 422);
        }

        $room->update($data);

        return $this->ok($room, 'Ruangan diupdate');
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