<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $category = Category::latest()->get();
        return new CategoryResource(true, 'List Category Data', $category);
    }
    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = Category::create([
            'category' => $request->category,
        ]);
        return new CategoryResource(true, 'Category Data Successfully Added', $category);
    }
    /**
     * show
     *
     * @param  mixed $id
     * @return void
     */
    public function show($id)
    {
        $category = Category::find($id);
        return new CategoryResource(true, 'Detail Category Data', $category);
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'category' => 'required',
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = Category::find($id);
        $category->update([
            'category' => $request->category,
        ]);
        return new CategoryResource(true, 'Category Data Successfully Changed', $category);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        $category->delete();
        return new CategoryResource(true, 'Data Deleted Successfully', $category);
    }
}
