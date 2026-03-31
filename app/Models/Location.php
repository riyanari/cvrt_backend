<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'place_id',
        'gmaps_url',
        'jumlah_ac',
        'last_service',
    ];

    protected $casts = [
        'last_service' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $hidden = [
        'pivot',
        'client_id',
    ];

    protected $appends = [
        'ac_count',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'location_user')
            ->withTimestamps();
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function acUnits()
    {
        return AcUnit::whereHas('room', function ($q) {
            $q->where('location_id', $this->id);
        });
    }

    public function getAcCountAttribute()
    {
        return \App\Models\AcUnit::whereHas('room', function ($q) {
            $q->where('location_id', $this->id);
        })->count();
    }
}