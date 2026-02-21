<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'builder_id',
        'category_id',
        'property_type_id',
        'name',
        'sq_feet',
        'starting_price',
        'ending_price',
        'image',
        'address',
        'latitude',
        'longitude',
        'youtube_link',
        'brochure',
        'additional_note',
        'status',
    ];

    protected $appends = ['image_url', 'brochure_url'];

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

    public function getImageUrlAttribute()
    {
        if (file_exists(public_path('uploads/properties/' . $this->image)) && !empty($this->image)) {
            return asset('uploads/properties/' . $this->image);
        }
        return '';
    }

    public function getBrochureUrlAttribute()
    {
        if (file_exists(public_path('uploads/brochures/' . $this->brochure)) && !empty($this->brochure)) {
            return asset('uploads/brochures/' . $this->brochure);
        }
        return '';
    }
}
