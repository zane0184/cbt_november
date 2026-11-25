<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Tag::all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'tenant_id' => $request->user()->tenant_id
        ]);

        return response()->json(['success' => true, 'data' => $tag], 201);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return response()->json(['success' => true]);
    }
}
