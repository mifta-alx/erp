<?php

namespace App\Http\Controllers\Api;

use App\Models\Material;
use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Models\Image;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::with('category', 'tag')->orderBy('created_at', 'ASC')->get();
        $materialData = $materials->map(function ($material) {
            return [
                'material_id' => $material->material_id,
                'material_name' => $material->material_name,
                'category_id' => $material->category_id,
                'category_name' => $material->category->category,
                // 'sales_price' => number_format($material->sales_price, 2),
                // 'cost' => number_format($material->cost, 2),
                'sales_price' => $material->sales_price,
                'cost' => $material->cost,
                'barcode' => $material->barcode,
                'internal_reference' => $material->internal_reference,
                'tags' => $material->tag->map(function ($tag) {
                    return [
                        'id' => $tag->tag_id,
                        'name' => $tag->name_tag
                    ];
                }),
                'notes' => $material->notes,
                'image_uuid' => $material->image_uuid,
                'image_url' => $material->image_url,
                'stock' => $material->stock,
                'created_at' => $material->created_at,
                'updated_at' => $material->updated_at
            ];
        });

        return new MaterialResource(true, 'List Material Data', $materialData);
    }

    private function validateMaterial(Request $request)
    {
        return Validator::make(
            $request->all(),
            [
                'material_name' => 'required|string',
                'category_id' => 'required',
                'sales_price' => 'required|numeric',
                'cost' => 'required|numeric',
                'image_uuid' => 'required|string|exists:images,image_uuid'
            ],
            [
                'material_name.required' => 'Material Name Must Be Filled',
                'category_id.required' => 'Category Must Be Filled',
                'sales_price.required' => 'Sales Price Must Be filled',
                'cost.required' => 'Cost Must Be filled',
                'image_uuid.required' => 'Image Must Be Filled',
            ]
        );
    }

    public function store(Request $request)
    {
        try {
            $data = $request->json()->all();
            $validator = $this->validateMaterial($request);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $image = Image::where('image_uuid', $data['image_uuid'])->first();
            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }

            $imageUrl = url('/storage/images/' . $image->image);

            $material = Material::create([
                'material_name' => $data['material_name'],
                'category_id' => $data['category_id'],
                'sales_price' => $data['sales_price'],
                'cost' => $data['cost'],
                'barcode' => $data['barcode'],
                'internal_reference' => $data['internal_reference'],
                'notes' => $data['notes'],
                'image_uuid' => $image->image_uuid,
                'image_url' => $imageUrl,
                'stock' => $data['stock'] ?? 0,
            ]);

            $material->tag()->sync($data['tags']);

            $materialWithTag = Material::with('tag')->find($material->material_id);

            return new MaterialResource(true, 'Material Data Successfully Added', [
                'material_id' => $materialWithTag->material_id,
                'material_name' => $materialWithTag->material_name,
                'category_id' => $materialWithTag->category_id,
                'sales_price' => $materialWithTag->sales_price,
                'cost' => $materialWithTag->cost,
                'barcode' => $materialWithTag->barcode,
                'internal_reference' => $materialWithTag->internal_reference,
                'notes' => $materialWithTag->notes,
                'tags' => $materialWithTag->tag->map(function ($tag) {
                    return [
                        'id' => $tag->tag_id,
                        'name' => $tag->name_tag,
                    ];
                }),
                'image_uuid' => $materialWithTag->image_uuid,
                'image_url' => $materialWithTag->image_url,
                'stock' => $materialWithTag->stock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        $material = Material::find($id);
        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found'
            ], 404);
        }
        return new MaterialResource(true, 'Detail Material Data', [
            'material_id' => $material->material_id,
            'material_name' => $material->material_name,
            'category_id' => $material->category_id,
            'category_name' => $material->category->category,
            // 'sales_price' => number_format($material->sales_price, 2),
            // 'cost' => number_format($material->cost, 2),
            'sales_price' => $material->sales_price,
            'cost' => $material->cost,
            'barcode' => $material->barcode,
            'internal_reference' => $material->internal_reference,
            'tags' => $material->tag->map(function ($tag) {
                return [
                    'id' => $tag->tag_id,
                    'name' => $tag->name_tag
                ];
            }),
            'notes' => $material->notes,
            'image_uuid' => $material->image_uuid,
            'image_url' => $material->image_url,
            'stock' => $material->stock,
            'created_at' => $material->created_at,
            'updated_at' => $material->updated_at
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->json()->all();
            $validator = $this->validateMaterial($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $material = Material::find($id);
            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material not found'
                ], 404);
            }

            $imageUuid = $data['image_uuid'] ?? $material->image_uuid;

            $material->update([
                'material_name' => $data['material_name'],
                'category_id' => $data['category_id'],
                'sales_price' => $data['sales_price'],
                'cost' => $data['cost'],
                'barcode' => $data['barcode'],
                'internal_reference' => $data['internal_reference'],
                'notes' => $data['notes'],
                'image_uuid' => $imageUuid,
                'image_url' => $data['image_url'],
                'stock' => $data['stock'] ?? 0,
            ]);

            $material->tag()->sync($data['tags']);

            $materialWithTag = Material::with('tag')->find($material->material_id);

            return new MaterialResource(true, 'Material Data Successfully Updated', [
                'material_id' => $materialWithTag->material_id,
                'material_name' => $materialWithTag->material_name,
                'category_id' => $materialWithTag->category_id,
                // 'sales_price' => number_format($materialWithTag->sales_price, 2),
                // 'cost' => number_format($materialWithTag->cost, 2),
                'sales_price' => $materialWithTag->sales_price,
                'cost' => $materialWithTag->cost,
                'barcode' => $materialWithTag->barcode,
                'internal_reference' => $materialWithTag->internal_reference,
                'notes' => $materialWithTag->notes,
                'tags' => $materialWithTag->tag->map(function ($tag) {
                    return [
                        'id' => $tag->tag_id,
                        'name' => $tag->name_tag,
                    ];
                }),
                'image_uuid' => $materialWithTag->image_uuid,
                'image_url' => $materialWithTag->image_url,
                'stock' => $materialWithTag->stock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $material = Material::find($id);
        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found'
            ], 404);
        }
        $material->delete();
        return new MaterialResource(true, 'Data Deleted Successfully', []);
    }
}
