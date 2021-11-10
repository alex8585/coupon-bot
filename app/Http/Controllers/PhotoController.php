<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use League\Glide\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use App\Http\Requests\PhotoStoreRequest;
use App\Http\Requests\PhotoUpdateRequest;
use App\Models\ImgCategory;

class PhotoController extends Controller
{
    public function index()
    {
        $direction =  request('direction', 'asc');
        $sort =  request('sort', 'id');
        $perPage =  request('perPage', 5);
        $items = Photo::filter(request()->only('search'))
            ->sort($sort, $direction)->with('categories')
            ->paginate($perPage)->withQueryString();

        $items->getCollection()->transform(function ($item) {
            //$item->imgUrl = $item->imgUrl;
            // $item->fullUrl = $item->fullUrl;
            //$item->thumbnail = $item->thumbnail;

            // $item->tags->transform(function ($tag) {
            //     return $tag->id;
            // })->toArray();

            return $item;
        });

        //dd($items);
        $cats = ImgCategory::all();

        return inertia(
            'Photos/Index',
            [
                'filters' => request()->all('search'),
                'items' => $items,
                'cats' => $cats,
            ]
        );
    }


    public function storeFile()
    {
        if (request()->file('file')->isValid()) {
            $path = request()->file('file')->store(Photo::$obj);

            $params = Photo::$imgParams['img'];

            return [
                "name" => basename($path),
                "imgUrl" => Photo::getUrlByPath($path, $params),
                "fullUrl" => Photo::getUrlByPath($path, []),
            ];
        }
    }



    public function store(PhotoStoreRequest $request)
    {
        $photo = Photo::create($request->validated());

        if (($request->cats)) {
            $catsIds = collect($request->cats)->pluck('id')->all();
            $photo->categories()->attach($catsIds);
        }


        return back()->with('success', "The Tag '{$request->name}' has been created.");
    }


    public function update(Photo $photo, PhotoUpdateRequest $request)
    {
        $photo->update($request->validated());

        $catsIds = collect($request->categories)->pluck('id')->all();
        $photo->categories()->sync($catsIds);



        return back()->with('success', "The Tag '{$photo->name}' has been updated.");
    }


    public function destroy(Photo $photo)
    {
        $photo->delete();
        return back()->with('success', "The Tag '{$photo->name}' has been deleted.");
    }
}
