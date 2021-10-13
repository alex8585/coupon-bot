<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

//use Telegram\Bot\Api;

class createHook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create_hook';

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
    public function handle()
    {

        //$token = '2071529025:AAHjnRSkieqjpxHZnF30lfIpjkPnG-JzdfQ';
        //$telegram = new Api($token);


        //        $response = $telegram->setWebhook(['url' => 'https://176.119.147.16/{$token}/webhook']);
        //      dd($response);
        //    return 0;
    }
}
