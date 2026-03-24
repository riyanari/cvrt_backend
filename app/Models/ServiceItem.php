<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'ac_unit_id',
        'technician_id',
        'assigned_at',
        'status',

        // ✅ per item timeline
        'tanggal_berkunjung',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_dikonfirmasi_owner',
        'tanggal_dikonfirmasi_client',

        // ✅ per item foto
        'foto_sebelum',
        'foto_pengerjaan',
        'foto_sesudah',
        'foto_suku_cadang',

        'diagnosa',
        'tindakan',
        'catatan'
    ];

    protected $casts = [
        'foto_sebelum' => 'array',
        'foto_pengerjaan' => 'array',
        'foto_sesudah' => 'array',
        'foto_suku_cadang' => 'array',

        'tanggal_berkunjung' => 'datetime',
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'tanggal_dikonfirmasi_owner' => 'datetime',
        'tanggal_dikonfirmasi_client' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function acUnit()
    {
        return $this->belongsTo(AcUnit::class, 'ac_unit_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
