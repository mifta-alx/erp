<?php

namespace App\Http\Controllers\Api;

use App\Models\Image;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ImageResource;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    private function validateImage(Request $request){
        return Validator::make($request->all(), [
            'name' => $request->isMethod('post') ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ],[
            'name.required' => 'Image Must Be Filled',
            'name.image' => 'File Must Be An Image',
            'name.mimes' => 'Images Must Be In jpeg, png, or jpg Format',
            'name.max' => 'Maximum Image Size is 2MB',
        ]);
    }
    public function index()
    {
        $image = Image::get();
        return new ImageResource(true, 'List Image Data', $image);
    }

    public function show($id){
        $image = Image::find($id);
        return new ImageResource(true, 'Detail Image Data', $image);
    }

    public function store(Request $request)
    {
        $validator = $this->validateImage($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = $request->file('name');
        $imageName = $image->hashName();
        $image->storeAs('public/images', $imageName);

        $imageUrl = Storage::url('images/' . $imageName);
        $image = Image::create([
            'name' => $imageName,
        ]);

        return new ImageResource(true, 'Image Successfully Uploaded', ['id'=>$image->image_id,'name'=>$image->name,'url'=>$imageUrl]);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->validateImage($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Vailed',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = Image::find($id);

        if ($request->hasFile('image')) {
            Storage::delete('public/images/' . $image->image);
            $newImageFile = $request->file('image');
            $newImageName = $newImageFile->hashName();
            $newImageFile->storeAs('public/images', $newImageName);
            $image->image = $newImageName;
            $image->save();
        }
        return new ImageResource(true, 'Image Updated Successfully', $image);
    }

    public function destroy($id)
    {
        $image = Image::find($id);
        Storage::delete('public/images/' . $image->image);
        $image->delete();
        return new ImageResource(true, 'Image Deleted Successfully', $image);
    }
}
