<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Coupon;
use App\Models\Source;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Utils\Bot;
use App\Utils\Paginator;

class CouponBotController extends Controller
{
    public function webhook()
    {
        $this->shops = Source::where('type', 'shop')->get()->toArray();
        $this->categories = Source::where('type', 'category')->get()->toArray();

        //$this->lastId = Cache::get('telegram_update_id');

        $msgs = Telegram::getWebhookUpdates();

        foreach ($msgs as $msg) {
            if (isset($msg['callback_query'])) {
                $this->callbackQuery($msg);
            } else {
                $this->respond($msg);
            }
            //$this->lastId = $msg->update_id;
        }

        //$updates = Telegram::getWebhookUpdates();

        //return $updates;
    }


    public function callbackQuery($msg)
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
                $this->bot->sendMsg($chatid, $data);
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


    public function categoryPage($chatid,  $params)
    {
        $perPage = 4;
        $chunkSize = 2;
        $descriptionLimit = 100;
        $page = isset($params['page']) ? $params['page'] : 1;

        $category = Source::where('type', 'category')->where('id', $params['category_id'])->first();
        $couponsObj = Coupon::where('type', 'category')
            ->where('source_id', $category->id)->with('logo')
            ->orderBy('advcampaign_id')->paginate($perPage, '*', 'page', $page);

        $coupons = [];
        foreach ($couponsObj as $couponObj) {
            $elem = $couponObj->toArray();
            $elem['data'] = json_decode($elem['data'], true);
            $elem['logo'] = $couponObj->logo->url;
            $coupons[$couponObj->advcampaign_id][] = $elem;
        }

        dump($coupons);

        $cnt = 0;
        foreach ($coupons as $shopId => $shopCouponsAll) {
            $shopCoupons = array_chunk($shopCouponsAll, $chunkSize);
            foreach ($shopCoupons as $chunk) {
                $html = '';
                $logo = $chunk[0]['logo'];
                $shopName = $chunk[0]['data']['shop_name'];
                foreach ($chunk as $couponNum => $coupon) {
                    $data = $coupon['data'];
                    $description = Str::limit($data['description'],  $descriptionLimit,  '...');

                    if ($couponNum == 0) {
                        $html .= "<b>{$shopName}</b><pre> </pre>";
                    }

                    $html .= "<i>{$data['name']}</i>";
                    $html .= "<pre>Срок действия: {$data['date_start']} - {$data['date_end']}</pre>";
                    $html .= "<pre>Промокод: {$data['promocode']}</pre>";
                    $html .= "<a href='{$data['gotolink']}'>ПОЛУЧИТЬ КУПОН</a>";
                    $html .= "<pre>{$description}</pre>";
                    $html .= "<pre> </pre>";
                    $cnt++;
                }
                //dd($logo);

                $keybordParams = [
                    'action' => 'categoryPage',
                    'category_id' => $params['category_id'],
                ];
                $keyboardArr = $this->paginator->getKeybord($couponsObj, $keybordParams);
                if ($cnt == $perPage) {
                    $this->bot->sendPhoto($chatid, $logo, $html, $keyboardArr);
                } else {
                    $this->bot->sendPhoto($chatid, $logo, $html);
                }
            }
        }

        return true;
    }


    public function shopPage($chatid,  $params)
    {
        $perPage = 2;
        $chunkSize = 1;
        $descriptionLimit = 100;
        $page = isset($params['page']) ? $params['page'] : 1;


        $shop = Source::where('type', 'shop')->where('id', $params['shop_id'])->first();
        $couponsObj = Coupon::where('type', 'shop')->where('source_id', $shop->id)->with('logo')->paginate($perPage, '*', 'page', $page);

        $logo = $couponsObj->first()->logo->url;

        $couponsArrPaginator = $couponsObj->toArray();
        $couponsArr = $couponsArrPaginator['data'];

        $shopCoupons = array_chunk($couponsArr, $chunkSize);

        $cnt = 0;
        foreach ($shopCoupons as $chunk) {
            $html = '';
            foreach ($chunk as $couponNum => $couponArr) {
                $coupon = json_decode($couponArr['data'], true);
                $description = Str::limit($coupon['description'],  $descriptionLimit,  '...');
                $html .= "<b>{$coupon['name']}</b>";
                $html .= "<pre>Срок действия: {$coupon['date_start']} - {$coupon['date_end']}</pre>";
                $html .= "<pre>Промокод: {$coupon['promocode']}</pre>";
                $html .= "<a href='{$coupon['gotolink']}'>ПОЛУЧИТЬ КУПОН</a>";
                $html .= "<pre>{$description}</pre>";
                $html .= "<pre> </pre>";
                $cnt++;
            }

            $keybordParams = [
                'action' => 'shopPage',
                'shop_id' => $params['shop_id'],
            ];
            $keyboardArr = $this->paginator->getKeybord($couponsObj, $keybordParams);
            if ($cnt == count($chunk)) {
                dump(count($chunk));
                $this->bot->sendPhoto($chatid, $logo, $html, $keyboardArr);
            } else {
                $this->bot->sendPhoto($chatid, $logo, $html);
            }
        }

        // $html = "<b>bold</b>, <strong>bold</strong>
        // <i>italic</i>, <em>italic</em>
        // <a href=''>inline URL</a>
        // <code>inline fixed-width code</code>
        // <pre>pre-formatted fixed-width code block</pre>";

        //dump($coupons);


        return true;
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
        $this->bot->sendMenu($chatid, $keyboardArr, $txt);
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
        $this->bot->sendMenu($chatid, $keyboardArr, $txt);
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

        $this->bot->sendMenu($chatid, $keyboardArr, $txt);
    }
}
