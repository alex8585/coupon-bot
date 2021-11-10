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

        $urlArr = parse_url($newUrl);
        $query =  $urlArr['query'];
        parse_str($query, $queryArr);
        $userId = $queryArr['inner_user_id'];

        $newUrl = str_replace("&inner_user_id=$userId", "", $newUrl);


        $activity = [
            'type' => 'url',
            'tguser_id' => $userId,
            'data' => json_encode([
                'url' => $newUrl,
            ])
        ];


        $userSession->saveActivity($activity);

        return redirect($newUrl);
    }
}
