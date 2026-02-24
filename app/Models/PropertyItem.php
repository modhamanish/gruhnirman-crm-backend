<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'sq_feet',
        'price',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
