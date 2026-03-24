<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcUnit;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientServisController extends BaseApiController
{
    // Request servis CUCI
    // public function requestCuci(Request $request)
    // {
    //     $clientId = $request->user()->id;

    //     $request->validate([
    //         'location_id' => 'required|exists:locations,id',
    //         'semua_ac' => 'nullable|boolean',
    //         'ac_units' => 'nullable|array|min:1',
    //         'ac_units.*' => 'exists:ac_units,id',
    //         'catatan' => 'nullable|string|max:500',
    //         'tanggal_berkunjung' => 'nullable|date|after_or_equal:today', // Tambahkan validasi
    //     ]);

    //     // Pastikan lokasi milik client
    //     $location = Location::where('id', $request->location_id)
    //         ->where('client_id', $clientId)
    //         ->with('acUnits:id,location_id')
    //         ->firstOrFail();

    //     $acUnitsIds = [];

    //     // Jika pilih "semua AC" atau tidak memilih AC sama sekali
    //     if ($request->boolean('semua_ac') || empty($request->ac_units)) {
    //         // Default: semua AC
    //         $acUnitsIds = $location->acUnits->pluck('id')->toArray();

    //         if (empty($acUnitsIds)) {
    //             return $this->error('Tidak ada AC di lokasi ini', 400);
    //         }
    //     } else {
    //         // Validasi AC units yang dipilih
    //         $acUnits = AcUnit::whereIn('id', $request->ac_units)
    //             ->where('location_id', $location->id)
    //             ->get();

    //         if (count($acUnits) !== count($request->ac_units)) {
    //             return $this->error('Salah satu AC tidak ditemukan di lokasi ini', 400);
    //         }

    //         $acUnitsIds = $request->ac_units;
    //     }

    //     // Buat service dengan tanggal_berkunjung
    //     $serviceData = [
    //         'jenis' => 'cuci',
    //         'status' => 'menunggu_konfirmasi',
    //         'client_id' => $clientId,
    //         'location_id' => $location->id,
    //         'ac_units' => $acUnitsIds,
    //         'jumlah_ac' => count($acUnitsIds),
    //         'catatan' => $request->catatan,
    //         'keluhan_client' => $request->catatan ?? 'Pencucian ' . count($acUnitsIds) . ' unit AC',
    //     ];

    //     // Tambahkan tanggal_berkunjung jika ada
    //     if ($request->has('tanggal_berkunjung')) {
    //         $serviceData['tanggal_berkunjung'] = $request->tanggal_berkunjung;
    //     }

    //     $service = Service::create($serviceData);

    //     return $this->ok($service, 'Request pencucian AC berhasil dikirim', 201);
    // }

    public function requestCuci(Request $request)
{
    $user = $request->user();
    $clientId = (int) $user->id;

    $request->validate([
        'location_id' => 'required|exists:locations,id',
        'semua_ac' => 'nullable|boolean',
        'ac_units' => 'nullable|array|min:1',
        'ac_units.*' => 'exists:ac_units,id',
        'catatan' => 'nullable|string|max:500',
        'tanggal_berkunjung' => 'nullable|date|after_or_equal:today',
    ]);

    // ✅ lokasi harus terhubung ke user via pivot
    $location = $user->locations()
        ->where('locations.id', (int) $request->location_id)
        ->with('acUnits:id,location_id')
        ->firstOrFail();

    // tentukan AC yang dipilih
    if ($request->boolean('semua_ac') || empty($request->ac_units)) {
        $acUnitsIds = $location->acUnits->pluck('id')->toArray();
        if (empty($acUnitsIds)) return $this->error('Tidak ada AC di lokasi ini', 400);
    } else {
        $acUnits = AcUnit::whereIn('id', $request->ac_units)
            ->where('location_id', $location->id)
            ->get();

        if ($acUnits->count() !== count($request->ac_units)) {
            return $this->error('Salah satu AC tidak ditemukan di lokasi ini', 400);
        }

        $acUnitsIds = array_map('intval', $request->ac_units);
    }

    return DB::transaction(function () use ($request, $clientId, $location, $acUnitsIds) {

        $serviceData = [
            'jenis' => 'cuci',
            'status' => 'menunggu_konfirmasi',
            'client_id' => $clientId,          // ✅ tetap simpan siapa yg request
            'location_id' => $location->id,
            'ac_units' => $acUnitsIds,         // optional (compat)
            'jumlah_ac' => count($acUnitsIds),
            'catatan' => $request->catatan,
            'keluhan_client' => $request->catatan ?? ('Pencucian ' . count($acUnitsIds) . ' unit AC'),
        ];

        if ($request->filled('tanggal_berkunjung')) {
            $serviceData['tanggal_berkunjung'] = $request->tanggal_berkunjung;
        }

        $service = Service::create($serviceData);

        $rows = collect($acUnitsIds)->map(fn ($acId) => [
            'service_id' => $service->id,
            'ac_unit_id' => (int) $acId,
            'status' => 'menunggu_konfirmasi',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        ServiceItem::insert($rows);

        return $this->ok(
            $service->load(['items.acUnit']),
            'Request pencucian AC berhasil dikirim',
            201
        );
    });
}


    // Request servis PERBAIKAN
    public function requestPerbaikan(Request $request)
{
    $user = $request->user();
    $clientId = (int) $user->id;

    $request->validate([
        'location_id' => 'required|exists:locations,id',
        'ac_unit_id' => 'required|exists:ac_units,id',
        'keluhan' => 'required|string|max:1000',
        'foto_keluhan' => 'nullable|array|max:5',
        'foto_keluhan.*' => 'image|max:2048',
        'priority' => 'nullable|in:tinggi,sedang,rendah',
        'tanggal_berkunjung' => 'nullable|date|after_or_equal:today',
    ]);

    // ✅ lokasi harus terhubung ke user via pivot
    $location = $user->locations()
        ->where('locations.id', (int) $request->location_id)
        ->firstOrFail();

    // ✅ AC unit harus milik lokasi tsb
    $acUnit = AcUnit::where('id', (int) $request->ac_unit_id)
        ->where('location_id', $location->id)
        ->firstOrFail();

    // upload foto keluhan (tetap di service level)
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

            // optional legacy fields (boleh dipertahankan)
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
            $service->load(['lokasi', 'items.acUnit', 'items.technician']),
            'Request perbaikan AC berhasil dikirim',
            201
        );
    });
}


    // Request servis INSTALASI
    public function requestInstalasi(Request $request)
    {
        $clientId = $request->user()->id;

        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'jumlah_ac' => 'required|integer|min:1|max:10',
            'jenis_ac' => 'required|in:split,cassette,standing,central',
            'kapasitas_ac' => 'required|string|max:50',
            'catatan' => 'nullable|string|max:500',
            'tanggal_berkunjung' => 'nullable|date|after_or_equal:today', // Tambahkan validasi
        ]);

        // Pastikan lokasi milik client
        $location = Location::where('id', $request->location_id)
            ->where('client_id', $clientId)
            ->firstOrFail();

        // Buat service instalasi dengan tanggal_berkunjung
        $serviceData = [
            'jenis' => 'instalasi',
            'status' => 'menunggu_konfirmasi',
            'client_id' => $clientId,
            'location_id' => $location->id,
            'jumlah_ac' => $request->jumlah_ac,
            'catatan' => "Instalasi {$request->jumlah_ac} unit AC {$request->jenis_ac} {$request->kapasitas_ac}. " . ($request->catatan ?? ''),
        ];

        // Tambahkan tanggal_berkunjung jika ada
        if ($request->has('tanggal_berkunjung')) {
            $serviceData['tanggal_berkunjung'] = $request->tanggal_berkunjung;
        }

        $service = Service::create($serviceData);

        return $this->ok($service, 'Request instalasi AC berhasil dikirim', 201);
    }

    // Lihat daftar servis client
    public function index(Request $request)
    {
        $clientId = $request->user()->id;

        $services = Service::where('client_id', $clientId)
            ->with([
                'lokasi:id,client_id,name,address,latitude,longitude,gmaps_url',
                'items' => function ($q) {
                    $q->with([
                        'acUnit:id,location_id,name,brand,type,capacity,last_service',
                        'technician:id,name,phone'
                    ])->orderBy('id');
                },
            ])
            ->orderByDesc('created_at')
            ->get();

        // optional: bikin payload ringkas biar gampang dipakai flutter
        $result = $services->map(function ($s) {
            $acCount = $s->items->count(); // sumber utama jumlah AC
            $uniqueTech = $s->items->pluck('technician')->filter()->unique('id')->values();

            return [
                'id' => $s->id,
                'jenis' => $s->jenis,
                'status' => $s->status,
                'tanggal_berkunjung' => $s->tanggal_berkunjung,
                'created_at' => $s->created_at,
                'lokasi' => $s->lokasi,
                'jumlah_ac' => $acCount > 0 ? $acCount : (int) ($s->jumlah_ac ?? 0), // fallback legacy
                'catatan' => $s->catatan,
                'items' => $s->items, // sudah include acUnit & technician
                'teknisi' => $uniqueTech, // list teknisi yang terlibat (per item)
            ];
        });

        return $this->ok($result);
    }


    public function show($id, Request $request)
    {
        $clientId = $request->user()->id;

        // Ambil service dengan relasi lengkap dan pastikan milik client
        $service = Service::with([
            'lokasi',
            'ac' => function ($query) {
                $query->withTrashed(); // Jika AC sudah dihapus, tetap tampilkan
            },
            'teknisi',
            'laporanTeknisi' => function ($query) {
                $query->orderByDesc('created_at');
            },
            'pembayaran',
            'acUnitsDetail' => function ($query) {
                $query->withTrashed(); // Jika AC sudah dihapus, tetap tampilkan
            }
        ])
            ->where('id', $id)
            ->where('client_id', $clientId)
            ->first();

        if (!$service) {
            return $this->error('Servis tidak ditemukan atau tidak memiliki akses', 404);
        }

        // Format data respons
        $response = [
            'id' => $service->id,
            'jenis' => $service->jenis,
            'status' => $service->status,
            'status_label' => $this->getStatusLabel($service->status),
            'created_at' => $service->created_at,
            'updated_at' => $service->updated_at,
            'catatan' => $service->catatan,
            'keluhan_client' => $service->keluhan_client,
            'foto_keluhan' => $service->foto_keluhan,
            'lokasi' => $service->lokasi,
            'ac' => $service->ac,
            'teknisi' => $service->teknisi,
            'laporan_teknisi' => $service->laporanTeknisi,
            'pembayaran' => $service->pembayaran,
        ];

        // Tambahkan data spesifik berdasarkan jenis servis
        switch ($service->jenis) {
            case 'cuci':
                $response['ac_units'] = $service->acUnitsDetail;
                $response['jumlah_ac'] = $service->jumlah_ac;
                break;

            case 'perbaikan':
                $response['ac_unit'] = $service->ac;
                $response['foto_keluhan'] = $this->getFullPhotoUrls($service->foto_keluhan);
                break;

            case 'instalasi':
                $response['jumlah_ac'] = $service->jumlah_ac;
                break;
        }

        // Tambahkan URL lengkap untuk foto jika ada
        if (!empty($service->foto_keluhan)) {
            $response['foto_keluhan_urls'] = $this->getFullPhotoUrls($service->foto_keluhan);
        }

        // Tambahkan data laporan teknisi jika ada
        if ($service->laporanTeknisi) {
            foreach ($service->laporanTeknisi as $laporan) {
                if (!empty($laporan->foto_sebelum)) {
                    $laporan->foto_sebelum_urls = $this->getFullPhotoUrls($laporan->foto_sebelum);
                }
                if (!empty($laporan->foto_sesudah)) {
                    $laporan->foto_sesudah_urls = $this->getFullPhotoUrls($laporan->foto_sesudah);
                }
            }
        }

        return $this->ok($response);
    }
}
