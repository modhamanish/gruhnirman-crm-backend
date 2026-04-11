<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FollowUp;
use Carbon\Carbon;

class MarkLossFollowUps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-loss-follow-ups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark scheduled follow-ups as loss if the next follow-up date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = FollowUp::where('status', 'schedule')
            ->whereNotNull('next_follow_up_date_time')
            ->where('next_follow_up_date_time', '<', Carbon::now())
            ->update(['status' => 'loss']);

        $this->info("Updated {$count} follow-ups to loss status.");
    }
}
