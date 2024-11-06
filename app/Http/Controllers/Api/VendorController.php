<?php

namespace App\Http\Controllers\Api;

use App\Models\Vendor;
use App\Models\Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::orderBy('created_at', 'ASC')->get();
        $vendorData = $vendors->map(function ($vendor) {
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'street' => $vendor->street,
                'city' => $vendor->city,
                'state' => $vendor->state,
                'zip' => $vendor->zip,
                'phone' => $vendor->phone,
                'mobile' => $vendor->mobile,
                'email' => $vendor->email,
                'image_uuid' => $vendor->image_uuid,
                'image_url' => $vendor->image_url,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'List Vendor Data',
            'data' => $vendorData
        ]);
    }

    private function validateVendor(Request $request, $id = null)
    {
        return Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => ['required', 'email', 'unique:vendors,email,' . $id], 
            'phone' => 'required|string',
            'image_uuid' => 'nullable|string|exists:images,image_uuid',
        ], [
            'name.required' => 'Vendor Name Must Be Filled',
            'email.required' => 'Email Must Be Filled',
            'phone.required' => 'Phone Must Be Filled',
            'image_uuid.exists' => 'Image Must Be a Valid UUID'
        ]);
    }    

    public function store(Request $request)
    {
        $data = $request->all();
        $validator = $this->validateVendor($request);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = Image::where('image_uuid', $data['image_uuid'])->first();
        $imageUrl = $image ? url('/storage/images/' . $image->image) : null;

        $vendor = Vendor::create([
            'name' => $data['name'],
            'street' => $data['street'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'],
            'phone' => $data['phone'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'image_uuid' => $image ? $image->image_uuid : null,
            'image_url' => $imageUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor Data Successfully Added',
            'data' => $vendor
        ]);
    }

    public function show($id)
    {
        $vendor = Vendor::find($id);
        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vendor Data',
            'data' => $vendor
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }
        $validator = $this->validateVendor($request, $id);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = Image::where('image_uuid', $data['image_uuid'])->first();
        $imageUrl = $image ? url('/storage/images/' . $image->image) : $vendor->image_url;

        $vendor->update([
            'name' => $data['name'],
            'street' => $data['street'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'],
            'phone' => $data['phone'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'image_uuid' => $image ? $image->image_uuid : $vendor->image_uuid,
            'image_url' => $imageUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor Data Successfully Updated',
            'data' => $vendor
        ]);
    }

    public function destroy($id)
    {
        $vendor = Vendor::find($id);
        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }
        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor Data Deleted Successfully',
            'data' => []
        ]);
    }
}
