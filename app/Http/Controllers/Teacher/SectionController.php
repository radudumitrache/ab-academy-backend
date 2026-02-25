<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    private function findOwnedHomework($homeworkId)
    {
        return Homework::where('homework_teacher', Auth::id())->find($homeworkId);
    }

    /**
     * List all sections of a homework (with question counts).
     */
    public function index($homeworkId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $sections = HomeworkSection::where('homework_id', $homeworkId)
            ->withCount('questions')
            ->orderBy('order')
            ->get();

        return response()->json([
            'message'  => 'Sections retrieved successfully',
            'sections' => $sections,
        ]);
    }

    /**
     * Create a section on a homework owned by the teacher.
     *
     * section_type: GrammarAndVocabulary | Writing | Reading | Listening
     *
     * Reading sections accept:  passage (required)
     * Listening sections accept: audio_url (required), transcript (optional)
     * All sections accept: title, instruction_files, order
     */
    public function store(Request $request, $homeworkId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'section_type'        => ['required', Rule::in(HomeworkSection::TYPES)],
            'title'               => 'nullable|string|max:255',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'url',
            'order'               => 'nullable|integer|min:1',
            // Reading-specific
            'passage'             => 'nullable|string',
            // Listening-specific
            'audio_url'           => 'nullable|url',
            'transcript'          => 'nullable|string',
        ]);

        if ($validated['section_type'] === 'Reading' && empty($validated['passage'])) {
            return response()->json(['message' => 'Reading sections require a passage'], 422);
        }

        if ($validated['section_type'] === 'Listening' && empty($validated['audio_url'])) {
            return response()->json(['message' => 'Listening sections require an audio_url'], 422);
        }

        $section = HomeworkSection::create([
            'homework_id'       => $homeworkId,
            'section_type'      => $validated['section_type'],
            'title'             => $validated['title'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
            'order'             => $validated['order'] ?? null,
            'passage'           => $validated['passage'] ?? null,
            'audio_url'         => $validated['audio_url'] ?? null,
            'transcript'        => $validated['transcript'] ?? null,
        ]);

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section,
        ], 201);
    }

    /**
     * Update a section. Only the homework owner may edit.
     */
    public function update(Request $request, $homeworkId, $sectionId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $validated = $request->validate([
            'title'               => 'nullable|string|max:255',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'url',
            'order'               => 'nullable|integer|min:1',
            'passage'             => 'nullable|string',
            'audio_url'           => 'nullable|url',
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
    public function destroy($homeworkId, $sectionId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->delete();

        return response()->json(['message' => 'Section deleted successfully']);
    }
}
