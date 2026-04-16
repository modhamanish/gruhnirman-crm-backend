<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\NotifiesAction;

class Lead extends Model
{
    use HasFactory, LogsActivity, NotifiesAction;

    protected $fillable = [
        'name',
        'cast',
        'contact_number',
        'inquiry_for',
        'interested_area',
        'area_latitude',
        'area_longitude',
        'min_budget',
        'max_budget',
        'category_id',
        'property_type_id',
        'lead_status_id',
        'lead_source_id',
        'assigned_to',
        'created_by',
        'type',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function siteVisits()
    {
        return $this->hasMany(SiteVisit::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }
}
