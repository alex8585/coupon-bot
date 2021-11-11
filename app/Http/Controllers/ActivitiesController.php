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
        $tguser_id = request('tguser_id');
        $page = request('page', 1);
        $query = [
            'direction' => $direction,
            'sort' => $sort,
            'perPage' => $perPage,
            'tguser_id' => $tguser_id,
            'page' => $page
        ];

        $items =  Activity::with(['user', 'category', 'shop', 'catsShop', 'coupon'])
            ->userFilter($tguser_id)->sort($sort, $direction)->paginate($perPage)->withQueryString();
        //->where('shop_id', "!=", null)
        //dd($items->toArray()['data'][4]);
        return Inertia::render('Activities/Index', [
            'items' => $items,
            'query' => $query
        ]);
    }
}
