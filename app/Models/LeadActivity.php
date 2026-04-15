<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'loggable_type',
        'loggable_id',
        'activity_type',
        'description',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected $appends = ['formatted_old_values', 'formatted_new_values'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loggable()
    {
        return $this->morphTo();
    }

    public function getFormattedOldValuesAttribute()
    {
        return $this->formatValues($this->old_values);
    }

    public function getFormattedNewValuesAttribute()
    {
        return $this->formatValues($this->new_values);
    }

    protected function formatValues($values)
    {
        if (empty($values)) return $values;

        $formatted = [];
        foreach ($values as $key => $value) {
            $formatted[$key] = $this->resolveLabel($key, $value);
        }
        return $formatted;
    }

    protected function resolveLabel($field, $value)
    {
        if (is_null($value)) return 'N/A';

        // Date/Time fields
        if (str_contains($field, 'date') || str_contains($field, 'time')) {
            try {
                return \Carbon\Carbon::parse($value)->format('d-M-Y h:i A');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // Relation IDs
        if (str_ends_with($field, '_id') || in_array($field, ['assigned_to', 'created_by', 'added_by', 'user_id'])) {
            $modelClass = null;
            if ($field === 'lead_status_id') $modelClass = \App\Models\LeadStatus::class;
            elseif ($field === 'category_id') $modelClass = \App\Models\Category::class;
            elseif ($field === 'property_type_id') $modelClass = \App\Models\PropertyType::class;
            elseif ($field === 'lead_source_id') $modelClass = \App\Models\LeadSource::class;
            elseif ($field === 'property_id') $modelClass = \App\Models\Property::class;
            elseif ($field === 'lead_id') $modelClass = \App\Models\Lead::class;
            elseif (in_array($field, ['assigned_to', 'created_by', 'user_id', 'added_by'])) $modelClass = \App\Models\User::class;

            if ($modelClass) {
                $related = $modelClass::find($value);
                return $related ? ($related->name ?? $related->title ?? $value) : $value;
            }
        }

        return $value;
    }
}
