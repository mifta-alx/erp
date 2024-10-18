<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::orderBy('tag_id', 'ASC')->get();
        $formattedTags = $tags->map(function ($tag) {
            return [
                'id' => $tag->tag_id,
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
            'name' => $tag->name_tag
        ]);
    }

    public function store(Request $request)
    {
        $tag = Tag::create([
            'name_tag' => $request->name_tag,
        ]);
        return new TagResource(true, 'Tag Successfully Uploaded', [
            'id'=>$tag->tag_id,
            'name'=>$tag->name_tag
        ]);
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }
        $tag->update([
            'name_tag' => $request->name_tag,
        ]);
        return new TagResource(true, 'Product Tag Successfully Updated', [
            'id'=>$tag->tag_id,
            'name'=>$tag->name_tag
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
        return new TagResource(true, 'Data Deleted Successfully', []);
    }
}
