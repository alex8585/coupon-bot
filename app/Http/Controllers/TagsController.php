<?php

namespace App\Http\Controllers;


use App\Models\Tag;
use App\Http\Requests\TagStoreRequest;
use App\Http\Requests\TagUpdateRequest;
use Illuminate\Support\Facades\Request;


class TagsController extends Controller
{
    public function index()
    {
        //$tag = Tag::factory()->count(10)->create();
        $direction =  request('direction', 'asc');
        $sort =  request('sort', 'id');
        $perPage =  request('perPage', 5);

        $tags = Tag::filter(Request::only('search'))
            ->sort($sort, $direction)
            ->paginate($perPage)->withQueryString();


        return inertia(
            'Tags/Index',
            [
                'filters' => Request::all('search'),
                'items' => $tags,
            ]
        );
    }


    public function store(TagStoreRequest $request)
    {
        Tag::create($request->validated());
        return back()->with('success', "The Tag '{$request->name}' has been created.");
    }


    public function update(Tag $tag, TagUpdateRequest $request)
    {
        $tag->update($request->validated());
        return back()->with('success', "The Tag '{$tag->name}' has been updated.");
    }


    public function destroy(Tag $tag)
    {
        $tag->delete();
        return back()->with('success', "The Tag '{$tag->name}' has been deleted.");
    }
}
