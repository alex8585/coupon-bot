<?php

namespace App\Utils;

use App\Utils\Bot;
use App\Utils\BotMessageHandler;

class UpdatesHandler
{
    public function __construct()
    {
        $this->bot = new Bot();
        $this->msgHandler = new BotMessageHandler();
    }

    public function commandRespond($msg)
    {

        $chatid = $msg['chat']['id'];
        $msgText = $msg['text'];
        dump($msgText);
        switch ($msgText) {
            case '/start':
                $this->msgHandler->mainMenu($chatid);
                break;
            default:
                $this->bot->sendMsg($chatid, 'Я не знаю такую команду');
        }
    }

    public function callbackQuery($msg)
    {
        $chatid = $msg['message']['chat']['id'];
        $data = $msg['data'];


        $params = [];
        if (strpos($data, 'action=') !== false) {
            parse_str($data, $params);
        }

        $action = isset($params['action']) ? $params['action'] : null;
        dump($data);
        dump($params);

        switch ($action) {
            case 'categoriesMenu':
                $this->msgHandler->categoriesMenu($chatid);
                break;
            case 'shopsMenu':
                $this->msgHandler->shopsMenu($chatid);
                break;
            case 'categoryPage':
                $this->msgHandler->categoryPage($chatid, $params);
                break;
            case 'shopPage':
                $this->msgHandler->shopPage($chatid, $params);
                break;
            default:
                $this->bot->sendMsg($chatid, 'Я не знаю такую команду');
        }
    }
}
