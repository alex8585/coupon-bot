<?php

namespace App\Http\Controllers;


use Inertia\Inertia;
use App\Models\TgUser;


class UsersController extends Controller
{
    public function index()
    {
        $direction =  request('direction', 'asc');
        $sort =  request('sort', 'id');
        $perPage =  request('perPage', 5);

        return Inertia::render('Users/Index', [

            'items' => TgUser::paginate($perPage)->withQueryString(),
        ]);
    }
}
