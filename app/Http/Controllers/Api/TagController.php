<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tag::query();
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        $tags = $query->orderBy('tag_id', 'ASC')->get();
        $formattedTags = $tags->map(function ($tag) {
            return [
                'id' => $tag->tag_id,
                'type' => $tag->type,
                'name' => $tag->name_tag,
            ];
        });
        return new TagResource(true, 'List Tag Data', $formattedTags);
    }


    public function show($id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }
        return new TagResource(true, 'Detail Tag Data', [
            'id' => $tag->tag_id,
            'type' => $tag->type,
            'name' => $tag->name_tag
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();
        $type = $request->query('type', $data['type'] ?? null);
        $tag = Tag::create([
            'type' => $type,
            'name_tag' => $data['name_tag'],
        ]);
        return new TagResource(true, 'Tag auccessfully added', [
            'id' => $tag->tag_id,
            'type' => $tag->type,
            'name' => $tag->name_tag
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->json()->all();
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }
        $tag->update([
            'type' => $data['type'],
            'name_tag' => $data['name_tag'],
        ]);
        return new TagResource(true, 'Tag successfully updated', [
            'id' => $tag->tag_id,
            'type' => $tag->type,
            'name' => $tag->name_tag
        ]);
    }

    public function destroy($id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }
        $tag->delete();
        return new TagResource(true, 'Tag deleted success', []);
    }
}
