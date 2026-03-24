<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $q = Complaint::query()->with(['location', 'acUnit', 'assignee', 'service']);

        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('priority')) $q->where('priority', $request->priority);
        if ($request->filled('assigned_to')) $q->where('assigned_to', $request->assigned_to);

        return $q->orderByDesc('id')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'ac_unit_id' => 'required|exists:ac_units,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'submitted_at' => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $complaint = Complaint::create($data);
        return response()->json($complaint->load(['location', 'acUnit', 'assignee']), 201);
    }

    public function show(Complaint $complaint)
    {
        return $complaint->load(['location', 'acUnit', 'assignee', 'service']);
    }

    public function update(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'title' => 'sometimes|string',
            'description' => 'sometimes|string',
            'status' => 'sometimes|string',
            'priority' => 'sometimes|string',
            'completed_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'servicer_notes' => 'nullable|string',
        ]);

        $complaint->update($data);
        return $complaint->refresh()->load(['location', 'acUnit', 'assignee', 'service']);
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
