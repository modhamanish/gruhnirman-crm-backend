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
            $fields = implode(', ', array_keys($changes));
            return "{$modelName} updated (Fields: {$fields})";
        }

        return "{$modelName} has been {$type}";
    }
}
