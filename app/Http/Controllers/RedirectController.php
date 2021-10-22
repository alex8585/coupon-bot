<?php

namespace App\Http\Controllers;

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
        return redirect($newUrl);
    }
}
