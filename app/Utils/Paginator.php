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

    public function getKeybord($paginator, $params, $filterName, $filterShopId = null)
    {
        $callback_data = $params;
        $filter = $params;
        $filter['shop_id'] = $filterShopId;

        $inlineLayout = [];
        $pages = $this->getPagesArr($paginator);
        $inlineLayout[0][] = Keyboard::inlineButton(['text' => $filterName, 'callback_data' => http_build_query($filter)]);

        if ($pages['prev']) {
            $callback_data['page'] = $pages['prev'];
            $inlineLayout[1][] = Keyboard::inlineButton(['text' => 'Предыдущая', 'callback_data' => http_build_query($callback_data)]);
        }
        if ($pages['next']) {
            $callback_data['page'] = $pages['next'];
            $inlineLayout[1][] = Keyboard::inlineButton(['text' => 'Следующая', 'callback_data' => http_build_query($callback_data)]);
        }
        // $inlineLayout[] = $row1;

        // $inlineLayout[] = $row2;
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
