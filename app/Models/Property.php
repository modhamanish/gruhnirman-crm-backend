<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PropertyItem;
use App\Traits\NotifiesAction;

class Property extends Model
{
    use NotifiesAction;
    protected $fillable = [
        'builder_id',
        'category_id',
        'property_type_id',
        'name',
        'starting_price',
        'ending_price',
        'image',
        'image_url',
        'address',
        'latitude',
        'longitude',
        'youtube_link',
        'brochure',
        'brochure_url',
        'additional_note',
        'status',
    ];

    protected $appends = ['image_display_url', 'brochure_display_url'];

    public function items()
    {
        return $this->hasMany(PropertyItem::class);
    }

    public function builder()
    {
        return $this->belongsTo(Builder::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function getImageDisplayUrlAttribute()
    {
        if (!empty($this->image) && file_exists(public_path('uploads/properties/' . $this->image))) {
            return asset('uploads/properties/' . $this->image);
        }
        return $this->image_url ?? '';
    }

    public function getBrochureDisplayUrlAttribute()
    {
        if (!empty($this->brochure) && file_exists(public_path('uploads/brochures/' . $this->brochure))) {
            return asset('uploads/brochures/' . $this->brochure);
        }
        return $this->brochure_url ?? '';
    }

    public function siteVisits()
    {
        return $this->hasMany(SiteVisit::class);
    }
}
