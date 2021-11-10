<?php

namespace App\Http\Controllers;


use Inertia\Inertia;
use App\Models\Activity;

class ActivitiesController extends Controller
{
    public function index()
    {
        $direction =  request('direction', 'asc');
        $sort =  request('sort', 'id');
        $perPage =  request('perPage', 5);

        return Inertia::render('Activities/Index', [
            'items' => Activity::with('user')->sort($sort, $direction)->paginate($perPage)->withQueryString(),
        ]);
    }
}
