<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Source;
use Illuminate\Console\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\FileUpload\InputFile;
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
        $this->shops = Source::where('type', 'shop')->get()->pluck('title')->toArray();
        $this->categories = Source::where('type', 'category')->get()->pluck('title')->toArray();
        //Telegram::addCommand(\Telegram\Bot\Commands\HelpCommand::class);
        //$comands = Telegram::getCommands();
        //dd($comands);

        $this->lastId = Cache::get('telegram_update_id');
        while (true) {
            sleep(1);
            $msgs = Telegram::getUpdates();
            //dump($msgs);
            foreach ($msgs as $msg) {

                //dump($msg->update_id);
                if ($msg->update_id > $this->lastId) {
                    Cache::set('telegram_update_id', $this->lastId);
                    //dump($msg);

                    if (isset($msg['callback_query'])) {
                        $this->callback_query($msg);
                    } else {
                        $this->respond($msg);
                    }

                    $this->lastId = $msg->update_id;
                }
            }
        }
    }


    public function callback_query($msg)
    {
        $chatid = $msg['callback_query']['message']['chat']['id'];
        $data = $msg['callback_query']['data'];
        dump($data);
        switch ($data) {
            case 'categories':
                $this->categoriesMenu($chatid);
                break;
            case 'shops':
                $this->shopsMenu($chatid);
                break;
            default:
                if ($this->shopPage($chatid, $data)) {
                    break;
                }
                if ($this->categoryPage($chatid, $data)) {
                    break;
                }
                $this->sendMsg($chatid, $data);
        }
    }


    public function respond($msg)
    {

        //@test04_2021Bot
        $chatid = $msg['message']['chat']['id'];
        $msgText = $msg['message']['text'];

        switch ($msgText) {
            case '/start':
                $this->mainMenu($chatid);
                break;
            default:
        }
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

    public function categoryPage($chatid,  $data)
    {

        if (!in_array($data, $this->categories)) {
            return false;
        }
        $category = Source::where('type', 'category')->where('title', $data)->first();
        $coupons = Coupon::where('type', 'category')->where('source_id', $category->id)->with('logo')->get();



        $logo = $coupons->first()->logo->url;
        $html = '';
        foreach ($coupons as $couponObj) {
            dump($couponObj);
            $coupon = json_decode($couponObj->data);
            $html .= "<b>{$coupon->name}</b>";
            $html .= "<pre>Срок действия: {$coupon->date_start} - {$coupon->date_end}</pre>";
            $html .= "<pre>Промокод: {$coupon->promocode}</pre>";
            $html .= "<a href='{$coupon->gotolink}'>ПОЛУЧИТЬ КУПОН</a>";
            $html .= "<pre>{$coupon->description}</pre>";
            $html .= "<pre> </pre>";
        }

        $keyboardArr = $this->mainMenuKeybord();
        $this->sendPhoto($chatid, $logo, '', $keyboardArr);



        return true;
    }


    public function shopPage($chatid,  $data)
    {

        if (!in_array($data, $this->shops)) {
            return false;
        }
        $shop = Source::where('type', 'shop')->where('title', $data)->first();
        $coupons = Coupon::where('type', 'shop')->where('source_id', $shop->id)->with('logo')->get();

        $logo = $coupons->first()->logo->url;
        $html = '';
        foreach ($coupons as $couponObj) {
            $coupon = json_decode($couponObj->data);
            $html .= "<b>{$coupon->name}</b>";
            $html .= "<pre>Срок действия: {$coupon->date_start} - {$coupon->date_end}</pre>";
            $html .= "<pre>Промокод: {$coupon->promocode}</pre>";
            $html .= "<a href='{$coupon->gotolink}'>ПОЛУЧИТЬ КУПОН</a>";
            $html .= "<pre>{$coupon->description}</pre>";
            $html .= "<pre> </pre>";
        }

        // $html = "<b>bold</b>, <strong>bold</strong>
        // <i>italic</i>, <em>italic</em>
        // <a href=''>inline URL</a>
        // <code>inline fixed-width code</code>
        // <pre>pre-formatted fixed-width code block</pre>";
        $keyboardArr = $this->mainMenuKeybord();
        $this->sendPhoto($chatid, $logo, $html, $keyboardArr);

        return true;
    }




    public function sendHtml($chatid, $html)
    {
        $response = Telegram::sendMessage([
            'chat_id' => $chatid,
            'text' =>  $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ]);
        $messageId = $response->getMessageId();
    }


    public function mainMenuKeybord()
    {
        return [
            ['text' => 'КАТЕГОРИИ', 'callback_data' => 'categories'],
            ['text' => 'МАГАЗИНЫ', 'callback_data' => 'shops']
        ];
    }

    public function mainMenu($chatid)
    {
        $txt = 'Как ищем?';

        $keyboardArr = $this->mainMenuKeybord();
        $this->sendMenu($chatid, $keyboardArr, $txt);
    }
    public function categoriesMenu($chatid)
    {
        $txt = 'Выберите категорию';

        $keyboardArr = [];
        foreach ($this->categories as $cat) {
            $keyboardArr[] = [
                'text' => $cat,
                'callback_data' => $cat,
            ];
        }
        $this->sendMenu($chatid, $keyboardArr, $txt);
    }

    public function shopsMenu($chatid)
    {
        $txt = 'Выберите магазин';

        $keyboardArr = [];
        foreach ($this->shops as $shop) {
            $keyboardArr[] = [
                'text' => $shop,
                'callback_data' => $shop,
            ];
        }

        $this->sendMenu($chatid, $keyboardArr, $txt);
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
