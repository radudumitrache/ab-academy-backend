<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TestSectionController extends Controller
{
    private function findOwnedTest($testId)
    {
        return Test::where('test_teacher', Auth::id())->find($testId);
    }

    /**
     * List all sections of a test (with question counts).
     */
    public function index($testId)
    {
        $test = $this->findOwnedTest($testId);
        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $sections = TestSection::where('test_id', $testId)
            ->withCount('questions')
            ->orderBy('order')
            ->get();

        return response()->json([
            'message'  => 'Sections retrieved successfully',
            'sections' => $sections,
        ]);
    }

    /**
     * Create a section on a test owned by the teacher.
     *
     * section_type: GrammarAndVocabulary | Writing | Reading | Listening | Speaking
     *
     * Reading sections accept:  passage (required)
     * Listening sections accept: audio_url (required), transcript (optional)
     * All sections accept: title, instruction_text, instruction_files, order
     */
    public function store(Request $request, $testId)
    {
        $test = $this->findOwnedTest($testId);
        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $validated = $request->validate([
            'section_type'        => ['required', Rule::in(TestSection::TYPES)],
            'title'               => 'nullable|string|max:255',
            'instruction_text'    => 'nullable|string',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'integer|exists:materials,material_id',
            'order'               => 'nullable|integer|min:1',
            // Reading-specific
            'passage'             => 'nullable|string',
            // Listening-specific
            'audio_url'           => 'nullable|url',
            'audio_material_id'   => 'nullable|integer|exists:materials,material_id',
            'transcript'          => 'nullable|string',
        ]);

        $section = TestSection::create([
            'test_id'           => $testId,
            'section_type'      => $validated['section_type'],
            'title'             => $validated['title'] ?? null,
            'instruction_text'  => $validated['instruction_text'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
            'order'             => $validated['order'] ?? null,
            'passage'           => $validated['passage'] ?? null,
            'audio_url'         => $validated['audio_url'] ?? null,
            'audio_material_id' => $validated['audio_material_id'] ?? null,
            'transcript'        => $validated['transcript'] ?? null,
        ]);

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section,
        ], 201);
    }

    /**
     * Update a section. Only the test owner may edit.
     */
    public function update(Request $request, $testId, $sectionId)
    {
        $test = $this->findOwnedTest($testId);
        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $section = TestSection::where('test_id', $testId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $validated = $request->validate([
            'title'               => 'nullable|string|max:255',
            'instruction_text'    => 'nullable|string',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'integer|exists:materials,material_id',
            'order'               => 'nullable|integer|min:1',
            'passage'             => 'nullable|string',
            'audio_url'           => 'nullable|url',
            'audio_material_id'   => 'nullable|integer|exists:materials,material_id',
            'transcript'          => 'nullable|string',
        ]);

        $section->update(array_filter($validated, fn ($v) => !is_null($v)));

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section->fresh(),
        ]);
    }

    /**
     * Delete a section. Questions inside cascade-delete automatically.
     */
    public function destroy($testId, $sectionId)
    {
        $test = $this->findOwnedTest($testId);
        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $section = TestSection::where('test_id', $testId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->delete();

        return response()->json(['message' => 'Section deleted successfully']);
    }
}
