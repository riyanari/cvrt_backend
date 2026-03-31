<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Floor;
use Illuminate\Http\Request;

class OwnerFloorController extends BaseApiController
{
    // GET /owner/floors
    public function index()
    {
        $floors = Floor::orderByRaw('number IS NULL, number ASC')
            ->orderBy('name')
            ->get();

        return $this->ok($floors);
    }

    // POST /owner/floors
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'number' => 'nullable|integer',
        ]);

        if (!is_null($data['number'] ?? null)) {
            $exists = Floor::where('number', $data['number'])->exists();
            if ($exists) {
                return $this->error('Nomor lantai sudah ada', 422);
            }
        }

        $floor = Floor::create($data);

        return $this->ok($floor, 'Lantai dibuat', 201);
    }

    // GET /owner/floors/{floor}
    public function show(Floor $floor)
    {
        $floor->load(['rooms:id,location_id,floor_id,name,code']);
        return $this->ok($floor);
    }

    // PUT /owner/floors/{floor}
    public function update(Request $request, Floor $floor)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'number' => 'sometimes|nullable|integer',
        ]);

        if (array_key_exists('number', $data) && !is_null($data['number'])) {
            $exists = Floor::where('number', $data['number'])
                ->where('id', '!=', $floor->id)
                ->exists();

            if ($exists) {
                return $this->error('Nomor lantai sudah ada', 422);
            }
        }

        $floor->update($data);

        return $this->ok($floor, 'Lantai diupdate');
    }

    // DELETE /owner/floors/{floor}
    public function destroy(Floor $floor)
    {
        if ($floor->rooms()->count() > 0) {
            return $this->error('Tidak dapat menghapus lantai yang masih memiliki ruangan', 400);
        }

        $floor->delete();

        return $this->ok(null, 'Lantai dihapus');
    }
}