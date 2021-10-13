<?php

namespace App\Utils;

use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class Bot
{
    public  function __construct()
    {
    }
    public function sendPhoto($chatid, $file = '', $html = '', $keyboardArr = [])
    {
        $keyboard = $this->makeKeybord($keyboardArr);

        $params = [
            'chat_id' => $chatid,
            'photo'                => new InputFile($file),
            'caption'              => $html,
            'parse_mode' => 'HTML',
        ];
        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $response = Telegram::sendPhoto($params);
    }
    public function makeKeybord($keyboardArr)
    {
        if (!$keyboardArr) {
            return [];
        }
        $keyboardArr = array_chunk($keyboardArr, 3);

        $inlineLayout = [];
        foreach ($keyboardArr as $row) {
            $newRow = [];
            foreach ($row as  $btn) {
                $newRow[] = Keyboard::inlineButton(['text' => $btn['text'], 'callback_data' => $btn["callback_data"]]);
            }
            $inlineLayout[] = $newRow;
        }

        $keyboard = Keyboard::make([
            'inline_keyboard' => $inlineLayout
        ]);

        return  $keyboard;
    }
    public function sendMenu($chatid, $keyboardArr, $txt)
    {

        $keyboard = $this->makeKeybord($keyboardArr);
        $response = Telegram::sendMessage([
            'chat_id' => $chatid,
            'text' => $txt,
            'reply_markup' => $keyboard
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
}
