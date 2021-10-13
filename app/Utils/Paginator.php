<?php

namespace App\Utils;



class Paginator
{
    public function getKeybord($paginator, $params)
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
