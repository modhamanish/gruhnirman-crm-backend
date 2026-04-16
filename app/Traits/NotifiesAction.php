<?php

namespace App\Traits;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait NotifiesAction
{
    public static function bootNotifiesAction()
    {
        static::created(function ($model) {
            $model->sendNotifications('created');
        });

        static::updated(function ($model) {
            $model->sendNotifications('updated');
        });

        static::deleted(function ($model) {
            $model->sendNotifications('deleted');
        });
    }

    public function sendNotifications($action)
    {
        $causer = Auth::user();
        $causerName = $causer ? $causer->name : 'System';

        $modelName = class_basename($this);
        $title = "{$modelName} {$action}";
        $message = "{$causerName} has {$action} a {$modelName}.";

        // 1. Get all Admins and Super Admins
        $admins = User::role(['Admin', 'Super Admin'])->get();

        // 2. Get Assigned User(s)
        $assignedUsers = $this->getUsersToNotify();

        $recipientIds = $admins->pluck('id')->merge($assignedUsers)->unique();

        foreach ($recipientIds as $userId) {
            // Skip the person who did the action
            if ($causer && $userId == $causer->id) continue;

            AppNotification::create([
                'user_id' => $userId,
                'causer_id' => $causer ? $causer->id : null,
                'title' => $title,
                'message' => $message,
                'notifiable_type' => get_class($this),
                'notifiable_id' => $this->id,
            ]);
        }
    }

    protected function getUsersToNotify()
    {
        $userIds = [];

        // For Lead
        if (isset($this->assigned_to)) {
            $userIds[] = $this->assigned_to;
        }

        // For FollowUp or SiteVisit
        if (isset($this->user_id)) {
            $userIds[] = $this->user_id;
        }

        return $userIds;
    }
}
