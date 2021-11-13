<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

class clearOldActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear_old_activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete  records  from activities table older than 1 month';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sub1m = now()->subMonths(1);
        Activity::where([['created_at', '<',  $sub1m]])->delete();

        return Command::SUCCESS;
    }
}
