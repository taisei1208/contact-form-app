<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::all();

        $contactsFilters = $request->only([
            'keyword',
            'gender',
            'category_id',
            'date',
        ]);

        $contacts = Contact::with(['category', 'tags'])
            ->search($contactsFilters)
            ->orderBy('created_at', 'desc')
            ->paginate(7)
            ->appends($request->query());

        $tags = Tag::all();

        return view('admin.index', compact('categories', 'contacts', 'tags'));
    }
}
