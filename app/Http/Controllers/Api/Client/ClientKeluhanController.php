<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\AcUnit;
use App\Models\Complaint;
use App\Models\Location;
use Illuminate\Http\Request;

class ClientKeluhanController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $request->user()->id;

        $q = Complaint::where('client_id', $clientId)
            ->with(['lokasi', 'ac', 'assigned']);

        return $this->ok($q->orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $clientId = $request->user()->id;

        $request->validate([
            'location_id' => 'required|exists:lokasi,id',
            'ac_unit_id' => 'required|exists:ac_unit,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'priority' => 'nullable|in:rendah,sedang,tinggi,darurat',
        ]);

        // validasi kepemilikan
        Location::where('id', $request->location_id)->where('client_id', $clientId)->firstOrFail();
        AcUnit::where('id', $request->ac_unit_id)->where('location_id', $request->location_id)->firstOrFail();

        $keluhan = Complaint::create([
            'client_id' => $clientId,
            'location_id' => $request->lokasi_id,
            'ac_unit_id' => $request->ac_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? 'sedang',
            'status' => 'diajukan',
            'submitted_at' => now(),
            'foto_keluhan' => [],
        ]);

        return $this->ok($keluhan, 'Keluhan dibuat', 201);
    }
}
