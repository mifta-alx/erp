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
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'vendor_type' => $vendor->vendor_type, 
                'vendor_street' => $vendor->street,
                'vendor_city' => $vendor->city,
                'vendor_state' => $vendor->state,
                'vendor_zip' => $vendor->zip,
                'vendor_phone' => $vendor->phone,
                'vendor_mobile' => $vendor->mobile,
                'vendor_email' => $vendor->email,
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
            'vendor_name' => 'required|string',
            'vendor_type' => 'required|string', 
            'vendor_email' => ['required', 'email', 'unique:vendors,email,' . $id],
            'vendor_phone' => 'required|string',
            'vendor_mobile' => 'required',
            'vendor_street' => 'required',
            'vendor_city' => 'required',
            'vendor_state' => 'required',
            'vendor_zip' => 'required',

        ], [
            'vendor_name.required' => 'Vendor Name Must Be Filled',
            'vendor_type.required' => 'Vendor Type Must Be Filled',
            'vendor_email.required' => 'Email Must Be Filled',
            'vendor_phone.required' => 'Phone Must Be Filled',
            'vendor_street.required' => 'Street Must Be Filled',
            'vendor_city.required' => 'City Must Be Filled',
            'vendor_state.required' => 'State Must Be Filled',
            'vendor_zip.required' => 'Zip Must Be Filled',
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
            'name' => $data['vendor_name'],
            'vendor_type' => $data['vendor_type'],
            'street' => $data['vendor_street'],
            'city' => $data['vendor_city'],
            'state' => $data['vendor_state'],
            'zip' => $data['vendor_zip'],
            'phone' => $data['vendor_phone'],
            'mobile' => $data['vendor_mobile'],
            'email' => $data['vendor_email'],
            'image_uuid' => $image ? $image->image_uuid : null,
            'image_url' => $imageUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor Data Successfully Added',
            'data' => [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'vendor_type' => $vendor->vendor_type,
                'vendor_street' => $vendor->street,
                'vendor_city' => $vendor->city,
                'vendor_state' => $vendor->state,
                'vendor_zip' => $vendor->zip,
                'vendor_phone' => $vendor->phone,
                'vendor_mobile' => $vendor->mobile,
                'vendor_email' => $vendor->email,
                'image_uuid' => $vendor->image_uuid,
                'image_url' => $vendor->image_url,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at
            ]
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
            'data' => [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'vendor_type' => $vendor->vendor_type,
                'vendor_street' => $vendor->street,
                'vendor_city' => $vendor->city,
                'vendor_state' => $vendor->state,
                'vendor_zip' => $vendor->zip,
                'vendor_phone' => $vendor->phone,
                'vendor_mobile' => $vendor->mobile,
                'vendor_email' => $vendor->email,
                'image_uuid' => $vendor->image_uuid,
                'image_url' => $vendor->image_url,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at
            ]
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
            'name' => $data['vendor_name'],
            'vendor_type' => $data['vendor_type'], 
            'street' => $data['vendor_street'],
            'city' => $data['vendor_city'],
            'state' => $data['vendor_state'],
            'zip' => $data['vendor_zip'],
            'phone' => $data['vendor_phone'],
            'mobile' => $data['vendor_mobile'],
            'email' => $data['vendor_email'],
            'image_uuid' => $image ? $image->image_uuid : $vendor->image_uuid,
            'image_url' => $imageUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor Data Successfully Updated',
            'data' => [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'vendor_type' => $vendor->vendor_type,
                'vendor_street' => $vendor->street,
                'vendor_city' => $vendor->city,
                'vendor_state' => $vendor->state,
                'vendor_zip' => $vendor->zip,
                'vendor_phone' => $vendor->phone,
                'vendor_mobile' => $vendor->mobile,
                'vendor_email' => $vendor->email,
                'image_uuid' => $vendor->image_uuid,
                'image_url' => $vendor->image_url,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at
            ]
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
