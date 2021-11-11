<?php

namespace App\Http\Controllers;


use App\Utils\UserSession;
use App\Utils\UrlConverter;

class RedirectController extends Controller
{
    public function sale(UrlConverter $urlConverter, UserSession  $userSession)
    {
        $url = request('u');
        if (!$url) {
            return 'Something went wrong';
        }

        $newUrl = $urlConverter->getOuterUrl($url);

        $userId = $urlConverter->getParamFromUrl($newUrl, 'inner_user_id');
        $couponId = $urlConverter->getParamFromUrl($newUrl, 'inner_coupon_id');

        $activity = [
            'type' => 'url',
            'tguser_id' => $userId,
            'coupon_id' =>  $couponId,
        ];
        $userSession->saveActivity($activity);


        $newUrl = str_replace("&inner_user_id=$userId", "", $newUrl);
        $newUrl = str_replace("&inner_coupon_id=$couponId", "", $newUrl);
        //dd([$newUrl, $userId, $couponId]);

        return redirect($newUrl);
    }
}
