<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Join a group using a class code.
     */
    public function joinByCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $group = Group::where('class_code', strtoupper($request->class_code))->first();

        if (!$group) {
            return response()->json(['message' => 'Invalid class code'], 404);
        }

        if ($group->students()->where('student_id', Auth::id())->exists()) {
            return response()->json(['message' => 'You are already in this group'], 409);
        }

        $group->students()->attach(Auth::id());

        return response()->json([
            'message' => 'Joined group successfully',
            'group'   => $group->load('students'),
        ]);
    }
}
