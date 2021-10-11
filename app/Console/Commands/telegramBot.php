<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class telegramBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram_bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Coupon Bot';

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
        //Telegram::addCommand(\Telegram\Bot\Commands\HelpCommand::class);
        //$comands = Telegram::getCommands();
        //dd($comands);

        $this->lastId = 198098369;
        while (true) {
            sleep(1);
            $msgs = Telegram::getUpdates();
            dump($msgs);
            foreach ($msgs as $msg) {

                //dump($msg->update_id);
                if ($msg->update_id > $this->lastId) {
                    $this->respond($msg);
                    $this->lastId = $msg->update_id;
                }
            }
        }
    }



    public function respond($msg)
    {

        //@test04_2021Bot
        $chatid = $msg['message']['chat']['id'];
        $text = $msg['message']['text'];

        switch ($text) {
            case '/start':
                $txt = 'Как ищем?';
                $this->mainMenu($chatid, $txt);
                break;
            case 'КАТЕГОРИИ':
                $txt = 'Товары для детей Товары для дома';
                $this->sendMsg($chatid, $txt);

                break;
            case 'МАГАЗИНЫ':
                $txt = 'МАГАЗИНЫ МАГАЗИНЫ';
                $this->sendMsg($chatid, $txt);
                break;
            case '/contact';

                break;
            default:
                $txt = 'нужные товары с большими скидками. Покупайте с экономией! Нажимайте /start';
                $this->sendMsg($chatid, $txt);
        }
    }


    public function mainMenu($chatid, $txt)
    {
        $keyboard = [
            ['КАТЕГОРИИ', 'МАГАЗИНЫ'],
        ];
        $this->sendMenu($chatid, $keyboard, $txt);
    }



    public function sendMenu($chatid, $keyboard, $txt = '')
    {

        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $response = Telegram::sendMessage([
            'chat_id' => $chatid,
            'text' => $txt,
            'reply_markup' => $reply_markup
        ]);

        $messageId = $response->getMessageId();
    }


    public function sendMsg($chatid, $txt)
    {
        $response = Telegram::sendMessage([
            'chat_id' => $chatid,
            'text' => $txt
        ]);
    }

    public function ____showMenu($chatid)
    {
        $message = '';
        $message .=  '/website' . chr(10);
        $message .= '/contact' . chr(10);

        $response = Telegram::sendMessage([
            'chat_id' => $chatid,
            'text' => $message
        ]);
    }
}
