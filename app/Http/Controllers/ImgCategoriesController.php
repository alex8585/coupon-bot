<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use League\Glide\Server;
use App\Models\ImgCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use App\Http\Requests\ImgCategoryStoreRequest;
use App\Http\Requests\ImgCategoryUpdateRequest;

class ImgCategoriesController extends Controller
{
    public function index()
    {
        $direction =  request('direction', 'asc');
        $sort =  request('sort', 'id');
        $perPage =  request('perPage', 5);
        $items = ImgCategory::filter(request()->only('search'))
            ->sort($sort, $direction)
            ->paginate($perPage)->withQueryString();
        //dd($items);
        return inertia(
            'ImgCategories/Index',
            [
                'filters' => request()->all('search'),
                'items' => $items,
            ]
        );
    }


    public function store(ImgCategoryStoreRequest $request)
    {

        ImgCategory::create($request->validated());
        return back()->with('success', "The Img Category '{$request->name}' has been created.");
    }


    public function update(ImgCategory $imgCategory, ImgCategoryUpdateRequest $request)
    {
        $imgCategory->update($request->validated());
        return back()->with('success', "The Img Category '{$imgCategory->name}' has been updated.");
    }


    public function destroy(ImgCategory $imgCategory)
    {
        $imgCategory->delete();
        return back()->with('success', "The Img Category '{$imgCategory->name}' has been deleted.");
    }
}
