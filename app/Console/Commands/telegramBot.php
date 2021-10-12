<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Source;
use Illuminate\Support\Str;
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
        $this->shops = Source::where('type', 'shop')->get()->toArray();
        $this->categories = Source::where('type', 'category')->get()->toArray();
        //Telegram::addCommand(\Telegram\Bot\Commands\HelpCommand::class);
        //$comands = Telegram::getCommands();
        //dd($comands);

        $this->lastId = Cache::get('telegram_update_id');
        while (true) {
            sleep(1);
            $msgs = Telegram::getUpdates();
            foreach ($msgs as $msg) {

                if ($msg->update_id > $this->lastId) {
                    Cache::set('telegram_update_id', $this->lastId);

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


        $params = [];
        if (strpos($data, 'action=') !== false) {
            parse_str($data, $params);
        }


        $action = isset($params['action']) ? $params['action'] : null;
        dump($data);
        dump($params);
        //$this->categoryPage($chatid, $data);

        switch ($action) {
            case 'categoriesMenu':
                $this->categoriesMenu($chatid);
                break;
            case 'shopsMenu':
                $this->shopsMenu($chatid);
                break;
            case 'categoryPage':
                $this->categoryPage($chatid, $params);
                break;
            case 'shopPage':
                $this->shopPage($chatid, $params);
                break;
            default:
                // if ($this->shopPage($chatid, $data)) {
                //     break;
                // }
                // if ($this->categoryPage($chatid, $data)) {
                //     break;
                // }
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


    public function getPagesArr($paginator)
    {

        $pages = [
            "curr" => $paginator->currentPage(),
            "next" => null,
            "prev" => null,
        ];

        $nextPageUrl = $paginator->nextPageUrl();
        $previousPageUrl = $paginator->previousPageUrl();


        if ($nextPageUrl) {
            $query = parse_url($nextPageUrl)['query'];
            parse_str($query, $params);
            $pages['next'] = $params['page'];
        }

        if ($previousPageUrl) {
            $query = parse_url($previousPageUrl)['query'];
            parse_str($query, $params);
            $pages['prev'] = $params['page'];
        }
        return $pages;
    }

    public function paginatorKeybord($paginator, $params)
    {
        $callback_data = $params;

        $keybord = [];
        $pages = $this->getPagesArr($paginator);
        if ($pages['prev']) {
            $callback_data['page'] = $pages['prev'];
            $keybord[] = ['text' => 'Предыдущая', 'callback_data' => http_build_query($callback_data)];
        }
        if ($pages['next']) {
            $callback_data['page'] = $pages['next'];
            $keybord[] = ['text' => 'Следующая', 'callback_data' => http_build_query($callback_data)];
        }
        return $keybord;
    }

    public function categoryPage($chatid,  $params)
    {
        $page = isset($params['page']) ? $params['page'] : 1;

        $category = Source::where('type', 'category')->where('id', $params['category_id'])->first();
        $couponsObj = Coupon::where('type', 'category')
            ->where('source_id', $category->id)->with('logo')
            ->orderBy('advcampaign_id')->paginate(5, '*', 'page', $page);
        // $couponsObj =  $this->getLoadMoreLengthAwarePaginator($couponsObj, 10, $page);


        $coupons = [];
        foreach ($couponsObj as $couponObj) {
            $elem = $couponObj->toArray();
            $elem['data'] = json_decode($elem['data'], true);
            $elem['logo'] = $couponObj->logo->url;
            $coupons[$couponObj->advcampaign_id][] = $elem;
        }



        foreach ($coupons as $shopId => $shopCoupons) {
            $html = '';
            $logo = $shopCoupons[0]['logo'];
            foreach ($shopCoupons as $coupon) {
                $data = $coupon['data'];
                $description = Str::limit($data['description'],  30,  '...');
                $html .= "<b>{$data['name']}</b>";
                $html .= "<pre>Срок действия: {$data['date_start']} - {$data['date_end']}</pre>";
                $html .= "<pre>Промокод: {$data['promocode']}</pre>";
                $html .= "<a href='{$data['gotolink']}'>ПОЛУЧИТЬ КУПОН</a>";
                $html .= "<pre>{$description}</pre>";
                $html .= "<pre> </pre>";
            }
            //dd($logo);

            $keybordParams = [
                'action' => 'categoryPage',
                'category_id' => $params['category_id'],
            ];
            $keyboardArr = $this->paginatorKeybord($couponsObj, $keybordParams);
            $this->sendPhoto($chatid, $logo, $html, $keyboardArr);
        }





        return true;
    }

    function getLoadMoreLengthAwarePaginator(\Illuminate\Database\Eloquent\Builder $query, $per_page = 10, $page = 1)
    {
        // query total count from DB
        $count = $query->count();

        // get a page number from request
        //$page = request()->get('page') ?? 1;

        // recalculate number of items for first page
        $first_page = $per_page - 1;

        // calculate offset
        $perPage = $page == 1 ? $first_page : $per_page;
        $offset = ($page - 2) * $perPage + $first_page;

        // get a collection from DB
        $reviews = $query->skip($offset)->take($perPage)->get();

        // return Paginator instance
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $reviews,
            $count,
            $per_page,
            $page,
            ['path'  => request()->url(), 'query' => request()->query()]
        );
    }

    public function shopPage($chatid,  $params)
    {

        // if (!in_array($data, $this->shops)) {
        //     return false;
        // }
        $shop = Source::where('type', 'shop')->where('id', $params['shop_id'])->first();
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
        $categoriesCallback['action'] = 'categoriesMenu';
        $shopsCallback['action'] = 'shopsMenu';
        return [
            ['text' => 'КАТЕГОРИИ', 'callback_data' => http_build_query($categoriesCallback)],
            ['text' => 'МАГАЗИНЫ', 'callback_data' => http_build_query($shopsCallback)]
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
        $callback_data = ['action' => 'categoryPage'];
        $keyboardArr = [];
        foreach ($this->categories as $cat) {
            $callback_data['category_id'] = $cat['id'];
            $keyboardArr[] = [
                'text' => $cat['title'],
                'callback_data' => http_build_query($callback_data),
            ];
        }
        $this->sendMenu($chatid, $keyboardArr, $txt);
    }

    public function shopsMenu($chatid)
    {
        $txt = 'Выберите магазин';
        $callback_data = ['action' => 'shopPage'];
        $keyboardArr = [];
        foreach ($this->shops as $shop) {
            $callback_data['shop_id'] = $shop['id'];
            $keyboardArr[] = [
                'text' => $shop['title'],
                'callback_data' => http_build_query($callback_data),
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
