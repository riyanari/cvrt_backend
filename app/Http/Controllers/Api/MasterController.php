<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcUnit;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;

class MasterController extends BaseApiController
{
    /**
     * Get locations (different data for owner vs client)
     */
    public function locations(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'owner') {
            // Owner bisa melihat semua lokasi semua client
            $query = Location::with(['client:id,name', 'acUnits'])
                ->withCount('acUnits');

            if ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            $locations = $query->orderBy('name')->get();

            return $this->ok($locations);
        }

        // Client hanya bisa melihat lokasi miliknya sendiri
        $locations = Location::where('client_id', $user->id)
            ->withCount('acUnits')
            ->with('acUnits:id,location_id,name,brand,type,capacity')
            ->orderBy('name')
            ->get();

        return $this->ok($locations);
    }

    /**
     * Get AC units (different data for owner vs client)
     */
    public function acUnits(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'client_id' => 'nullable|exists:users,id,role,client',
        ]);

        $query = AcUnit::query()->with('location');

        if ($user->role === 'owner') {
            // Owner bisa filter by client_id atau location_id
            if ($request->filled('client_id')) {
                $query->whereHas('location', function ($q) use ($request) {
                    $q->where('client_id', $request->client_id);
                });
            }

            if ($request->filled('location_id')) {
                $query->where('location_id', $request->location_id);
            }
        } else {
            // Client hanya bisa melihat AC di lokasi miliknya
            $query->whereHas('location', function ($q) use ($user) {
                $q->where('client_id', $user->id);
            });

            if ($request->filled('location_id')) {
                // Pastikan lokasi milik client
                $location = Location::where('id', $request->location_id)
                    ->where('client_id', $user->id)
                    ->first();

                if (!$location) {
                    return $this->error('Lokasi tidak ditemukan', 404);
                }

                $query->where('location_id', $request->location_id);
            }
        }

        $acUnits = $query->orderBy('name')->get();

        return $this->ok($acUnits);
    }

    /**
     * Get single location detail
     */
    public function locationShow(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'owner') {
            $location = Location::with(['client', 'acUnits'])
                ->withCount('acUnits')
                ->find($id);

            if (!$location) {
                return $this->error('Lokasi tidak ditemukan', 404);
            }
        } else {
            $location = Location::where('client_id', $user->id)
                ->with('acUnits')
                ->withCount('acUnits')
                ->find($id);

            if (!$location) {
                return $this->error('Lokasi tidak ditemukan', 404);
            }
        }

        return $this->ok($location);
    }

    /**
     * Get single AC unit detail
     */
    public function acUnitShow(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'owner') {
            $acUnit = AcUnit::with('location.client')->find($id);
        } else {
            $acUnit = AcUnit::whereHas('location', function ($q) use ($user) {
                $q->where('client_id', $user->id);
            })->with('location')->find($id);
        }

        if (!$acUnit) {
            return $this->error('AC unit tidak ditemukan', 404);
        }

        return $this->ok($acUnit);
    }

    /**
     * Create location (only for owner)
     */
    public function locationStore(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'client_id' => 'required|exists:users,id,role,client',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        $location = Location::create([
            'client_id' => $request->client_id,
            'name' => $request->name,
            'address' => $request->address,
            'last_service' => now(),
        ]);

        return $this->ok($location, 'Lokasi berhasil dibuat', 201);
    }

    /**
     * Update location (only for owner)
     */
    public function locationUpdate(Request $request, $id)
    {
        if ($request->user()->role !== 'owner') {
            return $this->error('Unauthorized', 403);
        }

        $location = Location::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'last_service' => 'sometimes|nullable|date',
        ]);

        $location->update($request->only('name', 'address', 'last_service'));

        return $this->ok($location, 'Lokasi berhasil diupdate');
    }

    /**
     * Delete location (only for owner)
     */
    public function locationDestroy(Request $request, $id)
    {
        if ($request->user()->role !== 'owner') {
            return $this->error('Unauthorized', 403);
        }

        $location = Location::findOrFail($id);

        if ($location->acUnits()->count() > 0) {
            return $this->error('Tidak dapat menghapus lokasi yang masih memiliki AC', 400);
        }

        $location->delete();

        return $this->ok(null, 'Lokasi berhasil dihapus');
    }

    /**
     * Create AC unit (only for owner)
     */
    public function acUnitStore(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'capacity' => 'nullable|string|max:50',
            'last_service' => 'nullable|date',
        ]);

        $acUnit = AcUnit::create([
            'location_id' => $request->location_id,
            'name' => $request->name,
            'brand' => $request->brand ?? 'Unknown',
            'type' => $request->type ?? 'Standard',
            'capacity' => $request->capacity ?? '1 PK',
            'last_service' => $request->last_service ?? now(),
        ]);

        // Update location's last_service
        $location = Location::find($request->location_id);
        if ($location) {
            $location->update([
                'last_service' => $request->last_service ?? now(),
                'jumlah_ac' => $location->acUnits()->count(),
            ]);
        }

        return $this->ok($acUnit, 'AC unit berhasil dibuat', 201);
    }

    /**
     * Update AC unit (only for owner)
     */
    public function acUnitUpdate(Request $request, $id)
    {
        if ($request->user()->role !== 'owner') {
            return $this->error('Unauthorized', 403);
        }

        $acUnit = AcUnit::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'brand' => 'sometimes|nullable|string|max:100',
            'type' => 'sometimes|nullable|string|max:100',
            'capacity' => 'sometimes|nullable|string|max:50',
            'last_service' => 'sometimes|nullable|date',
        ]);

        $acUnit->update($request->only('name', 'brand', 'type', 'capacity', 'last_service'));

        // Update location's last_service if last_service is updated
        if ($request->filled('last_service') && $acUnit->location) {
            $acUnit->location->update([
                'last_service' => $request->last_service,
                'jumlah_ac' => $acUnit->location->acUnits()->count(),
            ]);
        }

        return $this->ok($acUnit, 'AC unit berhasil diupdate');
    }

    /**
     * Delete AC unit (only for owner)
     */
    public function acUnitDestroy(Request $request, $id)
    {
        if ($request->user()->role !== 'owner') {
            return $this->error('Unauthorized', 403);
        }

        $acUnit = AcUnit::findOrFail($id);
        $location = $acUnit->location;

        $acUnit->delete();

        // Update location's ac count
        if ($location) {
            $location->update([
                'jumlah_ac' => $location->acUnits()->count(),
            ]);
        }

        return $this->ok(null, 'AC unit berhasil dihapus');
    }
}
