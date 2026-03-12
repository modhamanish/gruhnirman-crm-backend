<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'icon',
        'is_initial',
        'status',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
    ];

    protected $appends = ['lead_count'];

    public function getLeadCountAttribute()
    {
        return Lead::where('lead_status_id', $this->id)->count();
    }
}
