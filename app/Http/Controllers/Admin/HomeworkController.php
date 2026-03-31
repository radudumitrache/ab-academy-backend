<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\Homework;
use App\Models\HomeworkSection;
use App\Models\HomeworkSubmission;
use App\Models\Material;
use App\Models\Question;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HomeworkController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    // ── Homework CRUD ─────────────────────────────────────────────────────────

    /**
     * List all homework across all teachers.
     */
    public function index()
    {
        $homework = Homework::with('teacher:id,username')
            ->withCount('allQuestions')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message'  => 'Homework retrieved successfully',
            'count'    => $homework->count(),
            'homework' => $homework,
        ]);
    }

    /**
     * Show a single homework with all sections, questions and signed URLs.
     */
    public function show($id)
    {
        $homework = Homework::with('teacher:id,username')->find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $homework->load([
            'sections.questions.multipleChoiceDetails',
            'sections.questions.gapFillDetails',
            'sections.questions.rephraseDetails',
            'sections.questions.wordFormationDetails',
            'sections.questions.replaceDetails',
            'sections.questions.correctDetails',
            'sections.questions.wordDerivationDetails',
            'sections.questions.textCompletionDetails',
            'sections.questions.correlationDetails',
            'sections.questions.readingQuestionDetails',
            'sections.questions.writingQuestionDetails',
            'sections.questions.speakingQuestionDetails',
        ]);

        $homeworkData = $this->resolveSignedUrls($homework);

        return response()->json([
            'message'  => 'Homework retrieved successfully',
            'homework' => $homeworkData,
        ]);
    }

    /**
     * Create homework. Admin sets the teacher explicitly via homework_teacher field.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'homework_title'       => 'required|string|max:255',
            'homework_description' => 'nullable|string',
            'due_date'             => 'required|date_format:Y-m-d',
            'homework_teacher'     => 'nullable|integer|exists:users,id',
            'people_assigned'      => 'nullable|array',
            'people_assigned.*'    => 'integer|exists:users,id',
            'groups_assigned'      => 'nullable|array',
            'groups_assigned.*'    => 'integer|exists:groups,group_id',
            'status'               => ['sometimes', Rule::in(Homework::STATUSES)],
        ]);

        $validated['homework_teacher'] = $validated['homework_teacher'] ?? Auth::id();
        $validated['date_created']     = now();

        $homework = Homework::create($validated);

        DatabaseLog::logAction('create', Homework::class, $homework->id, "Homework '{$homework->homework_title}' created");

        return response()->json([
            'message'  => 'Homework created successfully',
            'homework' => $homework,
        ], 201);
    }

    /**
     * Update any homework (no ownership restriction).
     */
    public function update(Request $request, $id)
    {
        $homework = Homework::find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'homework_title'       => 'sometimes|string|max:255',
            'homework_description' => 'nullable|string',
            'due_date'             => 'sometimes|date_format:Y-m-d',
            'people_assigned'      => 'nullable|array',
            'people_assigned.*'    => 'integer|exists:users,id',
            'groups_assigned'      => 'nullable|array',
            'groups_assigned.*'    => 'integer|exists:groups,group_id',
            'status'               => ['sometimes', Rule::in(Homework::STATUSES)],
        ]);

        $homework->update($validated);

        DatabaseLog::logAction('update', Homework::class, $homework->id, "Homework '{$homework->homework_title}' updated");

        return response()->json([
            'message'  => 'Homework updated successfully',
            'homework' => $homework->fresh(),
        ]);
    }

    /**
     * Delete any homework. Sections and questions cascade-delete.
     */
    public function destroy($id)
    {
        $homework = Homework::find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $homeworkTitle = $homework->homework_title;
        $homeworkId = $homework->id;
        $homework->delete();

        DatabaseLog::logAction('delete', Homework::class, $homeworkId, "Homework '{$homeworkTitle}' deleted");

        return response()->json(['message' => 'Homework deleted successfully']);
    }

    /**
     * Assign (or replace) students/groups on any homework — no ownership restriction.
     */
    public function assignStudents(Request $request, $id)
    {
        $homework = Homework::find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'people_assigned'   => 'nullable|array',
            'people_assigned.*' => 'integer|exists:users,id',
            'groups_assigned'   => 'nullable|array',
            'groups_assigned.*' => 'integer|exists:groups,group_id',
        ]);

        $homework->update([
            'people_assigned' => $validated['people_assigned'] ?? [],
            'groups_assigned' => $validated['groups_assigned'] ?? [],
        ]);

        DatabaseLog::logAction('update', Homework::class, $homework->id, "Students assigned to homework '{$homework->homework_title}'");

        return response()->json([
            'message'  => 'Students assigned successfully',
            'homework' => $homework->fresh(),
        ]);
    }

    // ── Sections ──────────────────────────────────────────────────────────────

    public function sectionIndex($homeworkId)
    {
        if (!Homework::find($homeworkId)) {
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

    public function sectionStore(Request $request, $homeworkId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'section_type'        => ['required', Rule::in(HomeworkSection::TYPES)],
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

        $section = HomeworkSection::create([
            'homework_id'       => $homeworkId,
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

        DatabaseLog::logAction('create', HomeworkSection::class, $section->id, "Section created for homework #{$homeworkId}");

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section,
        ], 201);
    }

    public function sectionUpdate(Request $request, $homeworkId, $sectionId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($sectionId);
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

        DatabaseLog::logAction('update', HomeworkSection::class, $section->id, "Section #{$sectionId} updated for homework #{$homeworkId}");

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section->fresh(),
        ]);
    }

    public function sectionDestroy($homeworkId, $sectionId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->delete();

        DatabaseLog::logAction('delete', HomeworkSection::class, $sectionId, "Section #{$sectionId} deleted from homework #{$homeworkId}");

        return response()->json(['message' => 'Section deleted successfully']);
    }

    // ── Submissions (view only) ───────────────────────────────────────────────

    /**
     * List all student submissions for a homework.
     * File responses include a signed GCS download URL.
     */
    public function submissions($homeworkId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submissions = HomeworkSubmission::where('homework_id', $homeworkId)
            ->with(['student:id,username,email', 'responses'])
            ->get();

        // Attach signed URLs for any file-based responses
        $submissions->each(function ($submission) {
            $submission->responses->each(function ($response) {
                try {
                    $response->file_url = $response->file_path
                        ? $this->gcs->signedUrl($response->file_path, 60)
                        : null;
                } catch (\Throwable) {
                    $response->file_url = null;
                }
                try {
                    $response->correction_file_url = $response->correction_file_path
                        ? $this->gcs->signedUrl($response->correction_file_path, 60)
                        : null;
                } catch (\Throwable) {
                    $response->correction_file_url = null;
                }
            });
        });

        return response()->json([
            'message'     => 'Submissions retrieved successfully',
            'count'       => $submissions->count(),
            'submissions' => $submissions,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveSignedUrls(Homework $homework): array
    {
        $materialIds = [];
        foreach ($homework->sections as $section) {
            foreach ((array) $section->instruction_files as $id) {
                $materialIds[] = $id;
            }
            if ($section->audio_material_id) {
                $materialIds[] = $section->audio_material_id;
            }
            foreach ($section->questions as $question) {
                foreach ((array) $question->instruction_files as $id) {
                    $materialIds[] = $id;
                }
            }
        }

        $signedUrls = [];
        if (!empty($materialIds)) {
            $materials = Material::whereIn('material_id', array_unique($materialIds))->get()->keyBy('material_id');
            foreach ($materials as $mid => $material) {
                try {
                    $signedUrls[$mid] = $this->gcs->signedUrl($material->gcs_path, 60);
                } catch (\Throwable) {
                    $signedUrls[$mid] = null;
                }
            }
        }

        $data = $homework->toArray();
        foreach ($data['sections'] as &$sectionData) {
            $sectionData['instruction_file_urls'] = array_map(
                fn ($id) => ['material_id' => $id, 'url' => $signedUrls[$id] ?? null],
                (array) ($sectionData['instruction_files'] ?? [])
            );
            if (!empty($sectionData['audio_material_id'])) {
                $sectionData['audio_url_signed'] = $signedUrls[$sectionData['audio_material_id']] ?? null;
            }
            foreach ($sectionData['questions'] as &$questionData) {
                $questionData['instruction_file_urls'] = array_map(
                    fn ($id) => ['material_id' => $id, 'url' => $signedUrls[$id] ?? null],
                    (array) ($questionData['instruction_files'] ?? [])
                );
            }
            unset($questionData);
        }
        unset($sectionData);

        return $data;
    }
}
