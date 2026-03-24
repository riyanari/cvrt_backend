<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Service::query()->with(['complaint', 'location', 'acUnit', 'technician']);

        if ($request->filled('technician_id')) $q->where('technician_id', $request->technician_id);
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('jenis')) $q->where('jenis', $request->jenis);

        return $q->orderByDesc('id')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'complaint_id' => 'required|exists:complaints,id',
            'location_id' => 'required|exists:locations,id',
            'ac_unit_id' => 'required|exists:ac_units,id',
            'technician_id' => 'required|exists:users,id',
            'jenis' => 'required|string', // cuciAc/perbaikanAc/instalasi
            'status' => 'nullable|string',
            'assigned_at' => 'required|date',
        ]);

        $service = Service::create($data);
        return response()->json($service->load(['complaint', 'location', 'acUnit', 'technician']), 201);
    }

    public function show(Service $service)
    {
        return $service->load(['complaint', 'location', 'acUnit', 'technician']);
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'jenis' => 'sometimes|string',
            'status' => 'sometimes|string',
            'tindakan' => 'nullable|array',
            'tindakan.*' => 'string',
            'diagnosa' => 'nullable|string',
            'catatan' => 'nullable|string',
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
            'confirmed_at' => 'nullable|date',
            'biaya_servis' => 'nullable|numeric',
            'biaya_suku_cadang' => 'nullable|numeric',
            'no_invoice' => 'nullable|string',
        ]);

        $service->update($data);
        return $service->refresh()->load(['complaint', 'location', 'acUnit', 'technician']);
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
