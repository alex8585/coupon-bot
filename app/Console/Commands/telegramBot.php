<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Source;
use Illuminate\Console\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Support\Facades\Cache;
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
        $this->categories = Source::where('type', 'categories')->get()->pluck('title')->toArray();
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
                    dump($msg);
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
        $msgText = $msg['message']['text'];

        switch ($msgText) {
            case '/start':
                $txt = 'Как ищем?';
                $this->mainMenu($chatid, $txt);
                break;
            case 'КАТЕГОРИИ':
                $txt = 'Товары для детей Товары для дома';
                $this->sendMsg($chatid, $txt);

                break;
            case 'МАГАЗИНЫ':
                $txt = 'Выберите магазин';
                $this->shopsMenu($chatid, $txt);
                break;
            case '/contact';

                break;
            default:
                $this->shopPage2($chatid, $msgText);
                // $txt = 'нужные товары с большими скидками. Покупайте с экономией! Нажимайте /start';
                //$this->sendMsg($chatid, $txt);
        }
    }


    public function shopPage2($chatid,  $msgText)
    {

        if (!in_array($msgText, $this->shops)) {
            return;
        }

        $shop = Source::where('type', 'shop')->where('title', $msgText)->first();
        $coupons = Coupon::where('type', 'shop')->where('source_id', $shop->id)->with('logo')->get();


        $client = new \GuzzleHttp\Client(['http_errors' => false, 'headers' => ['Content-Type' => 'multipart/form-data']]);
        $apiToken = "1663064930:AAGjElDtI4SVl0usG8cN2x-LIsloJ11nZPc";
        $params = [
            'chat_id' => $chatid,
        ];

        $url = "https://api.telegram.org/bot$apiToken/sendMessage";
        $url .= '?' . http_build_query($params);



        foreach ($coupons as $couponObj) {
            $html = $msgText;
            $coupon = json_decode($couponObj->data);
            dump($coupon);
            //$html .=  "\n\n\t";
            $html .= "[ ]({$couponObj->logo->url})";
            $html .=  "\n\n\t";
            $html .= $coupon->name;
            $html .=  "\n\n\t";
            $html .= $coupon->date_start . " - " . $coupon->date_end;
            $html .=  "\n\n\t";
            $html .= $coupon->promocode;
            $html .=  "\n\n\t";
            $html .= "[ПОЛУЧИТЬ КУПОН]({$coupon->gotolink})";
            $html .=  "\n\n\t";
            $html .= $coupon->description;

            $optionsImgUpload['form_params'] = [
                'parse_mode' => 'markdown',
                'text' =>     $html,
                //'disable_web_page_preview' => false,
            ];


            $res = $client->request('POST', $url,  $optionsImgUpload);
            $resPhotos =  json_decode($res->getBody()->getContents());

            break;
        }
    }

    public function shopPage($chatid,  $msgText)
    {

        if (!in_array($msgText, $this->shops)) {
            return;
        }
        $shop = Source::where('type', 'shop')->where('title', $msgText)->first();
        $coupons = Coupon::where('type', 'shop')->where('source_id', $shop->id)->with('logo')->get();

        //dump($coupons);
        $html = "<pre style='text-align:center;'> Магазин: $msgText</pre>";
        $html .= "<pre> </pre>";
        foreach ($coupons as $couponObj) {
            dump($couponObj->logo->url);
            $coupon = json_decode($couponObj->data);
            //dump($coupon);
            $html .= "<pre>" . $couponObj->logo->url . "</pre>";
            $html .= "<b>{$coupon->name}</b>";
            //$html .= "<pre>Магазин: {$coupon->shop_name}</pre>";
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
        $this->sendHtml($chatid, $html);
    }


    public function shopsMenu($chatid, $txt)
    {

        $keyboard[] = $this->shops;
        dump($keyboard);
        //$keyboard = [];
        $this->sendMenu($chatid, $keyboard, $txt);
    }

    public function sendHtml($chatid, $html)
    {
        $response = Telegram::sendMessage([
            'chat_id' => $chatid,
            'text' =>  $html,
            'parse_mode' => 'HTML',
            //'disable_web_page_preview' => false,
        ]);
        $messageId = $response->getMessageId();
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
