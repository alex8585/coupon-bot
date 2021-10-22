<?php

namespace App\Utils;

class UrlConverter
{
    public function getInnerUrl($url)
    {

        if (strpos($url, '?') === false) {
            $url .= "?subid=coupon";
        } else {
            $url .= "&subid=coupon";
        }

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
