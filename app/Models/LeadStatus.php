<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_initial',
        'is_final',
        'status',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
    ];
}
