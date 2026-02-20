<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserNotesController extends Controller
{
    /**
     * Get notes for a specific user
     */
    public function getUserNotes($id)
    {
        // Verify user exists
        $user = User::findOrFail($id);
        
        // Get notes with creator information
        $notes = UserNote::where('user_id', $id)
            ->with('creator:id,username,role')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'message' => 'User notes retrieved successfully',
            'user_id' => $id,
            'notes' => $notes
        ]);
    }
    
    /**
     * Save a note for a specific user
     */
    public function saveUserNote(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'content' => 'required|string'
        ]);
        
        // Verify user exists
        $user = User::findOrFail($id);
        
        // Create the note
        $note = UserNote::create([
            'user_id' => $id,
            'created_by' => Auth::id(),
            'content' => $request->content
        ]);
        
        // Load the creator relationship
        $note->load('creator:id,username,role');
        
        return response()->json([
            'message' => 'Note saved successfully',
            'note' => $note
        ], 201);
    }
}
