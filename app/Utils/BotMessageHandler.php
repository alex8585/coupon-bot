<?php

namespace App\Utils;

use App\Utils\Bot;
use App\Models\Coupon;
use App\Models\Source;
use App\Utils\Paginator;
use Illuminate\Support\Str;

class BotMessageHandler
{
    // $html = "<b>bold</b>, <strong>bold</strong>
    // <i>italic</i>, <em>italic</em>
    // <a href=''>inline URL</a>
    // <code>inline fixed-width code</code>
    // <pre>pre-formatted fixed-width code block</pre>";


    public  function __construct()
    {
        $this->bot = new Bot();
        $this->paginator = new Paginator();

        $this->shops = Source::where('type', 'shop')->get()->toArray();
        $this->categories = Source::where('type', 'category')->get()->toArray();
    }

    public function categoryPage($chatid,  $params)
    {
        $perPage = 4;
        $chunkSize = 2;
        $descriptionLimit = 100;
        $page = isset($params['page']) ? $params['page'] : 1;
        $shopId = isset($params['shop_id']) ? $params['shop_id'] : null;


        $category = Source::where('type', 'category')->where('id', $params['category_id'])->first();
        $couponsObj = Coupon::where('type', 'category')
            ->where('source_id', $category->id)->with('logo')
            ->orderBy('advcampaign_id');

        if ($shopId) {
            $couponsObj = $couponsObj->where('advcampaign_id', $shopId);
        }

        $couponsObj = $couponsObj->paginate($perPage, '*', 'page', $page);
        $couponsCount = $couponsObj->count();
        $coupons = [];
        foreach ($couponsObj as $couponObj) {
            $elem = $couponObj->toArray();
            $elem['data'] = json_decode($elem['data'], true);
            $elem['logo'] = $couponObj->logo->url;
            $coupons[$couponObj->advcampaign_id][] = $elem;
        }


        $cnt = 0;
        foreach ($coupons as  $shopCouponsAll) {
            $shopCoupons = array_chunk($shopCouponsAll, $chunkSize);
            dump($shopCoupons);
            $chunks = count($shopCoupons);
            foreach ($shopCoupons as $chunk) {
                $html = '';
                $logo = $chunk[0]['logo'];
                $shopName = $chunk[0]['data']['shop_name'];
                $shop_id = $chunk[0]['data']['advcampaign_id'];
                foreach ($chunk as $couponNum => $coupon) {
                    $data = $coupon['data'];
                    $description = Str::limit($data['description'],  $descriptionLimit,  '...');

                    if ($couponNum == 0) {
                        // $html .= "<b>{$shopName}</b><pre> </pre>";
                    }
                    $html .= "<b>{$data['name']}</b>" . PHP_EOL;
                    $html .= "<pre>Срок действия: {$data['date_start']} - {$data['date_end']}</pre>" . PHP_EOL;
                    $html .= "<pre>Промокод: {$data['promocode']}</pre>" . PHP_EOL;
                    $html .= "<a href='{$data['gotolink']}'>ПОЛУЧИТЬ КУПОН</a>" . PHP_EOL;
                    $html .= "<pre>{$description}</pre>" . PHP_EOL;
                    $cnt++;
                }
                //dump($chunks);
                if ($cnt == $couponsCount) {
                    $keybordParams = [
                        'action' => 'categoryPage',
                        'category_id' => $params['category_id'],
                        'shop_id' => $shopId,
                    ];
                    if (!$shopId) {
                        $keyboardArr = $this->paginator->getKeybord($couponsObj, $keybordParams, "\xE2\x9C\x85 " . $shopName, $shop_id);
                    } else {
                        $keyboardArr = $this->paginator->getKeybord($couponsObj, $keybordParams, "\xE2\x9D\x8C Сбросить фильтр");
                    }

                    $this->bot->sendPhoto($chatid, $logo, $html, $keyboardArr);
                } else {
                    //$this->bot->sendPhoto($chatid, $logo, $html);

                    $keybordParams = [
                        'action' => 'categoryPage',
                        'category_id' => $params['category_id'],
                        'page' => 1
                    ];

                    if (!$shopId) {
                        $keybordParams['shop_id'] = $shop_id;
                        $keyboardArr = $this->paginator->getFilterKeybord($shopName, $keybordParams);
                    } else {
                        $keybordParams['shop_id'] = null;
                        $keyboardArr = $this->paginator->getFilterKeybord("\xE2\x9D\x8C Сбросить фильтр", $keybordParams);
                    }
                    $this->bot->sendPhoto($chatid, $logo, $html, $keyboardArr);
                }
            }
        }

        return true;
    }


    public function shopPage($chatid,  $params)
    {
        $perPage = 4;
        $chunkSize = 2;
        $descriptionLimit = 100;
        $page = isset($params['page']) ? $params['page'] : 1;


        $shop = Source::where('type', 'shop')->where('id', $params['shop_id'])->first();
        $couponsObj = Coupon::where('type', 'shop')->where('source_id', $shop->id)->with('logo')->paginate($perPage, '*', 'page', $page);

        $logo = $couponsObj->first()->logo->url;

        $couponsCount = $couponsObj->count();
        $couponsArrPaginator = $couponsObj->toArray();
        $couponsArr = $couponsArrPaginator['data'];

        $shopCoupons = array_chunk($couponsArr, $chunkSize);

        $cnt = 0;

        foreach ($shopCoupons as $chunk) {
            $html = '';
            foreach ($chunk as $couponNum => $couponArr) {
                $coupon = json_decode($couponArr['data'], true);
                $description = Str::limit($coupon['description'],  $descriptionLimit,  '...');
                $html .= "<b>{$coupon['name']}</b>". PHP_EOL;;
                $html .= "<pre>Срок действия: {$coupon['date_start']} - {$coupon['date_end']}</pre>". PHP_EOL;;
                $html .= "<pre>Промокод: {$coupon['promocode']}</pre>". PHP_EOL;
                $html .= "<a href='{$coupon['gotolink']}'>ПОЛУЧИТЬ КУПОН</a>". PHP_EOL;
                $html .= "<pre>{$description}</pre>";
                $cnt++;
            }

            if ($cnt == $couponsCount) {
                $keybordParams = [
                    'action' => 'shopPage',
                    'shop_id' => $params['shop_id'],
                ];
                $keyboardArr = $this->paginator->getKeybord($couponsObj, $keybordParams);


                $this->bot->sendPhoto($chatid, $logo, $html, $keyboardArr);
            } else {
                $this->bot->sendPhoto($chatid, $logo, $html);
            }
        }






        return true;
    }



    public function mainMenuKeybord()
    {
        $categoriesCallback['action'] = 'categoriesMenu';
        $shopsCallback['action'] = 'shopsMenu';
        return [
            ['text' => '🗄 КАТЕГОРИИ', 'callback_data' => http_build_query($categoriesCallback)],
            ['text' => '🛍 МАГАЗИНЫ', 'callback_data' => http_build_query($shopsCallback)]
        ];
    }

    public function mainMenu($chatid)
    {
        $txt = "Как ищем ?";

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
