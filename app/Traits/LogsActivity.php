<?php

namespace App\Traits;

use App\Models\LeadActivity;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::saved(function ($model) {
            $model->logActivity($model->wasRecentlyCreated ? 'created' : 'updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity($type)
    {
        $oldValues = null;
        $newValues = null;

        if ($type === 'updated') {
            $newValues = $this->getChanges();
            if (empty($newValues)) {
                return;
            }
            // Remove timestamps and id if they somehow got in
            unset($newValues['updated_at'], $newValues['created_at'], $newValues['id']);
            if (empty($newValues)) return;

            $oldValues = array_intersect_key($this->getOriginal(), $newValues);
        } elseif ($type === 'created') {
            $newValues = $this->getAttributes();
            unset($newValues['id'], $newValues['created_at'], $newValues['updated_at']);
        } elseif ($type === 'deleted') {
            $oldValues = $this->getAttributes();
        }

        // Determine Lead ID
        $leadId = null;
        if ($this instanceof \App\Models\Lead) {
            $leadId = $this->id;
        } elseif (isset($this->lead_id)) {
            $leadId = $this->lead_id;
        }

        if (!$leadId) {
            return;
        }

        LeadActivity::create([
            'lead_id' => $leadId,
            'user_id' => Auth::id(),
            'loggable_type' => get_class($this),
            'loggable_id' => $this->id,
            'activity_type' => $type,
            'description' => $this->getActivityDescription($type, $newValues),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    protected function getActivityDescription($type, $changes = [])
    {
        $modelName = class_basename($this);
        if ($modelName === 'Lead' && $this->type === 'inquiry') {
            $modelName = 'Inquiry';
        }

        if ($type === 'updated' && !empty($changes)) {
            $details = [];
            foreach ($changes as $key => $value) {
                if (in_array($key, ['updated_at', 'created_at', 'id'])) continue;

                $oldValue = $this->getOriginal($key);
                $label = ucwords(str_replace('_', ' ', $key));

                $oldLabel = $this->resolveValueLabel($key, $oldValue);
                $newLabel = $this->resolveValueLabel($key, $value);

                $details[] = "{$label} changed from '{$oldLabel}' to '{$newLabel}'";
            }
            if (!empty($details)) {
                return "{$modelName} updated: " . implode(', ', $details);
            }
        }

        return "{$modelName} has been {$type}";
    }

    protected function resolveValueLabel($field, $value)
    {
        if (is_null($value)) return 'N/A';
        if ($value === '') return 'empty';

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
