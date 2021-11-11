<?php

namespace App\Utils;

#use App\Utils\UserSession;

class UrlConverter
{
    public function getInnerUrl($url, $userId, $couponId)
    {

        if (strpos($url, '?') === false) {
            $url .= "?subid=coupon";
        } else {
            $url .= "&subid=coupon";
        }

        $url .= "&inner_user_id=$userId";
        $url .= "&inner_coupon_id=$couponId";

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


    public function getParamFromUrl($url, $key)
    {
        $urlArr = parse_url($url);
        $query =  $urlArr['query'];
        parse_str($query, $queryArr);
        $result = isset($queryArr[$key]) ? $queryArr[$key] : null;
        return $result;
    }
}
