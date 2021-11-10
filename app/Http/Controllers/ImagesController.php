<?php

namespace App\Http\Controllers;

use League\Glide\Server;

class ImagesController extends Controller
{
    public function show(Server $glide, $path)
    {
        $obj = request('obj');

        return $glide->getImageResponse($obj . "/" . $path, request()->all());
    }
}
