<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
        if (Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Admin') || Auth::user()->hasPermissionTo('lead-access-all')) {
            return Lead::where('lead_status_id', $this->id)->count();
        } else {
            return Lead::where('lead_status_id', $this->id)->where('created_by', Auth::user()->id)->orWhere('assigned_to', Auth::user()->id)->count();
        }
    }
}
