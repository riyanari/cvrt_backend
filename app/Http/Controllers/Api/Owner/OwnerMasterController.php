<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcUnit;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OwnerMasterController extends BaseApiController
{
    // ==================== CLIENT CRUD ====================

    public function clients(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:name,email,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::where('role', 'client');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        $clients = $query->orderBy($sortBy, $sortOrder)->get();

        return response()->json([
            'success' => true,
            'data' => $clients,
            'meta' => [
                'total' => $clients->count(),
            ],
        ]);
    }

    public function clientShow($id)
    {
        $client = User::where('role', 'client')
            ->withCount('locations')
            ->with([
                'locations' => function ($q) {
                    $q->withCount('acUnits')->orderBy('name');
                }
            ])
            ->find($id);

        if (!$client) {
            return $this->error('Client tidak ditemukan', 404);
        }

        return $this->ok($client);
    }

    public function clientStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ]);

        $client = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $client->makeHidden(['password', 'remember_token']);

        return $this->ok($client, 'Client berhasil dibuat', 201);
    }

    public function clientUpdate(Request $request, $id)
    {
        $client = User::where('role', 'client')->find($id);

        if (!$client) {
            return $this->error('Client tidak ditemukan', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($client->id),
            ],
            'phone' => 'sometimes|required|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
            'password_confirmation' => 'required_with:password|string|min:6',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'name' => $request->input('name', $client->name),
            'email' => $request->input('email', $client->email),
            'phone' => $request->input('phone', $client->phone),
            'address' => $request->input('address', $client->address),
            'notes' => $request->input('notes', $client->notes),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $client->update($updateData);
        $client->makeHidden(['password', 'remember_token']);

        return $this->ok($client, 'Client berhasil diupdate');
    }

    public function clientDestroy(Request $request, $id)
    {
        $client = User::where('role', 'client')->find($id);

        if (!$client) {
            return $this->error('Client tidak ditemukan', 404);
        }

        $locationCount = $client->locations()->count();

        if ($locationCount > 0) {
            return $this->error("Tidak dapat menghapus client yang masih memiliki {$locationCount} lokasi", 400);
        }

        $activeServiceCount = \App\Models\Service::where('client_id', $id)
            ->whereIn('status', [
                'menunggu_konfirmasi',
                'ditugaskan',
                'dalam_perjalanan',
                'dalam_pengerjaan',
                'menunggu_konfirmasi_owner'
            ])
            ->count();

        if ($activeServiceCount > 0) {
            return $this->error("Tidak dapat menghapus client yang masih memiliki {$activeServiceCount} servis aktif", 400);
        }

        $client->delete();

        return $this->ok(null, 'Client berhasil dihapus');
    }

    public function clientStats($id)
    {
        $client = User::where('role', 'client')->find($id);

        if (!$client) {
            return $this->error('Client tidak ditemukan', 404);
        }

        $monthlyStats = \App\Models\Service::where('client_id', $id)
            ->selectRaw('
                MONTH(created_at) as bulan,
                YEAR(created_at) as tahun,
                COUNT(*) as total_servis,
                SUM(CASE WHEN status = "selesai" THEN 1 ELSE 0 END) as selesai,
                SUM(total_biaya) as total_pendapatan
            ')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->limit(12)
            ->get();

        $serviceByJenis = \App\Models\Service::where('client_id', $id)
            ->select('jenis', DB::raw('COUNT(*) as total'))
            ->groupBy('jenis')
            ->get();

        $recentServices = \App\Models\Service::where('client_id', $id)
            ->with(['lokasi:id,name', 'teknisi:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $this->ok([
            'monthly_stats' => $monthlyStats,
            'service_by_jenis' => $serviceByJenis,
            'recent_services' => $recentServices,
            'total_locations' => $client->locations()->count(),
            'total_ac_units' => AcUnit::whereHas('room.location.users', function ($q) use ($id) {
                $q->where('users.id', $id);
            })->count(),
        ]);
    }

    // ==================== TEKNISI CRUD ====================

    public function teknisi(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'spesialisasi' => 'nullable|string',
            'sort_by' => 'nullable|string|in:name,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::where('role', 'teknisi');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('spesialisasi', 'like', $search);
            });
        }

        if ($request->filled('spesialisasi')) {
            $query->where('spesialisasi', $request->spesialisasi);
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        $teknisi = $query->orderBy($sortBy, $sortOrder)->get();

        $specializations = User::where('role', 'teknisi')
            ->whereNotNull('spesialisasi')
            ->distinct('spesialisasi')
            ->pluck('spesialisasi')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $teknisi,
            'filter_options' => [
                'specializations' => $specializations,
            ],
            'meta' => [
                'total' => $teknisi->count(),
            ],
        ]);
    }

    public function teknisiShow($id)
    {
        $teknisi = User::where('role', 'teknisi')
            ->withCount([
                'servisTeknisi',
                'servisTeknisi as completed_services_count' => function ($query) {
                    $query->where('status', 'selesai');
                }
            ])
            ->find($id);

        if (!$teknisi) {
            return $this->error('Teknisi tidak ditemukan', 404);
        }

        return $this->ok($teknisi);
    }

    public function teknisiStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ]);

        $teknisi = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'teknisi',
            'spesialisasi' => 'servis',
            'rating' => 0,
            'total_service' => 0,
            'email_verified_at' => now(),
        ]);

        $teknisi->makeHidden(['password', 'remember_token']);

        return $this->ok($teknisi, 'Teknisi berhasil dibuat', 201);
    }

    public function teknisiUpdate(Request $request, $id)
    {
        $teknisi = User::where('role', 'teknisi')->find($id);

        if (!$teknisi) {
            return $this->error('Teknisi tidak ditemukan', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($teknisi->id),
            ],
            'phone' => 'sometimes|required|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
            'password_confirmation' => 'required_with:password|string|min:6',
            'spesialisasi' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:500',
            'experience' => 'nullable|string|max:100',
            'certifications' => 'nullable|string|max:1000',
            'hourly_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'name' => $request->input('name', $teknisi->name),
            'email' => $request->input('email', $teknisi->email),
            'phone' => $request->input('phone', $teknisi->phone),
            'spesialisasi' => $request->input('spesialisasi', $teknisi->spesialisasi),
            'address' => $request->input('address', $teknisi->address),
            'experience' => $request->input('experience', $teknisi->experience),
            'certifications' => $request->input('certifications', $teknisi->certifications),
            'hourly_rate' => $request->input('hourly_rate', $teknisi->hourly_rate),
            'notes' => $request->input('notes', $teknisi->notes),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $teknisi->update($updateData);
        $teknisi->makeHidden(['password', 'remember_token']);

        return $this->ok($teknisi, 'Teknisi berhasil diupdate');
    }

    public function teknisiDestroy(Request $request, $id)
    {
        $teknisi = User::where('role', 'teknisi')->find($id);

        if (!$teknisi) {
            return $this->error('Teknisi tidak ditemukan', 404);
        }

        $activeServiceCount = \App\Models\Service::where('technician_id', $id)
            ->whereIn('status', ['dalam_perjalanan', 'dalam_pengerjaan', 'menunggu_konfirmasi_owner'])
            ->count();

        if ($activeServiceCount > 0) {
            return $this->error("Tidak dapat menghapus teknisi yang masih memiliki {$activeServiceCount} servis aktif", 400);
        }

        $teknisi->delete();

        return $this->ok(null, 'Teknisi berhasil dihapus');
    }

    public function availableTeknisi()
    {
        $availableTeknisi = User::where('role', 'teknisi')
            ->select('id', 'name', 'spesialisasi')
            ->get()
            ->map(function ($tek) {
                $tek->active_assignments = \App\Models\Service::where('technician_id', $tek->id)
                    ->whereIn('status', ['dalam_perjalanan', 'dalam_pengerjaan'])
                    ->count();

                return $tek;
            });

        return $this->ok($availableTeknisi);
    }

    // ==================== LOKASI CRUD ====================

    public function lokasiIndex(Request $request)
    {
        $q = Location::query();

        if ($request->filled('user_id')) {
            $q->whereHas('users', function ($query) use ($request) {
                $query->where('users.id', $request->user_id);
            });
        }

        $locations = $q->orderBy('id', 'desc')->get()->map(function ($location) {
            $location->total_ac_units = AcUnit::whereHas('room', function ($q) use ($location) {
                $q->where('location_id', $location->id);
            })->count();

            return $location;
        });

        return $this->ok($locations);
    }

    public function lokasiStore(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'place_id' => 'nullable|string|max:255',
            'gmaps_url' => 'nullable|string|max:500',
        ]);

        $client = User::where('role', 'client')->findOrFail($validated['client_id']);

        $lokasi = Location::create([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'place_id' => $validated['place_id'] ?? null,
            'gmaps_url' => $validated['gmaps_url'] ?? null,
            'last_service' => now(),
        ]);

        $lokasi->users()->syncWithoutDetaching([$client->id]);

        return $this->ok($lokasi->load('users'), 'Lokasi dibuat', 201);
    }

    public function lokasiUpdate(Request $request, $id)
    {
        $lokasi = Location::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'last_service' => 'sometimes|nullable|date',
        ]);

        $lokasi->update($request->only('name', 'address', 'last_service'));

        return $this->ok($lokasi, 'Lokasi diupdate');
    }

    public function lokasiDestroy($id)
    {
        $lokasi = Location::findOrFail($id);

        if ($lokasi->acUnits()->count() > 0) {
            return $this->error('Tidak dapat menghapus lokasi yang masih memiliki AC', 400);
        }

        $lokasi->delete();

        return $this->ok(null, 'Lokasi dihapus');
    }

    // ==================== AC UNIT CRUD ====================

    public function acIndex(Request $request)
    {
        $q = AcUnit::with(['room.location', 'room.floor']);

        if ($request->filled('location_id')) {
            $q->whereHas('room', function ($query) use ($request) {
                $query->where('location_id', $request->location_id);
            });
        }

        return $this->ok($q->orderBy('id', 'desc')->get());
    }

    public function acStore(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'capacity' => 'nullable|string|max:50',
            'last_service' => 'nullable|date',
        ]);

        $ac = AcUnit::create([
            'room_id' => $request->room_id,
            'name' => $request->name,
            'brand' => $request->brand ?? 'Unknown',
            'type' => $request->type ?? 'Standard',
            'capacity' => $request->capacity ?? '1 PK',
            'last_service' => $request->last_service ?? now(),
        ]);

        $location = $ac->room->location;

        if ($location) {
            $location->update([
                'jumlah_ac' => AcUnit::whereHas('room', function ($q) use ($location) {
                    $q->where('location_id', $location->id);
                })->count(),
                'last_service' => $request->last_service ?? now(),
            ]);
        }

        return $this->ok($ac->load(['room.location', 'room.floor']), 'AC dibuat', 201);
    }

    public function acUpdate(Request $request, $id)
    {
        $ac = AcUnit::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'brand' => 'sometimes|nullable|string|max:100',
            'type' => 'sometimes|nullable|string|max:100',
            'capacity' => 'sometimes|nullable|string|max:50',
            'last_service' => 'sometimes|nullable|date',
        ]);

        $ac->update($request->only('name', 'brand', 'type', 'capacity', 'last_service'));

        if ($request->filled('last_service')) {
            $location = $ac->room->location;

            if ($location) {
                $location->update([
                    'last_service' => $request->last_service,
                    'jumlah_ac' => AcUnit::whereHas('room', function ($q) use ($location) {
                        $q->where('location_id', $location->id);
                    })->count(),
                ]);
            }
        }

        return $this->ok($ac->load(['room.location', 'room.floor']), 'AC diupdate');
    }

    public function acDestroy($id)
    {
        $ac = AcUnit::findOrFail($id);
        $location = $ac->room->location;

        $ac->delete();

        if ($location) {
            $location->update([
                'jumlah_ac' => AcUnit::whereHas('room', function ($q) use ($location) {
                    $q->where('location_id', $location->id);
                })->count(),
            ]);
        }

        return $this->ok(null, 'AC dihapus');
    }

    public function acShow($id)
    {
        $ac = AcUnit::with(['room.location', 'room.floor'])->find($id);

        if (!$ac) {
            return $this->error('AC tidak ditemukan', 404);
        }

        return $this->ok($ac);
    }
}