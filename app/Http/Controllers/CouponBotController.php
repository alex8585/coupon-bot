<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class CouponBotController extends Controller
{
    public function webhook()
    {
        $updates = Telegram::getWebhookUpdates();

        return $updates;
    }
}
