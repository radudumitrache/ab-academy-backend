<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use Illuminate\Http\Request;

class DatabaseLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DatabaseLog::query()->orderBy('created_at', 'desc');

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('model')) {
            $query->where('model', 'like', '%' . $request->model . '%');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);

        return response()->json([
            'message' => 'Database logs retrieved successfully',
            'logs' => $logs,
        ]);
    }

    public function show($id)
    {
        $log = DatabaseLog::with('user')->findOrFail($id);

        return response()->json([
            'message' => 'Database log retrieved successfully',
            'log' => $log,
        ]);
    }
}
