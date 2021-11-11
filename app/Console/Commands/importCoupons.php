<?php

namespace App\Console\Commands;

use Goutte\Client;
use App\Models\Logo;
use App\Models\Shop;
use App\Models\Coupon;
use App\Models\Source;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
// use Illuminate\Support\Facades\URL;
// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Storage;
//use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Console\Command;
use Carbon\Exceptions\InvalidFormatException;

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
        //Image::configure(array('driver' => 'imagick'));
        $this->logosArr = $this->getLogos();
        $this->logosPath = storage_path('app/public/logo/');
        $this->shops = [];
    }


    public function convertUrl($oldUrl)
    {

        // $url = str_replace("/g/", "/tpt/", $oldUrl);

        // if (strpos($url, '?') === false) {
        //     $url .= "?user_agent=" . urlencode("TelegramBot (like TwitterBot)");
        // } else {
        //     $url .= "&user_agent=" . urlencode("TelegramBot (like TwitterBot)");
        // }
        // //$url .= '&country_code=RU';
        // $url .= "&referer=coupon-bot";

        // $response = Http::get($url)->json();
        // if (isset($response[0])) {
        //     return  $response[0];
        // }

        return null;
    }


    public function importSorces()
    {
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


    public function getLogos()
    {
        $logoS = Logo::all()->pluck('id', 'old_url');
        return  $logoS;
    }

    public function convertImg($oldUrl)
    {

        if (isset($this->logosArr[$oldUrl])) {
            return $this->logosArr[$oldUrl];
        }

        $pathParts = pathinfo($oldUrl);
        $fileName = time() . "_" . $pathParts['filename'] . '.' . $pathParts['extension'];

        $path = $this->logosPath . $fileName;
        $contents = file_get_contents($oldUrl);
        file_put_contents($path, $contents);

        $newUrl = "/storage/logo/{$fileName}";
        $logo = new Logo;
        $logo->old_url = $oldUrl;
        $logo->new_url = $newUrl;
        $logo->save();

        $this->logosArr[$oldUrl] = $logo->id;

        return $logo->id;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function allImagesSvgToPng()
    {
        $files = array_diff(scandir($this->logosPath), array('..', '.'));
        foreach ($files as $file) {
            $pathParts = pathinfo($file);
            if (!($pathParts['extension'] == 'svg')) continue;

            $newFilePath = $this->logosPath . $pathParts['filename'] . '.png';

            $filePath = $this->logosPath . $file;
            $output = shell_exec("inkscape $filePath -e $newFilePath");
            dump([$filePath, $output]);
            shell_exec("rm -f $filePath");
        }
    }

    public function getDbShops()
    {
        $shops = Shop::all()->pluck('advcampaign_id', 'id');
        return $shops;
    }

    public function getShopId($advcampaign_id, $shopArr)
    {
        if (isset($this->shops[$advcampaign_id])) {
            return $this->shops[$advcampaign_id];
        }

        $shop = Shop::firstOrCreate(
            ['advcampaign_id' => $advcampaign_id],
            ['name' => $shopArr['name']]
        );

        $this->shops[$advcampaign_id] = $shop->id;
        return $shop->id;
    }


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

        $this->shops = $this->getDbShops();

        $time_start = microtime(true);
        $client = new Client();

        $sources = Source::get();
        $sourcesIds = $sources->pluck('id');


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
            $crawler->filter('coupon')->each(function ($node) use (&$coupons, $shops, $source) {
                $advcampaign_id = $node->filter('advcampaign_id')->text();
                $old_logo  = $node->filter('logo')->text();
                $oldGotolink = $node->filter('gotolink')->text('');

                $gotolink = $this->convertUrl($oldGotolink);
                $logo_id = $this->convertImg($old_logo);
                dump([$oldGotolink, $old_logo]);
                if (!$oldGotolink) {
                    return;
                }

                $shop_id = $this->getShopId($advcampaign_id, $shops[$advcampaign_id]);

                $date_start = $node->filter('date_start')->text('');


                $date_end = $node->filter('date_end')->text('');


                try {
                    $date_start = Carbon::parse($date_start);
                } catch (InvalidFormatException  $e) {
                    $date_start = now();
                }

                try {
                    $date_end = Carbon::parse($date_end);
                } catch (InvalidFormatException  $e) {
                    $date_end = null;
                }

                $descr = $node->filter('description')->text('');
                $coupons[] = [
                    'shop_id' => $shop_id,
                    'shop_name' => $shops[$advcampaign_id]['name'],
                    'shop_site' => $shops[$advcampaign_id]['site'],
                    'id' => $node->attr('id'),
                    'name' => $node->filter('name')->text(),
                    'date_start' =>  $date_start,
                    'date_end' =>  $date_end,
                    'promocode' => $node->filter('promocode')->text(''),
                    'oldGotolink' => $oldGotolink,
                    'gotolink' => $gotolink,
                    'advcampaign_id' => $advcampaign_id,
                    'rating' => $node->filter('rating')->text(),
                    'description' =>  Str::limit($descr,  120),

                    'logo_id' => $logo_id,
                    'old_logo' => $old_logo,
                ];
            });
            $now = now();
            $updatedIds = [];
            $insertData = [];
            foreach ($coupons as $coupon) {

                // Coupon::updateOrCreate(
                //     ['source_id' => $source->id, 'outher_coupon_id' => $coupon['id']],
                //     ['type' => $source->type, 'logo_id' => $coupon['logo_id'], 'advcampaign_id' => $coupon['advcampaign_id'], 'data' => json_encode($coupon)]
                // );
                $insertData[] = [
                    'source_id' => $source->id,
                    'outher_coupon_id' => $coupon['id'],
                    'type' => $source->type,
                    'logo_id' => $coupon['logo_id'],
                    'shop_id' => $coupon['shop_id'],
                    'date_start' =>   $coupon['date_start'],
                    'date_end' =>  $coupon['date_end'],
                    'advcampaign_id' => $coupon['advcampaign_id'],
                    'data' => json_encode($coupon)
                ];
                $updatedIds[] = $coupon['id'];
            }

            $chunks = array_chunk($insertData, 50);
            foreach ($chunks as $data) {
                Coupon::upsert($data, ['source_id', 'outher_coupon_id'], ['data']);
            }

            Coupon::where('source_id', $source->id)->whereNotIn('outher_coupon_id', $updatedIds)->delete();
        }

        Coupon::whereNotIn('source_id', $sourcesIds)->delete();

        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        dump($execution_time);

        $this->allImagesSvgToPng();

        dump('import coupons done!!');

        return 0;
    }
}
