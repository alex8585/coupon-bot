<?php

namespace App\Console\Commands;

//use App\Utils\UserSession;

use App\Utils\UpdatesHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;

class telegramBot extends Command
{
    protected $signature = 'telegram_bot';

    protected $description = 'Start Coupon Bot';

    public function __construct()
    {
        parent::__construct();
        $this->updatesHandler = new UpdatesHandler();
        //$this->userSession = new UserSession();
    }
    //test04_2021Bot

    public function handle()
    {
        $this->lastId = Cache::get('telegram_update_id');
        while (true) {
            sleep(1);
            $updates = Telegram::getUpdates();
            foreach ($updates as $update) {
                if ($update->update_id > $this->lastId) {
                    //dump($update);
                    Cache::set('telegram_update_id', $this->lastId);

                    //$this->userSession->setUser($update);
                    if (isset($update['callback_query'])) {
                        $this->updatesHandler->callbackQuery($update['callback_query']);
                    } else if (isset($update['message'])) {
                        $this->updatesHandler->commandRespond($update['message']);
                    }

                    $this->lastId = $update->update_id;
                }
            }
        }
    }
}
