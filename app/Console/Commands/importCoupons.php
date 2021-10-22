<?php

namespace App\Console\Commands;

use Goutte\Client;
use App\Models\Logo;
use App\Models\Coupon;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class importCoupons extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import_coupons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        Image::configure(array('driver' => 'imagick'));
    }


    public function convertUrl($oldUrl)
    {
        $url = str_replace("/g/", "/tpt/", $oldUrl);

        if (strpos($url, '?') === false) {
            $url .= "?user_agent=" . urlencode("TelegramBot (like TwitterBot)");
        } else {
            $url .= "&user_agent=" . urlencode("TelegramBot (like TwitterBot)");
        }
        //$url .= '&country_code=RU';
        $url .= "&referer=coupon-bot";

        $response = Http::get($url)->json();
        if (isset($response[0])) {
            return  $response[0];
        }

        return null;
    }


    public function importSorces()
    {
        // $path = Storage::path('');
        // $path =  base_path() . "/" . 'categories.json';
        $cats = config('settings.categories');
        $shops = config('settings.shops');
        $updatedIds = [];
        foreach ($cats  as $cat) {
            Source::updateOrCreate(
                ['title' => $cat['name'], 'type' => 'category'],
                ['url' => $cat['url']]
            );
            $updatedIds[] = $cat['name'];
        }
        Source::where('type', 'category')->whereNotIn('title', $updatedIds)->delete();



        foreach ($shops  as $shop) {

            $client = new Client();
            $crawler = $client->request('GET', $shop['url']);


            $old_logoElem  = $crawler->filter('logo');

            $old_logo  = $old_logoElem->first()->text('');
            if (!$old_logo) {
                continue;
            }


            $logo_id = $this->convertImg($old_logo);

            Source::updateOrCreate(
                ['title' => $shop['name'], 'type' => 'shop'],
                ['url' => $shop['url'], 'logo_id' => $logo_id]
            );
            $updatedIds[] = $shop['name'];
        }
        Source::where('type', 'shop')->whereNotIn('title', $updatedIds)->delete();
    }


    public function convertImg($oldUrl)
    {

        $logo = Logo::where('old_url', $oldUrl)->first();
        if ($logo) {
            return $logo->id;
        }
        $pathParts = pathinfo($oldUrl);
        $fileName = time() . "_" . $pathParts['filename'] . '.jpg';

        $img = Image::make($oldUrl);

        $path = storage_path('app/public/logo/') . $fileName;

        //http://local-coupon-bot.com/storage/logo/bar.jpg

        $img->save($path);

        $newUrl = "/storage/logo/{$fileName}";


        $logo = new Logo;
        $logo->old_url = $oldUrl;
        $logo->new_url = $newUrl;
        $logo->save();

        return $logo->id;
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // <name>Скидка 1000р при заказе от 5000р</name>
        // <advcampaign_id>22298</advcampaign_id>
        // <rating>1.39</rating>
        // <logo>
        // https://cdn.admitad.com/campaign/images/2021/4/6/22298-1558cb37dd7e73e1.svg
        // </logo>
        // <description>Только для новых клиентов</description>
        // <specie_id>1</specie_id>
        // <promocode>SALE1000</promocode>
        // <promolink>
        // http://ad.admitad.com/coupon/zhu49dopfk5b2235ca3be5e8082b2a/
        // </promolink>
        // <gotolink>
        // https://ad.admitad.com/g/7dw4ggkus15b2235ca3be5e8082b2a/?i=3
        // </gotolink>
        // <date_start>2021-09-16 00:00:00</date_start>
        // <date_end>2021-10-31 23:59:00</date_end>
        // <exclusive>false</exclusive>
        // <discount>1 000 ₽</discount>
        // <types>
        // <type_id>2</type_id>
        // </types>
        // <categories>
        // <category_id>1</category_id>
        // </categories>
        // <special_category/>



        $this->importSorces();



        $client = new Client();

        $sources = Source::get();
        foreach ($sources as $source) {
            $crawler = $client->request('GET', $source->url);



            $shops = [];
            $crawler->filter('advcampaign')->each(function ($node) use (&$shops) {
                $id = $node->attr('id');
                $shops[$id] = [
                    'name' => $node->filter('name')->text(),
                    'site' => $node->filter('site')->text(),
                ];
            });



            $coupons = [];
            $crawler->filter('coupon')->each(function ($node) use (&$coupons, $shops) {
                $advcampaign_id = $node->filter('advcampaign_id')->text();
                $old_logo  = $node->filter('logo')->text();
                $oldGotolink = $node->filter('gotolink')->text('');

                $gotolink = $this->convertUrl($oldGotolink);
                $logo_id = $this->convertImg($old_logo);


                $coupons[] = [
                    'shop_name' => $shops[$advcampaign_id]['name'],
                    'shop_site' => $shops[$advcampaign_id]['site'],
                    'id' => $node->attr('id'),
                    'name' => $node->filter('name')->text(),
                    'date_start' => $node->filter('date_start')->text(''),
                    'date_end' => $node->filter('date_end')->text(''),
                    'promocode' => $node->filter('promocode')->text(''),
                    'oldGotolink' => $oldGotolink,
                    'gotolink' => $gotolink,
                    'advcampaign_id' => $advcampaign_id,
                    'rating' => $node->filter('rating')->text(),
                    'description' => $node->filter('description')->text(''),
                    'logo_id' => $logo_id,
                    'old_logo' => $old_logo,
                ];
            });

            $updatedIds = [];
            foreach ($coupons as $coupon) {
                Coupon::updateOrCreate(
                    ['source_id' => $source->id, 'outher_coupon_id' => $coupon['id']],
                    ['type' => $source->type, 'logo_id' => $coupon['logo_id'], 'advcampaign_id' => $coupon['advcampaign_id'], 'data' => json_encode($coupon)]
                );
                $updatedIds[] = $coupon['id'];
            }

            Coupon::where('source_id', $source->id)->whereNotIn('outher_coupon_id', $updatedIds)->delete();
        }




        //dd($coupons);

        return 0;
    }
}
