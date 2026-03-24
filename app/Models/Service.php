<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis',
        'status',
        'complaint_id',
        'client_id',
        'location_id',
        'ac_unit_id',
        'technician_id',
        'ac_units',
        'jumlah_ac',
        'tindakan',
        'diagnosa',
        'catatan',
        'keluhan_client',
        'foto_keluhan',
        'foto_sebelum',
        'foto_pengerjaan',
        'foto_sesudah',
        'foto_suku_cadang',
        'tanggal_berkunjung',
        'tanggal_ditugaskan',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_dikonfirmasi_owner',
        'tanggal_dikonfirmasi_client',
        'biaya_servis',
        'biaya_suku_cadang',
        'total_biaya',
        'no_invoice'
    ];

    protected $casts = [
        'ac_units' => 'array',
        'tindakan' => 'array',
        'foto_keluhan' => 'array',
        'foto_sebelum' => 'array',
        'foto_pengerjaan' => 'array',
        'foto_sesudah' => 'array',
        'foto_suku_cadang' => 'array',
        'tanggal_berkunjung' => 'datetime',
        'tanggal_ditugaskan' => 'datetime',
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'tanggal_dikonfirmasi_owner' => 'datetime',
        'tanggal_dikonfirmasi_client' => 'datetime',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function ac()
    {
        return $this->belongsTo(AcUnit::class, 'ac_unit_id');
    }

    public function acUnits()
    {
        return AcUnit::whereIn('id', $this->ac_units ?? [])->get();
    }

    public function acUnitsRelation()
    {
        return $this->belongsToMany(AcUnit::class, 'service_items', 'service_id', 'ac_unit_id');
    }

    public function teknisi()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    // Scope queries
    public function scopePendingOwnerConfirmation($query)
    {
        return $query->where('status', 'menunggu_konfirmasi_owner');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['ditugaskan', 'dalam_perjalanan', 'dalam_pengerjaan']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'selesai');
    }

    // Custom attributes
    public function getTotalBiayaAttribute()
    {
        return $this->total_biaya ?? ((float)$this->biaya_servis + (float)$this->biaya_suku_cadang);
    }

    public function getIsCuciAttribute()
    {
        return $this->jenis === 'cuci';
    }

    public function getIsPerbaikanAttribute()
    {
        return $this->jenis === 'perbaikan';
    }

    public function getIsInstalasiAttribute()
    {
        return $this->jenis === 'instalasi';
    }

    public function technicians()
    {
        return $this->belongsToMany(
            User::class,
            'service_technicians',
            'service_id',
            'technician_id'
        )->withPivot(['is_lead', 'assigned_at'])
            ->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany(\App\Models\ServiceItem::class);
    }
}
