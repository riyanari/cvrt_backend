<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;

class OwnerKeluhanController extends BaseApiController
{
    public function index(Request $request)
    {
        $q = Complaint::with(['client', 'lokasi', 'ac', 'assigned']);

        foreach (['status', 'priority', 'client_id'] as $f) {
            if ($request->filled($f)) $q->where($f, $request->$f);
        }

        return $this->ok($q->orderBy('id', 'desc')->get());
    }
}
