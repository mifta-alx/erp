<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('tag')->orderBy('created_at', 'ASC')->get();
        $customerData = $customers->map(function ($customer) {
            return $this->transformCustomer($customer);
        });

        return new CustomerResource(true, 'List Customer Data', $customerData);
    }

    private function transformCustomer($customer)
    {
        return [
            'id' => $customer->customer_id,
            'company' => $customer->company,
            'type' => $customer->type,
            'name' => $customer->name,
            'street' => $customer->street,
            'city' => $customer->city,
            'state' => $customer->state,
            'zip' => $customer->zip,
            'phone' => $customer->phone,
            'mobile' => $customer->mobile,
            'email' => $customer->email,
            'image_url' => $customer->image_url,
            'image_uuid' => $customer->image_uuid,
            'tags' => $customer->tag->map(function ($tag) {
                return [
                    'id' => $tag->tag_id,
                    'name' => $tag->name_tag
                ];
            }),
            'created_at' => $customer->created_at,
            'updated_at' => $customer->updated_at
        ];
    }

    private function validateCustomer(Request $request)
    {
        return Validator::make($request->all(), [
            'type' => 'required',
            'name' => 'required|string',
            'street' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
            'phone' => 'nullable|string',
            'mobile' => 'required|string',
            'email' => 'nullable|string|email',
            'image_uuid' => 'nullable|string|exists:images,image_uuid',
            'tag_id' => 'array|nullable',
        ], [
            'type.required' => 'Type Must Be Filled',
            'name.nullable' => 'Name Must Be Filled',
            'street.nullable' => 'Street Must Be Filled',
            'city.nullable' => 'City Must Be Filled',
            'state.nullable' => 'State Must Be Filled',
            'zip.nullable' => 'Zip Must Be Filled',
            'phone.nullable' => 'Phone Must Be Filled',
            'mobile.required' => 'Mobile Must Be Filled',
            'email.nullable' => 'Email Must Be Filled',
            'image_uuid.nullable' => 'Image UUID Must Be Filled',
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validator = $this->validateCustomer($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->json()->all();
            //  jika image tidak ditemukan maka null
            // Periksa apakah image_uuid ada dan valid
            $image = isset($data['image_uuid'])
                ? Image::where('image_uuid', $data['image_uuid'])->first()
                : null;

            $imageUrl = $image ? url('/storage/images/' . $image->image) : null;

            $customer = new Customer();
            $customer->fill([
                'company' => $data['company'],
                'type' => $data['type'],
                'name' => $data['name'],
                'street' => $data['street'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'],
                'phone' => $data['phone'],
                'mobile' => $data['mobile'],
                'email' => $data['email'],
                'image_url' => $imageUrl,
                'image_uuid' => $image->image_uuid??null,
            ]);
            $customer->save();

            if (isset($data['tag_id'])) {
                $customer->tag()->sync($data['tag_id']);
            }

            return new CustomerResource(true, 'Customer Data Created', $this->transformCustomer($customer));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $customer = Customer::with('tag')->find($id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
        return new CustomerResource(true, 'Customer Data Retrieved', $this->transformCustomer($customer));
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = $this->validateCustomer($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->json()->all();
            $customer = Customer::find($id);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $image = Image::where('image_uuid', $data['image_uuid'])->first();
            $image = isset($data['image_uuid']) 
            ? Image::where('image_uuid', $data['image_uuid'])->first() 
            : null;


            $customer->fill([
                'company' => $data['company'],
                'type' => $data['type'],
                'name' => $data['name'],
                'street' => $data['street'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'],
                'phone' => $data['phone'],
                'mobile' => $data['mobile'],
                'email' => $data['email'],
                'image_url' => url(Storage::url('images/' . $image->image)) ?? null,
                'image_uuid' => $image->image_uuid??null,
            ]);
            $customer->save();

            if (isset($data['tag_id'])) {
                $customer->tag()->sync($data['tag_id']);
            }

            return new CustomerResource(true, 'Customer Data Updated', $this->transformCustomer($customer));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
        $customer->delete();
        return response()->json([
            'success' => true,
            'message' => 'Customer Data Deleted'
        ]);
    }
}
