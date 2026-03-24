<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LocationUser extends Pivot
{
    protected $table = 'location_user';
    protected $fillable = ['location_id', 'user_id'];
}
