<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StateResource;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StateController extends Controller
{
    public function index() {
        $state = State::orderBy('state_id', 'ASC')->get();
        return new StateResource(true, 'List Data State', $state);
    }

    public function show($id) {
        $state = State::find($id);
        return new StateResource(true, 'List Data State', $state);
    }

    private function validateState(Request $request){
        return Validator::make($request->all(),[
            'name' => 'required'
        ], [
            'name' => 'Name must be filled'
        ]);
    }

    public function store(Request $request) {
        $validator = $this->validateState($request);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $state = State::create([
            'name' => $request->name
        ]);
        return new StateResource(true, 'State Data Successfully Added', $state);
    }

    public function update(Request $request, $id) {
        $validator = $this->validateState($request);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $state = State::find($id);
        $state->update([
            'name' => $request->name
        ]);
        return new StateResource(true, 'State Data Successfully Updated', $state);
    }

    public function destroy($id) {
        $state = State::fin($id);
        $state->delete();
        return new StateResource(true, 'List Data State', []);
    }
}
