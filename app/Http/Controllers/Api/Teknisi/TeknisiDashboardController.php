<?php

namespace App\Http\Controllers\Api\Teknisi;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class TeknisiDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teknisiId = $request->user()->id;

        $list = Service::where('technician_id', $teknisiId)
            ->with(['lokasi', 'ac', 'client'])
            ->orderBy('id', 'desc')
            ->get();

        $total = $list->count();
        $berjalan = $list->whereNotIn('status', ['selesai', 'ditolak'])->count();
        $menungguKonfirmasi = $list->where('status', 'menungguKonfirmasi')->count();
        $selesai = $list->where('status', 'selesai')->count();

        return $this->ok([
            'status' => [
                'total' => $total,
                'berjalan' => $berjalan,
                'menunggu_konfirmasi' => $menungguKonfirmasi,
                'selesai' => $selesai,
            ],
            'servis' => $list,
        ]);
    }
}
