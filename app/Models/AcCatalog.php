<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcCatalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'type_id',
        'capacity_id',
        'series',
        'is_active',
    ];

    public function brand()
    {
        return $this->belongsTo(AcBrand::class, 'brand_id');
    }

    public function type()
    {
        return $this->belongsTo(AcType::class, 'type_id');
    }

    public function capacity()
    {
        return $this->belongsTo(AcCapacity::class, 'capacity_id');
    }
}