<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Source;
use Illuminate\Support\Str;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Utils\Bot;
use App\Utils\Paginator;
use Illuminate\Support\Facades\Log;
use App\Utils\UpdatesHandler;

class CouponBotController extends Controller
{
    public function __construct()
    {
        $this->updatesHandler = new UpdatesHandler();
    }

    public function webhook()
    {
        $update = Telegram::getWebhookUpdates();

        if (isset($update['callback_query'])) {
            $this->updatesHandler->callbackQuery($update['callback_query']);
        } else if (isset($update['message'])) {
            $this->updatesHandler->commandRespond($update['message']);
        }
    }
}
