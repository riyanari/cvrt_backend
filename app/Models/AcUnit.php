<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcUnit extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'name', 'brand', 'type', 'capacity', 'last_service'];

    protected $casts = [
        'last_service' => 'datetime'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    
    // use HasFactory;

    // protected $table = 'ac_units';
    // protected $fillable = ['location_id', 'name', 'lantai', 'brand', 'type', 'capacity', 'last_service'];

    // protected $casts = [
    //     'last_service' => 'datetime'
    // ];

    // public function location()
    // {
    //     return $this->belongsTo(Location::class, 'location_id');
    // }
}
