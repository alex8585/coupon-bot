<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Utils\UrlConverter;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function sale(UrlConverter $urlConverter)
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

        $this->saveUrlActivity($userId, $newUrl);


        return redirect($newUrl);
    }

    private function saveUrlActivity($userId, $newUrl)
    {
        $activity = Activity::create([
            'type' => 'url',
            'tguser_id' => $userId,
            'data' => json_encode([
                'url' => $newUrl,
            ])
        ]);
    }
}
