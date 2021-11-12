<?php

namespace App\Utils;

use App\Utils\Bot;
use Telegram\Bot\Actions;
use App\Utils\UserSession;
use Illuminate\Support\Str;
use App\Utils\BotMessageHandler;
use Telegram\Bot\Laravel\Facades\Telegram;



class UpdatesHandler
{

    public function __construct()
    {
        $this->bot = new Bot();

        $this->userSession = new UserSession();
    }

    public function commandRespond($msg)
    {

        $chatid = $msg['chat']['id'];
        //$userid = $msg['from']['id'];
        $msgText = isset($msg['text']) ? $msg['text'] : null;
        Telegram::sendChatAction(['action' => Actions::TYPING, 'chat_id' => $chatid]);
        $this->msgHandler = new BotMessageHandler();


        if (!$msgText) {
            $this->msgHandler->mainMenu($chatid);
            return;
        }

        if (Str::startsWith($msgText, '/start')) {
            $referrer = Str::replace('/start', '', $msgText);
            $msgText = '/start';
            $referrer = (string)Str::of($referrer)->trim();
            dump($referrer);
            if ($referrer) {
                $user = $this->userSession->getDbUserByTgUser($msg['from']);
                $activity['tguser_id'] = $user['id'];
                $activity['type'] = 'referrer';
                $activity['referrer'] = $referrer;
                $this->userSession->saveActivity($activity);
            }
        }

        switch ($msgText) {
            case '/start':
                $this->msgHandler->mainMenu($chatid);
                break;
            default:
                $this->bot->sendMsg($chatid, 'Я не знаю такую команду');
        }
    }
    //test04_2021Bot
    public function callbackQuery($msg)
    {
        $chatid = $msg['message']['chat']['id'];
        $userid = $msg['from']['id'];
        $data = $msg['data'];

        $user = $this->userSession->getDbUserByTgUser($msg['from']);
        $this->msgHandler = new BotMessageHandler($user);

        $params = [];
        if (strpos($data, 'action=') !== false) {
            parse_str($data, $params);
        }

        $action = isset($params['action']) ? $params['action'] : null;

        // dump($data);
        //dump($params);
        $activity = $params;
        $activity['tguser_id'] = $user['id'];
        $activity['type'] = 'inner';
        $this->userSession->saveActivity($activity);

        Telegram::sendChatAction(['action' => Actions::TYPING, 'chat_id' => $chatid]);
        switch ($action) {
            case 'categoriesMenu':
                $this->msgHandler->categoriesMenu($chatid);
                break;
            case 'shopsMenu':
                $this->msgHandler->shopsMenu($chatid);
                break;
            case 'inCategoryMenu':
                $this->msgHandler->inCategoryMenu($chatid, $params);
                break;
            case 'allCoupons':
                $this->msgHandler->allCoupons($chatid, $params);
                break;
            case 'expiringCoupons':
                $this->msgHandler->expiringCoupons($chatid, $params);
                break;
            case 'byCatAndShopMeny':
                $this->msgHandler->byCatAndShopMeny($chatid, $params);
                break;
            case 'catShopCoupons':
                $this->msgHandler->catShopCoupons($chatid, $params);
                break;
            case 'shopPage':
                $this->msgHandler->shopPage($chatid, $params);
                break;
            case 'menuBack':
                $this->msgHandler->mainMenu($chatid);
                break;
            default:
                $this->bot->sendMsg($chatid, 'Я не знаю такую команду');
        }
    }
}
