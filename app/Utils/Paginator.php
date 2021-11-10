<?php

namespace App\Utils;

use Telegram\Bot\Keyboard\Keyboard;



class Paginator
{




    public function getFilterKeybord($shopName, $params)
    {
        $callback_data = $params;

        $keybord[] = ['text' => $shopName, 'callback_data' => http_build_query($callback_data)];

        return $keybord;
    }

    public function getShopKeybord($paginator, $params)
    {
        return $this->getKeybord($paginator, $params, '', null, false, true);
    }

    public function getKeybord($paginator, $params, $filterName = '', $filterShopId = null, $withFilter = true, $isShop = false)
    {
        $callback_data = $params;
        $filter = $params;
        $filter['shop_id'] = $filterShopId;


        $menu = $callback_data;
        $menu['action'] = 'menuBack';

        $i = 0;
        $inlineLayout = [];
        $pages = $this->getPagesArr($paginator);
        if ($isShop) {
            $shopsCallback['action'] = 'shopsMenu';
            $inlineLayout[$i++][] = Keyboard::inlineButton(['text' => "\xE2\xAC\x85 Меню магазинов", 'callback_data' => http_build_query($shopsCallback)]);
        } else {
            $categoriesCallback['action'] = 'categoriesMenu';

            $inlineLayout[$i++][] = Keyboard::inlineButton(['text' => "\xE2\xAC\x85 Меню категорий", 'callback_data' => http_build_query($categoriesCallback)]);
        }


        $inlineLayout[$i++][] = Keyboard::inlineButton(['text' =>  "\xE2\xAC\x86 Главное Меню", 'callback_data' => http_build_query($menu)]);

        if ($withFilter) {
            $inlineLayout[$i++][] = Keyboard::inlineButton(['text' =>  $filterName, 'callback_data' => http_build_query($filter)]);
        }


        // if ($pages['prev']) {
        //     $callback_data['page'] = $pages['prev'];
        //     $inlineLayout[$i][] = Keyboard::inlineButton(['text' => "\xE2\xAC\x85 Назад", 'callback_data' => http_build_query($callback_data)]);
        // }
        if ($pages['next']) {
            $callback_data['page'] = $pages['next'];
            $inlineLayout[$i][] = Keyboard::inlineButton(['text' => "Смотреть еще \xE2\x9E\xA1", 'callback_data' => http_build_query($callback_data)]);
        }

        $keyboard = Keyboard::make([
            'inline_keyboard' => $inlineLayout
        ]);
        return $keyboard;
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
}
