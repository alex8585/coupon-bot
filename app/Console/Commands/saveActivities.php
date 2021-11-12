<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Utils\UserSession;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

class saveActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save_activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    private $fields = [
        'coupon_id',
        'action',
        'shop_id',
        'category_id',
        'is_id',
        'page',
        'referrer',
    ];


    public function handle(UserSession  $userSession)
    {
        $insertData = [];
        $activities = $userSession->getCacheAactivities();
        foreach ($activities as $userKey => $activities) {
            foreach ($activities as $activity) {
                $activity['created_at'] = Carbon::parse($activity['created_at']);
                $activity['updated_at'] = Carbon::parse($activity['updated_at']);
                foreach ($this->fields as $k => $field) {
                    if (!isset($activity[$field])) {
                        $activity[$field] = null;
                    }
                }


                $insertData[] = $activity;
            }
        }
        dump($insertData);
        Activity::insert($insertData);
    }
}
