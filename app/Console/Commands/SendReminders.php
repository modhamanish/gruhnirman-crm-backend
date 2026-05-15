<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FollowUp;
use App\Models\SiteVisit;
use App\Models\AppNotification;
use Carbon\Carbon;

class SendReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 15-minute reminders for Follow-ups and Site Visits';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $reminderTimeStart = Carbon::now()->addMinutes(15)->startOfMinute();
        $reminderTimeEnd = Carbon::now()->addMinutes(15)->endOfMinute();

        $this->info("Checking for reminders between {$reminderTimeStart} and {$reminderTimeEnd}");

        // 1. Follow Ups
        $followUps = FollowUp::whereBetween('next_follow_up_date_time', [$reminderTimeStart, $reminderTimeEnd])
            ->whereNotNull('user_id')
            ->get();

        foreach ($followUps as $followUp) {
            $this->sendReminder($followUp, 'Follow-up', 'next_follow_up_date_time');
        }

        // 2. Site Visits
        $siteVisits = SiteVisit::whereBetween('visit_date', [$reminderTimeStart, $reminderTimeEnd])
            ->whereNotNull('user_id')
            ->get();

        foreach ($siteVisits as $siteVisit) {
            $this->sendReminder($siteVisit, 'Site Visit', 'visit_date');
        }

        return 0;
    }

    protected function sendReminder($model, $type, $dateField)
    {
        // Check if reminder already sent to avoid duplicates (just in case)
        $exists = AppNotification::where('notifiable_type', get_class($model))
            ->where('notifiable_id', $model->id)
            ->where('title', 'LIKE', '%Reminder%')
            ->exists();

        if ($exists) {
            $this->info("Reminder already exists for {$type} ID: {$model->id}");
            return;
        }

        $time = Carbon::parse($model->$dateField)->format('h:i A');
        $title = "Upcoming {$type} Reminder";
        $message = "You have a {$type} scheduled at {$time}.";

        AppNotification::create([
            'user_id' => $model->user_id,
            'title' => $title,
            'message' => $message,
            'notifiable_type' => get_class($model),
            'notifiable_id' => $model->id,
            'is_read' => false,
        ]);

        $this->info("Reminder sent for {$type} ID: {$model->id} to User ID: {$model->user_id}");
    }
}
