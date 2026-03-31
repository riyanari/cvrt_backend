<?php

namespace App\Http\Controllers\Api\Teknisi;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcUnit;
use App\Models\Service;
use App\Models\ServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeknisiServisController extends BaseApiController
{
    private const STATUS_DITUGASKAN = 'ditugaskan';
    private const STATUS_DIKERJAKAN = 'dikerjakan';
    private const STATUS_SELESAI = 'selesai';

    // Daftar tugas teknisi (sesuai status owner: ditugaskan, dikerjakan)
    public function tugasSaya(Request $request)
    {
        $teknisiId = (int) $request->user()->id;

        $services = Service::query()
            ->whereIn('status', [
                self::STATUS_DITUGASKAN,
                self::STATUS_DIKERJAKAN,
                self::STATUS_SELESAI,
            ])
            ->whereHas('items', function ($q) use ($teknisiId) {
                $q->where('technician_id', $teknisiId);
            })
            ->with([
                'client:id,name,phone',
                'lokasi:id,name,address,latitude,longitude,gmaps_url',

                'items' => function ($q) use ($teknisiId) {
                    $q->where('technician_id', $teknisiId)
                        ->with([
                            'acUnit:id,room_id,name,brand,type,capacity,last_service',
                            'acUnit.room:id,location_id,floor_id,name,code',
                            'acUnit.room.floor:id,name,number',
                            'acUnit.room.location:id,name,address',
                            'technician:id,name,phone',
                        ])
                        ->orderBy('id');
                },
            ])
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('tanggal_ditugaskan')
            ->get();

        $services->each(function ($service) {
            $service->items->transform(function ($item) {
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
        });

        return $this->ok($services);
    }

    public function mulaiPengerjaan(Request $request, $serviceId)
    {
        $teknisiId = (int) $request->user()->id;

        $request->validate([
            'service_item_id' => ['required', 'integer'],
        ]);

        return DB::transaction(function () use ($request, $serviceId, $teknisiId) {
            $service = $this->startItemForTechnician(
                (int) $serviceId,
                (int) $request->service_item_id,
                $teknisiId
            );

            return $this->ok(
                $this->loadServiceDetail($service),
                'Item mulai dikerjakan'
            );
        });
    }

    public function updatePengerjaan(Request $request, $serviceId)
    {
        $teknisiId = (int) $request->user()->id;

        $request->validate([
            'service_item_id' => ['required', 'integer'],

            // text
            'diagnosa' => ['nullable', 'string', 'max:5000'],
            'tindakan' => ['nullable', 'string', 'max:5000'], // kalau tindakan json, ganti ke array

            // foto arrays (opsional)
            'foto_sebelum' => ['nullable', 'array', 'min:1', 'max:10'],
            'foto_sebelum.*' => ['image', 'max:5120'],

            'foto_pengerjaan' => ['nullable', 'array', 'min:1', 'max:10'],
            'foto_pengerjaan.*' => ['image', 'max:5120'],

            'foto_sesudah' => ['nullable', 'array', 'min:1', 'max:10'],
            'foto_sesudah.*' => ['image', 'max:5120'],
        ]);

        return DB::transaction(function () use ($request, $serviceId, $teknisiId) {

            $service = Service::query()
                ->where('id', (int) $serviceId)
                ->whereIn('status', [self::STATUS_DITUGASKAN, self::STATUS_DIKERJAKAN]) // masih berjalan
                ->lockForUpdate()
                ->firstOrFail();

            $item = ServiceItem::query()
                ->where('id', (int) $request->service_item_id)
                ->where('service_id', $service->id)
                ->where('technician_id', $teknisiId)
                ->where('status', self::STATUS_DIKERJAKAN) // update progres hanya saat dikerjakan
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();

            // helper buat merge array json
            $mergeJsonArray = function ($current, array $new) {
                if (!is_array($current)) $current = (array) $current;
                return array_values(array_merge($current, $new));
            };

            // simpan foto (kalau ada)
            $update = [];

            if ($request->filled('diagnosa')) $update['diagnosa'] = $request->diagnosa;
            if ($request->filled('tindakan')) $update['tindakan'] = $request->tindakan;

            if ($request->hasFile('foto_sebelum')) {
                $paths = [];
                foreach ($request->file('foto_sebelum') as $file) {
                    $paths[] = $file->store("services/{$item->service_id}/items/{$item->id}/sebelum", 'public');
                }
                $update['foto_sebelum'] = $mergeJsonArray($item->foto_sebelum, $paths);
            }

            if ($request->hasFile('foto_pengerjaan')) {
                $paths = [];
                foreach ($request->file('foto_pengerjaan') as $file) {
                    $paths[] = $file->store("services/{$item->service_id}/items/{$item->id}/pengerjaan", 'public');
                }
                $update['foto_pengerjaan'] = $mergeJsonArray($item->foto_pengerjaan, $paths);
            }

            if ($request->hasFile('foto_sesudah')) {
                $paths = [];
                foreach ($request->file('foto_sesudah') as $file) {
                    $paths[] = $file->store("services/{$item->service_id}/items/{$item->id}/sesudah", 'public');
                }
                $update['foto_sesudah'] = $mergeJsonArray($item->foto_sesudah, $paths);
            }

            if (!empty($update)) {
                $item->update($update);
            }

            // pastikan tanggal_mulai ada (kalau sebelumnya lupa ke-set)
            if (!$item->tanggal_mulai) {
                $item->update(['tanggal_mulai' => $now]);
            }

            $this->syncServiceStatusFromItems($service, $now);

            return $this->ok(
                $this->loadServiceDetail($service->fresh()),
                'Progress pengerjaan tersimpan'
            );
        });
    }

    public function mulaiItem(Request $request, ServiceItem $item)
    {
        $teknisiId = (int) $request->user()->id;

        return DB::transaction(function () use ($item, $teknisiId) {
            $service = $this->startItemForTechnician(
                (int) $item->service_id,
                (int) $item->id,
                $teknisiId
            );

            return $this->ok(
                $this->loadServiceDetail($service),
                'Item mulai dikerjakan'
            );
        });
    }

    // =========================
    // 2) UPDATE PROGRESS PER ITEM (multipart)
    // - foto_sebelum
    // - diagnosa
    // - tindakan
    // - foto_pengerjaan
    // - foto_sesudah
    // =========================
    public function uploadFotoItem(Request $request, ServiceItem $item)
    {
        $teknisiId = (int) $request->user()->id;

        $request->validate([
            // text
            'diagnosa' => ['nullable', 'string', 'max:5000'],
            'tindakan' => ['nullable', 'string', 'max:5000'], // kalau tindakan array/json, ubah sesuai kebutuhan

            // foto opsional (partial update)
            'foto_sebelum' => ['nullable', 'array', 'min:1', 'max:10'],
            'foto_sebelum.*' => ['image', 'max:5120'],

            'foto_pengerjaan' => ['nullable', 'array', 'min:1', 'max:10'],
            'foto_pengerjaan.*' => ['image', 'max:5120'],

            'foto_sesudah' => ['nullable', 'array', 'min:1', 'max:10'],
            'foto_sesudah.*' => ['image', 'max:5120'],
        ]);

        return DB::transaction(function () use ($request, $item, $teknisiId) {

            $item = ServiceItem::query()
                ->where('id', $item->id)
                ->where('technician_id', $teknisiId)
                ->where('status', self::STATUS_DIKERJAKAN) // hanya saat dikerjakan
                ->lockForUpdate()
                ->firstOrFail();

            $service = Service::query()
                ->where('id', $item->service_id)
                ->whereIn('status', [self::STATUS_DITUGASKAN, self::STATUS_DIKERJAKAN])
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();

            $mergeJsonArray = function ($current, array $new) {
                if (!is_array($current)) $current = (array) $current;
                return array_values(array_merge($current, $new));
            };

            $update = [];

            if ($request->filled('diagnosa')) $update['diagnosa'] = $request->diagnosa;
            if ($request->filled('tindakan')) $update['tindakan'] = $request->tindakan;

            // foto_sebelum
            if ($request->hasFile('foto_sebelum')) {
                $paths = [];
                foreach ($request->file('foto_sebelum') as $file) {
                    $paths[] = $file->store("services/{$item->service_id}/items/{$item->id}/sebelum", 'public');
                }
                $update['foto_sebelum'] = $mergeJsonArray($item->foto_sebelum, $paths);
            }

            // foto_pengerjaan
            if ($request->hasFile('foto_pengerjaan')) {
                $paths = [];
                foreach ($request->file('foto_pengerjaan') as $file) {
                    $paths[] = $file->store("services/{$item->service_id}/items/{$item->id}/pengerjaan", 'public');
                }
                $update['foto_pengerjaan'] = $mergeJsonArray($item->foto_pengerjaan, $paths);
            }

            // foto_sesudah
            if ($request->hasFile('foto_sesudah')) {
                $paths = [];
                foreach ($request->file('foto_sesudah') as $file) {
                    $paths[] = $file->store("services/{$item->service_id}/items/{$item->id}/sesudah", 'public');
                }
                $update['foto_sesudah'] = $mergeJsonArray($item->foto_sesudah, $paths);
            }

            // pastikan tanggal_mulai ada
            if (!$item->tanggal_mulai) {
                $update['tanggal_mulai'] = $now;
            }

            if (!empty($update)) {
                $item->update($update);
            }

            $this->syncServiceStatusFromItems($service, $now);

            return $this->ok(
                $this->loadServiceDetail($service->fresh()),
                'Progress item tersimpan'
            );
        });
    }

    // =========================
    // 3) SELESAI PER ITEM (dikerjakan -> selesai)
    // =========================
    public function selesaikanItem(Request $request, ServiceItem $item)
    {
        $teknisiId = (int) $request->user()->id;

        $request->validate([
            // optional final update saat klik selesai
            'diagnosa' => ['nullable', 'string', 'max:5000'],
            'tindakan' => ['nullable', 'string', 'max:5000'],

            // optional upload foto_sesudah di tahap akhir
            'foto_sesudah' => ['nullable', 'array', 'min:1', 'max:10'],
            'foto_sesudah.*' => ['image', 'max:5120'],
        ]);

        return DB::transaction(function () use ($request, $item, $teknisiId) {

            $item = ServiceItem::query()
                ->where('id', $item->id)
                ->where('technician_id', $teknisiId)
                ->where('status', self::STATUS_DIKERJAKAN)
                ->lockForUpdate()
                ->firstOrFail();

            $service = Service::query()
                ->where('id', $item->service_id)
                ->whereIn('status', [self::STATUS_DITUGASKAN, self::STATUS_DIKERJAKAN])
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();

            // enforce minimal (silakan kamu longgarkan kalau mau)
            $fotoSebelum = is_array($item->foto_sebelum) ? $item->foto_sebelum : (array) $item->foto_sebelum;
            if (count($fotoSebelum) < 1) {
                return $this->badRequest('Foto sebelum wajib ada sebelum menyelesaikan.');
            }

            $update = [];

            if ($request->filled('diagnosa')) $update['diagnosa'] = $request->diagnosa;
            if ($request->filled('tindakan')) $update['tindakan'] = $request->tindakan;

            // upload foto_sesudah jika ada
            if ($request->hasFile('foto_sesudah')) {
                $paths = [];
                foreach ($request->file('foto_sesudah') as $file) {
                    $paths[] = $file->store("services/{$item->service_id}/items/{$item->id}/sesudah", 'public');
                }
                $current = $item->foto_sesudah;
                if (!is_array($current)) $current = (array) $current;
                $update['foto_sesudah'] = array_values(array_merge($current, $paths));
            }

            // enforce foto_sesudah minimal 1
            $fotoSesudahCandidate = $update['foto_sesudah']
                ?? (is_array($item->foto_sesudah) ? $item->foto_sesudah : (array) $item->foto_sesudah);

            if (count($fotoSesudahCandidate) < 1) {
                return $this->badRequest('Foto sesudah wajib ada sebelum menyelesaikan.');
            }

            $update['status'] = self::STATUS_SELESAI;
            $update['tanggal_selesai'] = $item->tanggal_selesai ?? $now;
            $update['tanggal_mulai'] = $item->tanggal_mulai ?? $now;

            $item->update($update);

            if ($service->jenis === 'cuci' && $item->ac_unit_id) {
                AcUnit::where('id', $item->ac_unit_id)->update(['last_service' => $now]);
            }

            $this->syncServiceStatusFromItems($service, $now);

            return $this->ok(
                $this->loadServiceDetail($service->fresh()),
                'Item selesai'
            );
        });
    }

    // =========================
    // OPTIONAL: SELESAI SERVICE (validasi semua item selesai)
    // =========================
    public function selesaikanPengerjaan(Request $request, Service $service)
    {
        $teknisiId = (int) $request->user()->id;

        return DB::transaction(function () use ($service, $teknisiId) {

            $service = Service::query()
                ->where('id', $service->id)
                ->whereIn('status', [self::STATUS_DITUGASKAN, self::STATUS_DIKERJAKAN])
                ->lockForUpdate()
                ->firstOrFail();

            // pastikan service ini memang punya item milik teknisi ini (optional check)
            $hasMine = ServiceItem::query()
                ->where('service_id', $service->id)
                ->where('technician_id', $teknisiId)
                ->exists();

            if (!$hasMine) {
                return $this->badRequest('Anda tidak memiliki item pada servis ini.');
            }

            $now = now();

            // service bisa "selesai" hanya kalau semua item selesai
            $total = ServiceItem::query()->where('service_id', $service->id)->count();
            $selesai = ServiceItem::query()->where('service_id', $service->id)->where('status', self::STATUS_SELESAI)->count();

            if ($total < 1 || $selesai !== $total) {
                return $this->badRequest('Belum semua item selesai.');
            }

            $service->update([
                'status' => self::STATUS_SELESAI,
                'tanggal_selesai' => $service->tanggal_selesai ?? $now,
            ]);

            return $this->ok(
                $this->loadServiceDetail($service->fresh()),
                'Servis selesai'
            );
        });
    }

    // =========================
    // HELPER: sync status service dari semua item
    // =========================
    private function syncServiceStatusFromItems(Service $service, $now): void
    {
        $counts = ServiceItem::query()
            ->where('service_id', $service->id)
            ->selectRaw("
                SUM(status = ?) AS cnt_dikerjakan,
                SUM(status = ?) AS cnt_selesai,
                COUNT(*) AS cnt_total
            ", [self::STATUS_DIKERJAKAN, self::STATUS_SELESAI])
            ->first();

        $cntDikerjakan = (int) ($counts->cnt_dikerjakan ?? 0);
        $cntSelesai = (int) ($counts->cnt_selesai ?? 0);
        $cntTotal = (int) ($counts->cnt_total ?? 0);

        if ($cntTotal > 0 && $cntSelesai === $cntTotal) {
            $service->update([
                'status' => self::STATUS_SELESAI,
                'tanggal_mulai' => $service->tanggal_mulai ?? $now,
                'tanggal_selesai' => $service->tanggal_selesai ?? $now,
            ]);
            return;
        }

        if ($cntDikerjakan > 0) {
            $service->update([
                'status' => self::STATUS_DIKERJAKAN,
                'tanggal_mulai' => $service->tanggal_mulai ?? $now,
                'tanggal_selesai' => null,
            ]);
            return;
        }

        if ($service->status !== self::STATUS_DITUGASKAN) {
            $service->update([
                'status' => self::STATUS_DITUGASKAN,
                'tanggal_mulai' => null,
                'tanggal_selesai' => null,
            ]);
        }
    }

    // Upload foto pengerjaan (boleh saat ditugaskan/dikerjakan)
    public function uploadFoto(Request $request, $itemId)
    {
        $teknisiId = $request->user()->id;

        $request->validate([
            'jenis_foto' => 'required|in:sebelum,pengerjaan,sesudah,suku_cadang',
            'foto' => 'required|array|min:1|max:10',
            'foto.*' => 'image|max:5120',
        ]);

        $item = ServiceItem::query()
            ->where('id', $itemId)
            ->where('technician_id', $teknisiId)
            ->whereIn('status', [self::STATUS_DITUGASKAN, self::STATUS_DIKERJAKAN])
            ->firstOrFail();

        $fotoPaths = [];
        foreach ($request->file('foto') as $file) {
            $path = $file->store(
                "services/{$item->service_id}/items/{$item->id}/{$request->jenis_foto}",
                'public'
            );
            $fotoPaths[] = $path;
        }

        $field = "foto_{$request->jenis_foto}";
        $current = $item->{$field};
        if (!is_array($current)) $current = (array) $current;

        $item->update([
            $field => array_values(array_merge($current, $fotoPaths)),
        ]);

        return $this->ok(
            $this->loadItemDetail($item->fresh()),
            'Foto berhasil diupload'
        );
    }

    private function loadServiceDetail(Service $service): Service
    {
        $service->load([
            'client:id,name,phone',
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
        ]);

        $service->items->transform(function ($item) {
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

        return $service;
    }

    private function loadItemDetail(ServiceItem $item): ServiceItem
    {
        $item->load([
            'acUnit:id,room_id,name,brand,type,capacity,last_service',
            'acUnit.room:id,location_id,floor_id,name,code',
            'acUnit.room.floor:id,name,number',
            'acUnit.room.location:id,name,address',
            'technician:id,name,phone',
        ]);

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
    }

    private function startItemForTechnician(int $serviceId, int $itemId, int $teknisiId): Service
    {
        $service = Service::query()
            ->where('id', $serviceId)
            ->whereIn('status', [self::STATUS_DITUGASKAN, self::STATUS_DIKERJAKAN])
            ->lockForUpdate()
            ->firstOrFail();

        $item = ServiceItem::query()
            ->where('id', $itemId)
            ->where('service_id', $service->id)
            ->where('technician_id', $teknisiId)
            ->where('status', self::STATUS_DITUGASKAN)
            ->lockForUpdate()
            ->firstOrFail();

        $now = now();

        $item->update([
            'status' => self::STATUS_DIKERJAKAN,
            'tanggal_mulai' => $item->tanggal_mulai ?? $now,
        ]);

        $this->syncServiceStatusFromItems($service, $now);

        return $service->fresh();
    }
}
