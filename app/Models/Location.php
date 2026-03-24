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

    public function users()
    {
        return $this->belongsToMany(User::class, 'location_user')
            ->withTimestamps();
    }

    public function floors()
    {
        return $this->hasMany(Floor::class);
    }

    public function acUnits()
    {
        return AcUnit::whereHas('room.floor', function ($q) {
            $q->where('location_id', $this->id);
        });
    }
}