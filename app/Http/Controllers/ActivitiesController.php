<?php

namespace App\Http\Controllers;


use Inertia\Inertia;
use App\Models\Activity;

class ActivitiesController extends Controller
{
    public function index()
    {
        $direction =  request('direction', 'asc');
        $sort =  request('sort', 'created_at');
        $perPage =  request('perPage', 25);
        $items =  Activity::with(['user', 'category', 'shop', 'catsShop', 'coupon'])
            ->sort($sort, $direction)->paginate($perPage)->withQueryString();
        //->where('shop_id', "!=", null)
        //dd($items->toArray()['data'][4]);
        return Inertia::render('Activities/Index', [
            'items' => $items,
        ]);
    }
}
