<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PropertyItem;

class SiteVisit extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'lead_id',
        'property_id',
        'user_id',
        'unit_type',
        'visit_date',
        'visited',
        'interest_status',
        'notes',
        'added_by'
    ];

    protected $casts = [
        'unit_type' => 'array',
    ];

    protected $appends = ['property_unit_details'];

    public function getPropertyUnitDetailsAttribute()
    {
        $unitIds = $this->unit_type;
        if (is_array($unitIds)) {
            return PropertyItem::whereIn('id', $unitIds)->get();
        }
        return [];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function executive()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
