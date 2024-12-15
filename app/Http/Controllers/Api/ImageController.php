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
    private function validateImage(Request $request)
    {
        return Validator::make($request->all(), [
            'image' => $request->isMethod('post') ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'image.required' => 'Image Must Be Filled',
            'image.image' => 'File Must Be An Image',
            'image.mimes' => 'Images Must Be In jpeg, png, or jpg Format',
            'image.max' => 'Maximum Image Size is 2MB',
        ]);
    }

    public function index()
    {
        $images = Image::orderBy('image_id', 'ASC')->get();
        $imageData = $images->map(function ($image) {
            return [
                'id' => $image->image_id,
                'uuid' => $image->image_uuid,
                'name' => $image->image,
                'url' => url('images/' . $image->image),
            ];
        });
        return new ImageResource(true, 'List Image Data', $imageData);
    }

    public function show($uuid)
    {
        $image = Image::where('image_uuid', $uuid)->first();
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found'
            ], 404);
        }
        return new ImageResource(true, 'Detail Image Data', [
            'id' => $image->image_id,
            'uuid' => $image->image_uuid,
            'name' => $image->image,
            'url' => url('images/' . $image->image),
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input gambar
        $validator = $this->validateImage($request);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = $request->file('image');
        $imageName = $image->hashName();

        // Memindahkan gambar ke dalam folder public/images
        $image->move(public_path('images'), $imageName);

        // Menyimpan informasi gambar ke database
        $image = Image::create([
            'image_uuid' => $request->image_uuid,
            'image' => $imageName,
        ]);

        // Mengembalikan respon dengan URL gambar yang dapat diakses
        return new ImageResource(true, 'Image Successfully Uploaded', [
            'id' => $image->image_id,
            'uuid' => $image->image_uuid,
            'name' => $image->image,
            'url' => url('images/' . $image->image),
        ]);
    }



    public function update(Request $request, $uuid)
    {
        $validator = $this->validateImage($request);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $image = Image::where('image_uuid', $uuid)->first();
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found'
            ], 404);
        }

        if ($request->hasFile('image')) {

            // Hapus file lama di folder public/images
            $oldImagePath = public_path('images/' . $image->image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }

            // Simpan file baru ke folder public/images
            $newImageFile = $request->file('image');
            $newImageName = $newImageFile->hashName();
            $newImageFile->move(public_path('images'), $newImageName);


            // Update data di database
            $image->image = $newImageName;
            $image->save();
        }

        return new ImageResource(true, 'Image Updated Successfully', [
            'id' => $image->image_id,
            'uuid' => $image->image_uuid,
            'name' => $image->image,
            'url' => url('images/' . $image->image),
        ]);
    }

    public function destroy($uuid)
    {
        $image = Image::where('image_uuid', $uuid)->first();
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found'
            ], 404);
        }
    
        // Hapus file dari folder public/images
        $imagePath = public_path('images/' . $image->image);
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    
        // Hapus data dari database
        $image->delete();
    
        return new ImageResource(true, 'Image Deleted Successfully', []);
    }
    
}
