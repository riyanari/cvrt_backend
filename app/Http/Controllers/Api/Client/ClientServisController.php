<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcUnit;
use App\Models\Service;
use App\Models\ServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientServisController extends BaseApiController
{
    public function requestCuci(Request $request)
    {
        $user = $request->user();
        $clientId = (int) $user->id;

        $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'semua_ac' => 'nullable|boolean',
            'ac_units' => 'nullable|array|min:1',
            'ac_units.*' => 'integer|exists:ac_units,id',
            'catatan' => 'nullable|string|max:500',
            'tanggal_berkunjung' => 'nullable|date|after_or_equal:today',
        ]);

        $location = $user->locations()
            ->where('locations.id', (int) $request->location_id)
            ->firstOrFail();

        if ($request->boolean('semua_ac') || empty($request->ac_units)) {
            $acUnitsIds = AcUnit::whereHas('room', function ($q) use ($location) {
                $q->where('location_id', $location->id);
            })->pluck('id')->map(fn($id) => (int) $id)->toArray();

            if (empty($acUnitsIds)) {
                return $this->error('Tidak ada AC di lokasi ini', 400);
            }
        } else {
            $acUnits = AcUnit::whereIn('id', $request->ac_units)
                ->whereHas('room', function ($q) use ($location) {
                    $q->where('location_id', $location->id);
                })
                ->get();

            if ($acUnits->count() !== count($request->ac_units)) {
                return $this->error('Salah satu AC tidak ditemukan di lokasi ini', 400);
            }

            $acUnitsIds = collect($request->ac_units)->map(fn($id) => (int) $id)->values()->all();
        }

        return DB::transaction(function () use ($request, $clientId, $location, $acUnitsIds) {
            $serviceData = [
                'jenis' => 'cuci',
                'status' => 'menunggu_konfirmasi',
                'client_id' => $clientId,
                'location_id' => $location->id,
                'ac_units' => $acUnitsIds,
                'jumlah_ac' => count($acUnitsIds),
                'catatan' => $request->catatan,
                'keluhan_client' => $request->catatan ?? ('Pencucian ' . count($acUnitsIds) . ' unit AC'),
            ];

            if ($request->filled('tanggal_berkunjung')) {
                $serviceData['tanggal_berkunjung'] = $request->tanggal_berkunjung;
            }

            $service = Service::create($serviceData);

            $rows = collect($acUnitsIds)->map(fn($acId) => [
                'service_id' => $service->id,
                'ac_unit_id' => (int) $acId,
                'status' => 'menunggu_konfirmasi',
                'tanggal_berkunjung' => $service->tanggal_berkunjung,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            ServiceItem::insert($rows);

            return $this->ok(
                $service->load([
                    'lokasi:id,name,address,latitude,longitude,gmaps_url',
                    'items.acUnit:id,room_id,name,brand,type,capacity,last_service',
                    'items.acUnit.room:id,location_id,floor_id,name,code',
                    'items.acUnit.room.floor:id,name,number',
                    'items.acUnit.room.location:id,name,address',
                ]),
                'Request pencucian AC berhasil dikirim',
                201
            );
        });
    }

    public function requestPerbaikan(Request $request)
    {
        $user = $request->user();
        $clientId = (int) $user->id;

        $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'ac_unit_id' => 'required|integer|exists:ac_units,id',
            'keluhan' => 'required|string|max:1000',
            'foto_keluhan' => 'nullable|array|max:5',
            'foto_keluhan.*' => 'image|max:2048',
            'priority' => 'nullable|in:tinggi,sedang,rendah',
            'tanggal_berkunjung' => 'nullable|date|after_or_equal:today',
        ]);

        $location = $user->locations()
            ->where('locations.id', (int) $request->location_id)
            ->firstOrFail();

        $acUnit = AcUnit::where('id', (int) $request->ac_unit_id)
            ->whereHas('room', function ($q) use ($location) {
                $q->where('location_id', $location->id);
            })
            ->firstOrFail();

        $fotoKeluhanPaths = [];
        if ($request->hasFile('foto_keluhan')) {
            foreach ($request->file('foto_keluhan') as $foto) {
                $fotoKeluhanPaths[] = $foto->store('services/keluhan', 'public');
            }
        }

        return DB::transaction(function () use ($request, $clientId, $location, $acUnit, $fotoKeluhanPaths) {
            $serviceData = [
                'jenis' => 'perbaikan',
                'status' => 'menunggu_konfirmasi',
                'client_id' => $clientId,
                'location_id' => $location->id,
                'ac_unit_id' => $acUnit->id,
                'jumlah_ac' => 1,
                'keluhan_client' => $request->keluhan,
                'catatan' => $request->keluhan,
                'foto_keluhan' => $fotoKeluhanPaths,
            ];

            if ($request->filled('tanggal_berkunjung')) {
                $serviceData['tanggal_berkunjung'] = $request->tanggal_berkunjung;
            }

            $service = Service::create($serviceData);

            ServiceItem::create([
                'service_id' => $service->id,
                'ac_unit_id' => $acUnit->id,
                'status' => 'menunggu_konfirmasi',
                'tanggal_berkunjung' => $service->tanggal_berkunjung,
                'catatan' => $request->keluhan,
            ]);

            return $this->ok(
                $service->load([
                    'lokasi:id,name,address,latitude,longitude,gmaps_url',
                    'items.acUnit:id,room_id,name,brand,type,capacity,last_service',
                    'items.acUnit.room:id,location_id,floor_id,name,code',
                    'items.acUnit.room.floor:id,name,number',
                    'items.acUnit.room.location:id,name,address',
                    'items.technician:id,name,phone',
                ]),
                'Request perbaikan AC berhasil dikirim',
                201
            );
        });
    }

    public function requestInstalasi(Request $request)
    {
        $user = $request->user();
        $clientId = (int) $user->id;

        $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'jumlah_ac' => 'required|integer|min:1|max:10',
            'jenis_ac' => 'required|in:split,cassette,standing,central',
            'kapasitas_ac' => 'required|string|max:50',
            'catatan' => 'nullable|string|max:500',
            'tanggal_berkunjung' => 'nullable|date|after_or_equal:today',
        ]);

        $location = $user->locations()
            ->where('locations.id', (int) $request->location_id)
            ->firstOrFail();

        $serviceData = [
            'jenis' => 'instalasi',
            'status' => 'menunggu_konfirmasi',
            'client_id' => $clientId,
            'location_id' => $location->id,
            'jumlah_ac' => $request->jumlah_ac,
            'catatan' => "Instalasi {$request->jumlah_ac} unit AC {$request->jenis_ac} {$request->kapasitas_ac}. " . ($request->catatan ?? ''),
            'keluhan_client' => $request->catatan ?? "Request instalasi {$request->jumlah_ac} unit AC",
        ];

        if ($request->filled('tanggal_berkunjung')) {
            $serviceData['tanggal_berkunjung'] = $request->tanggal_berkunjung;
        }

        $service = Service::create($serviceData);

        return $this->ok($service, 'Request instalasi AC berhasil dikirim', 201);
    }

    public function index(Request $request)
    {
        $clientId = (int) $request->user()->id;

        $services = Service::where('client_id', $clientId)
            ->with([
                'lokasi:id,name,address,latitude,longitude,gmaps_url',
                'items' => function ($q) {
                    $q->with([
                        'acUnit:id,room_id,name,brand,type,capacity,last_service',
                        'acUnit.room:id,location_id,floor_id,name,code',
                        'acUnit.room.floor:id,name,number',
                        'acUnit.room.location:id,name,address',
                        'technician:id,name,phone',
                    ])->orderBy('id');
                },
            ])
            ->orderByDesc('created_at')
            ->get();

        $result = $services->map(function ($s) {
            $uniqueTech = $s->items->pluck('technician')->filter()->unique('id')->values();

            $items = $s->items->map(function ($item) {
                $room = optional($item->acUnit)->room;
                $floor = optional($room)->floor;

                $item->setAttribute('room_info', $room ? [
                    'id' => $room->id,
                    'name' => $room->name,
                    'code' => $room->code,
                ] : null);

                $item->setAttribute('floor_info', $floor ? [
                    'id' => $floor->id,
                    'name' => $floor->name,
                    'number' => $floor->number,
                ] : null);

                return $item;
            });

            return [
                'id' => $s->id,
                'jenis' => $s->jenis,
                'status' => $s->status,
                'tanggal_berkunjung' => $s->tanggal_berkunjung,
                'created_at' => $s->created_at,
                'lokasi' => $s->lokasi,
                'jumlah_ac' => $items->count() > 0 ? $items->count() : (int) ($s->jumlah_ac ?? 0),
                'catatan' => $s->catatan,
                'items' => $items,
                'teknisi' => $uniqueTech,
            ];
        });

        return $this->ok($result);
    }

    public function show($id, Request $request)
    {
        $clientId = (int) $request->user()->id;

        $service = Service::with([
            'lokasi:id,name,address,latitude,longitude,gmaps_url',
            'teknisi:id,name,phone',
            'items' => function ($q) {
                $q->with([
                    'acUnit:id,room_id,name,brand,type,capacity,last_service',
                    'acUnit.room:id,location_id,floor_id,name,code',
                    'acUnit.room.floor:id,name,number',
                    'acUnit.room.location:id,name,address',
                    'technician:id,name,phone',
                ])->orderBy('id');
            },
            'pembayaran',
        ])
        ->where('id', $id)
        ->where('client_id', $clientId)
        ->first();

        if (!$service) {
            return $this->error('Servis tidak ditemukan atau tidak memiliki akses', 404);
        }

        $items = $service->items->map(function ($item) {
            $room = optional($item->acUnit)->room;
            $floor = optional($room)->floor;

            $item->setAttribute('room_info', $room ? [
                'id' => $room->id,
                'name' => $room->name,
                'code' => $room->code,
            ] : null);

            $item->setAttribute('floor_info', $floor ? [
                'id' => $floor->id,
                'name' => $floor->name,
                'number' => $floor->number,
            ] : null);

            if (!empty($item->foto_sebelum)) {
                $item->foto_sebelum_urls = $this->getFullPhotoUrls($item->foto_sebelum);
            }
            if (!empty($item->foto_pengerjaan)) {
                $item->foto_pengerjaan_urls = $this->getFullPhotoUrls($item->foto_pengerjaan);
            }
            if (!empty($item->foto_sesudah)) {
                $item->foto_sesudah_urls = $this->getFullPhotoUrls($item->foto_sesudah);
            }
            if (!empty($item->foto_suku_cadang)) {
                $item->foto_suku_cadang_urls = $this->getFullPhotoUrls($item->foto_suku_cadang);
            }

            return $item;
        });

        $response = [
            'id' => $service->id,
            'jenis' => $service->jenis,
            'status' => $service->status,
            'created_at' => $service->created_at,
            'updated_at' => $service->updated_at,
            'tanggal_berkunjung' => $service->tanggal_berkunjung,
            'tanggal_ditugaskan' => $service->tanggal_ditugaskan,
            'tanggal_mulai' => $service->tanggal_mulai,
            'tanggal_selesai' => $service->tanggal_selesai,
            'catatan' => $service->catatan,
            'keluhan_client' => $service->keluhan_client,
            'foto_keluhan' => $service->foto_keluhan,
            'foto_keluhan_urls' => !empty($service->foto_keluhan)
                ? $this->getFullPhotoUrls($service->foto_keluhan)
                : [],
            'lokasi' => $service->lokasi,
            'teknisi' => $service->teknisi,
            'pembayaran' => $service->pembayaran,
            'jumlah_ac' => $items->count() > 0 ? $items->count() : (int) ($service->jumlah_ac ?? 0),
            'items' => $items,
        ];

        return $this->ok($response);
    }

    private function getFullPhotoUrls($photos)
    {
        if (empty($photos)) {
            return [];
        }

        if (!is_array($photos)) {
            $photos = [$photos];
        }

        return array_map(function ($photo) {
            return Storage::disk('public')->url($photo);
        }, $photos);
    }
}