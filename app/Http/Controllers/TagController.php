<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Tag;

class TagController extends Controller
{
    public function store(TagRequest $request)
    {
        Tag::create($request->validated());

        return redirect()->route('admin.index');
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(TagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return redirect()->route('admin.index');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()->route('admin.index');
    }
}
