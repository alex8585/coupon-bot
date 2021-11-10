<?php

namespace App\Utils;

#use App\Utils\UserSession;

class UrlConverter
{
    public function getInnerUrl($url, $userId)
    {

        if (strpos($url, '?') === false) {
            $url .= "?subid=coupon";
        } else {
            $url .= "&subid=coupon";
        }

        $url .= "&inner_user_id=$userId";


        $url = urlencode(base64_encode($url));

        $domain = env('REDIRECT_HOST');
        $domain = rtrim($domain, '/');

        $newUrl = "{$domain}/sale?u={$url}";

        return $newUrl;
    }

    public function getOuterUrl($url)
    {
        $url = base64_decode(urldecode($url));
        return  $url;
    }
}
