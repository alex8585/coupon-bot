<?php

namespace App\Utils;

use App\Utils\Bot;
use App\Models\Shop;
use App\Models\Coupon;
use App\Models\Source;
use App\Utils\Paginator;
#use App\Utils\UrlConverter;
use Illuminate\Support\Str;


class BotMessageHandler
{
    // $html = "<b>bold</b>, <strong>bold</strong>
    // <i>italic</i>, <em>italic</em>
    // <a href=''>inline URL</a>
    // <code>inline fixed-width code</code>
    // <pre>pre-formatted fixed-width code block</pre>";


    public  function __construct($user = null)
    {
        $this->bot = new Bot();
        $this->paginator = new Paginator();
        $this->url = new UrlConverter();
        $this->user = $user;
    }


    public function shopPage($chatid,  $params)
    {
        $perPage = 3;
        $chunkSize = 1;
        $descriptionLimit = 100;
        $page = isset($params['page']) ? $params['page'] : 1;

        $couponsObj = Coupon::sourceShop($params['shop_id'])->paginate($perPage, '*', 'page', $page);
        if (!$couponsObj->first()) {
            return;
        }
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
                $date_start = isset($couponArr['date_start']) ? $couponArr['date_start'] : '';
                $date_end = isset($couponArr['date_end']) ? $couponArr['date_end'] : 'None';

                //$gotolink = !empty($coupon['gotolink']) ? $coupon['gotolink'] : $coupon['oldGotolink'];
                $gotolink = $this->url->getInnerUrl($coupon['oldGotolink'],  $this->user->id, $couponArr['id']);
                $html .= "<b>{$coupon['name']}</b>" . PHP_EOL;;
                $html .= "<pre>Срок действия: {$date_start} - {$date_end}</pre>" . PHP_EOL;
                $html .= "<pre>Промокод: {$coupon['promocode']}</pre>" . PHP_EOL;
                $html .= "=======================" . PHP_EOL;
                $html .= " \xE2\x9D\x97<a href='{$gotolink}'>ПРИМЕНИТЬ КУПОН</a>\xE2\x9D\x97" . PHP_EOL;
                $html .= "=======================" . PHP_EOL;
                $html .= "<pre>{$description}</pre>";
                $cnt++;
            }

            if ($cnt == $couponsCount) {
                $keybordParams = [
                    'action' => 'shopPage',
                    'shop_id' => $params['shop_id'],
                ];
                $keyboardArr = $this->paginator->getShopKeybord($couponsObj, $keybordParams);

                $this->bot->sendPhoto($chatid, $logo, $html, $keyboardArr);
            } else {
                $this->bot->sendPhoto($chatid, $logo, $html);
            }
        }

        return true;
    }




    public function showCatCoupons($chatid,  $params, $couponsObj)
    {
        $perPage = 3;
        $chunkSize = 1;
        $descriptionLimit = 100;
        $action = $params['action'];
        $page = isset($params['page']) ? $params['page'] : 1;
        $shopId = isset($params['shop_id']) ? $params['shop_id'] : null;
        $is_id = isset($params['is_id']) ? $params['is_id'] : null;
        $category_id =  isset($params['category_id']) ? $params['category_id'] : null;

        $withFilter = !$is_id;


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

            foreach ($shopCoupons as $chunk) {
                $html = '';
                $logo = $chunk[0]['logo'];
                $shopName = $chunk[0]['data']['shop_name'];
                $shop_id = $chunk[0]['data']['advcampaign_id'];
                foreach ($chunk as $couponNum => $coupon) {
                    $data = $coupon['data'];
                    $description = Str::limit($data['description'],  $descriptionLimit,  '...');
                    $gotolink = $this->url->getInnerUrl($data['oldGotolink'], $this->user->id, $coupon['id']);


                    $date_start = isset($coupon['date_start']) ? $coupon['date_start'] : '';
                    $date_end = isset($coupon['date_end']) ? $coupon['date_end'] : 'None';
                    if ($couponNum == 0) {
                        // $html .= "<b>{$shopName}</b><pre> </pre>";
                    }
                    $html .= "<b>{$data['name']}</b>" . PHP_EOL;
                    $html .= "<pre>Срок действия: {$date_start} - {$date_end}</pre>" . PHP_EOL;
                    $html .= "<pre>Промокод: {$data['promocode']}</pre>" . PHP_EOL;
                    $html .= "=======================" . PHP_EOL;
                    $html .= " \xE2\x9D\x97<a href='{$gotolink}'>ПРИМЕНИТЬ КУПОН</a>\xE2\x9D\x97" . PHP_EOL;
                    $html .= "=======================" . PHP_EOL;
                    $html .= "<pre>{$description}</pre>" . PHP_EOL;
                    $cnt++;
                }

                if ($cnt == $couponsCount) {
                    $keybordParams = [
                        'action' =>   $action,

                    ];

                    if ($category_id) {
                        $keybordParams['category_id'] = $category_id;
                    }
                    if ($shopId) {
                        $keybordParams['shop_id'] = $shopId;
                    }
                    if ($is_id) {
                        $keybordParams['is_id'] = $is_id;
                    }
                    if (!$shopId) {
                        $keyboardArr = $this->paginator->getKeybord($couponsObj, $keybordParams, "\xE2\x9C\x85 " . $shopName, $shop_id, $withFilter);
                    } else {
                        $keyboardArr = $this->paginator->getKeybord($couponsObj, $keybordParams, "\xE2\x9D\x8C Сбросить фильтр", null, $withFilter);
                    }
                } else {
                    $keyboardArr = [];
                    if ($withFilter) {

                        $keybordParams = [
                            'action' =>  $action,
                            'category_id' => $category_id,
                            'page' => 1
                        ];

                        if (!$shopId) {
                            $keybordParams['shop_id'] = $shop_id;
                            $keyboardArr = $this->paginator->getFilterKeybord("\xE2\x9C\x85 " . $shopName, $keybordParams);
                        } else {
                            $keybordParams['shop_id'] = null;
                            $keyboardArr = $this->paginator->getFilterKeybord("\xE2\x9D\x8C Сбросить фильтр", $keybordParams);
                        }
                    }
                }
                $this->bot->sendPhoto($chatid, $logo, $html, $keyboardArr);
            }
        }

        return true;
    }


    public function allCoupons($chatid, $params)
    {
        $category_id = $params['category_id'];
        $couponsObj = Coupon::category($category_id);
        $this->showCatCoupons($chatid, $params, $couponsObj);
    }

    public function expiringCoupons($chatid, $params)
    {
        $category_id = $params['category_id'];
        $couponsObj = Coupon::expiring($category_id);
        $this->showCatCoupons($chatid, $params, $couponsObj);
    }

    public function catShopCoupons($chatid, $params)
    {
        $category_id = $params['category_id'];
        $is_id = isset($params['is_id']) ? $params['is_id'] : null;
        $couponsObj = Coupon::category($category_id)->where('shop_id', $is_id);

        $this->showCatCoupons($chatid, $params, $couponsObj);
    }

    public function byCatAndShopMeny($chatid, $params)
    {
        $category_id = $params['category_id'];
        $txt = "Выберите магазин:";

        $shops = Shop::category($category_id)->get()->pluck('name', 'id');

        $callback_data = ['action' => 'catShopCoupons'];
        $callback_data['category_id'] = $params['category_id'];
        $keyboardArr = [];
        foreach ($shops as $id => $name) {
            $callback_data['is_id'] = $id;
            $keyboardArr[] = [
                'text' => $name,
                'callback_data' => http_build_query($callback_data),
            ];
        }
        $this->bot->sendMenu($chatid, $keyboardArr, $txt);
    }

    public function inCategoryMenuKeybord($params)
    {
        $category_id = $params['category_id'];
        $count  = Coupon::expiring($category_id)->selectRaw('count(*) as aggregate')->first()->aggregate;

        $all = $expiring = $byShop = $params;
        $all['action'] = 'allCoupons';
        $expiring['action'] = 'expiringCoupons';
        $byShop['action'] = 'byCatAndShopMeny';

        $keyboard = [
            ['text' => "\xF0\x9F\x98\x83 ВСЕ КУПОНЫ", 'callback_data' => http_build_query($all)],
            ['text' => "🛍 ВЫБРАТЬ МАГАЗИН", 'callback_data' => http_build_query($byShop)],
        ];
        if ($count) {
            $keyboard[] = ['text' => "\xE2\x99\xA8 ИСТЕКАЮТ СЕГОДНЯ", 'callback_data' => http_build_query($expiring)];
        }
        return $keyboard;
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
    public function inCategoryMenu($chatid, $params)
    {
        $cat = Source::find($params['category_id']);
        $txt = $cat->title . " :";
        $keyboardArr = $this->inCategoryMenuKeybord($params);
        $this->bot->sendMenu($chatid, $keyboardArr, $txt);
    }


    public function mainMenu($chatid)
    {
        $txt = "Как ищем ?";
        $keyboardArr = $this->mainMenuKeybord();
        $this->bot->sendMenu($chatid, $keyboardArr, $txt);
    }

    public function categoriesMenu($chatid)
    {

        $categories = Source::where('type', 'category')->get()->toArray();
        $txt = 'Выберите категорию:';
        $callback_data = ['action' => 'inCategoryMenu'];
        $keyboardArr = [];
        foreach ($categories as $cat) {
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
        $shops = Source::where('type', 'shop')->get()->toArray();
        $txt = 'Выберите магазин:';
        $callback_data = ['action' => 'shopPage'];
        $keyboardArr = [];
        foreach ($shops as $shop) {
            $callback_data['shop_id'] = $shop['id'];
            $keyboardArr[] = [
                'text' => $shop['title'],
                'callback_data' => http_build_query($callback_data),
            ];
        }

        $this->bot->sendMenu($chatid, $keyboardArr, $txt);
    }
}
