<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Builder extends Model
{
    protected $fillable = [
        'company_name',
        'name',
        'company_logo',
        'experience',
        'status',
        'contact_number',
        'email',
        'website',
        'office_address',
        'total_project_completed',
        'ongoing_projects',
    ];

    protected $appends = ['company_logo_url'];
    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function getCompanyLogoUrlAttribute()
    {
        return $this->company_logo ? asset('uploads/builders/' . $this->company_logo) : '';
    }
}
