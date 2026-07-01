<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $categories = Category::all();

        $validated = $request->validated();

        $contactsFilters = $request->only([
            'keyword' => $validated['keyword'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'date' => $validated['date'] ?? null,
        ]);

        $contacts = Contact::with(['category', 'tags'])
            ->search($contactsFilters)
            ->orderBy('created_at', 'desc')
            ->paginate(7)
            ->appends($request->query());

        $tags = Tag::all();

        return view('admin.index', compact('categories', 'contacts', 'tags'));
    }

    public function show(Contact $contact)
    {
        $contact->with(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.index');
    }
}
