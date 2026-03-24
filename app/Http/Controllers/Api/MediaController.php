<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function serviceItemPhoto(Request $request, ServiceItem $item)
{
    $user = $request->user();

    // Ambil relasi minimal
    $item->load('service:id,client_id'); // kalau teknisi ada di service, load juga

    $role = strtolower((string) ($user->role ?? ''));

    // OWNER boleh semua
    if ($role === 'owner') {
        return $this->serveItemPhoto($request, $item);
    }

    // CLIENT: harus pemilik service
    if ($role === 'client') {
        $isOwner = $item->service && (int)$item->service->client_id === (int)$user->id;
        if (!$isOwner) return response()->json(['message' => 'Forbidden'], 403);

        return $this->serveItemPhoto($request, $item);
    }

    // TEKNISI: harus yang ditugaskan
    if ($role === 'teknisi') {
        // ✅ PILIH SALAH SATU SESUAI STRUKTUR DB KAMU:

        // (A) kalau di service_items ada kolom technician_id / teknisi_id:
        $assignedId = (int) ($item->technician_id ?? $item->teknisi_id ?? 0);

        // (B) kalau teknisi ditugaskan di service (services.technician_id):
        // $item->loadMissing('service:id,client_id,technician_id');
        // $assignedId = (int) ($item->service->technician_id ?? 0);

        if ($assignedId !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $this->serveItemPhoto($request, $item);
    }

    return response()->json(['message' => 'Forbidden'], 403);
}

/**
 * Pisahkan logic ambil foto biar controller rapi
 */
private function serveItemPhoto(Request $request, ServiceItem $item)
{
    $type = strtolower($request->query('type', 'sebelum'));
    $i = (int) $request->query('i', 0);

    $fieldMap = [
        'sebelum' => 'foto_sebelum',
        'pengerjaan' => 'foto_pengerjaan',
        'sesudah' => 'foto_sesudah',
        'suku_cadang' => 'foto_suku_cadang',
    ];

    $field = $fieldMap[$type] ?? 'foto_sebelum';
    $value = $item->{$field};

    $list = [];
    if (is_array($value)) {
        $list = $value;
    } elseif (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $list = $decoded;
        } else {
            $list = [$value];
        }
    }

    $path = $list[$i] ?? null;
    if (!$path) return response()->json(['message' => 'Not found'], 404);

    $path = ltrim($path, '/');
    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'File not exists', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path) ?? 'image/jpeg';

    return response()->stream(function () use ($path) {
        $stream = Storage::disk('public')->readStream($path);
        fpassthru($stream);
        if (is_resource($stream)) fclose($stream);
    }, 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'private, max-age=86400',
    ]);
}

}
