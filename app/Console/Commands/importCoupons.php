<?php

namespace App\Console\Commands;

use Goutte\Client;
use App\Models\Coupon;
use App\Models\Source;
use Illuminate\Console\Command;

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
            Source::updateOrCreate(
                ['title' => $shop['name'], 'type' => 'shop'],
                ['url' => $shop['url'], 'logo' => $shop['logo']]
            );
            $updatedIds[] = $shop['name'];
        }
        Source::where('type', 'shop')->whereNotIn('title', $updatedIds)->delete();
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

        //dd('1');


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
                $coupons[] = [
                    'shop_name' => $shops[$advcampaign_id]['name'],
                    'shop_site' => $shops[$advcampaign_id]['site'],
                    'id' => $node->attr('id'),
                    'name' => $node->filter('name')->text(),
                    'date_start' => $node->filter('date_start')->text(''),
                    'date_end' => $node->filter('date_end')->text(''),
                    'promocode' => $node->filter('promocode')->text(''),
                    'gotolink' => $node->filter('gotolink')->text(''),
                    'advcampaign_id' => $advcampaign_id,
                    'rating' => $node->filter('rating')->text(),
                    'logo' => $node->filter('logo')->text(),
                    'description' => $node->filter('description')->text(''),
                ];
            });

            $updatedIds = [];
            foreach ($coupons as $coupon) {
                Coupon::updateOrCreate(
                    ['source_id' => $source->id, 'coupon_id' => $coupon['id']],
                    ['type' => $source->type, 'data' => json_encode($coupon)]
                );
                $updatedIds[] = $coupon['id'];
            }

            Coupon::where('source_id', $source->id)->whereNotIn('coupon_id', $updatedIds)->delete();
        }




        //dd($coupons);

        return 0;
    }
}
