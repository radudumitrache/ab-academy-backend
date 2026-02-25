<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Homework;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeworkController extends Controller
{
    /**
     * List all homework created by the authenticated teacher.
     */
    public function index()
    {
        $homework = Homework::where('homework_teacher', Auth::id())
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
     * Show a single homework with all questions, sections and their detail records.
     */
    public function show($id)
    {
        $homework = Homework::where('homework_teacher', Auth::id())->find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $homework->load([
            // Top-level questions with all possible detail relations
            'questions.multipleChoiceDetails',
            'questions.gapFillDetails',
            'questions.rephraseDetails',
            'questions.wordFormationDetails',
            'questions.replaceDetails',
            'questions.correctDetails',
            'questions.wordDerivationDetails',
            'questions.textCompletionDetails',
            'questions.correlationDetails',
            // Reading sections and their questions (including open reading_question type)
            'readingSections.questions.multipleChoiceDetails',
            'readingSections.questions.rephraseDetails',
            'readingSections.questions.readingQuestionDetails',
            // Listening sections and their questions
            'listeningSections.questions.multipleChoiceDetails',
            'listeningSections.questions.textCompletionDetails',
        ]);

        return response()->json([
            'message'  => 'Homework retrieved successfully',
            'homework' => $homework,
        ]);
    }

    /**
     * Create a new homework. The authenticated teacher is automatically set as the owner.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'homework_title'       => 'required|string|max:255',
            'homework_description' => 'nullable|string',
            'due_date'             => 'required|date_format:Y-m-d',
            'people_assigned'      => 'nullable|array',
            'people_assigned.*'    => 'integer|exists:users,id',
            'groups_assigned'      => 'nullable|array',
            'groups_assigned.*'    => 'integer|exists:groups,group_id',
        ]);

        // Validate group ownership
        if (!empty($validated['groups_assigned'])) {
            $ownedGroupIds = Group::where('group_teacher', Auth::id())
                ->pluck('group_id')
                ->toArray();

            $unauthorized = array_diff($validated['groups_assigned'], $ownedGroupIds);
            if (!empty($unauthorized)) {
                return response()->json([
                    'message'            => 'You do not own all of the specified groups',
                    'unauthorized_groups' => array_values($unauthorized),
                ], 422);
            }
        }

        $validated['homework_teacher'] = Auth::id();
        $validated['date_created']     = now();

        $homework = Homework::create($validated);

        return response()->json([
            'message'  => 'Homework created successfully',
            'homework' => $homework,
        ], 201);
    }

    /**
     * Update homework fields. Only the owning teacher may update.
     */
    public function update(Request $request, $id)
    {
        $homework = Homework::where('homework_teacher', Auth::id())->find($id);

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
        ]);

        if (!empty($validated['groups_assigned'])) {
            $ownedGroupIds = Group::where('group_teacher', Auth::id())
                ->pluck('group_id')
                ->toArray();

            $unauthorized = array_diff($validated['groups_assigned'], $ownedGroupIds);
            if (!empty($unauthorized)) {
                return response()->json([
                    'message'             => 'You do not own all of the specified groups',
                    'unauthorized_groups' => array_values($unauthorized),
                ], 422);
            }
        }

        $homework->update($validated);

        return response()->json([
            'message'  => 'Homework updated successfully',
            'homework' => $homework->fresh(),
        ]);
    }

    /**
     * Delete a homework. Only the owning teacher may delete.
     * Questions and their detail records cascade via DB foreign keys.
     */
    public function destroy($id)
    {
        $homework = Homework::where('homework_teacher', Auth::id())->find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $homework->delete();

        return response()->json(['message' => 'Homework deleted successfully']);
    }

    /**
     * Assign (or replace) the student/group list on a homework.
     * Teachers may only assign students that belong to their own groups.
     */
    public function assignStudents(Request $request, $id)
    {
        $homework = Homework::where('homework_teacher', Auth::id())->find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'people_assigned'   => 'nullable|array',
            'people_assigned.*' => 'integer|exists:users,id',
            'groups_assigned'   => 'nullable|array',
            'groups_assigned.*' => 'integer|exists:groups,group_id',
        ]);

        // Verify group ownership
        if (!empty($validated['groups_assigned'])) {
            $ownedGroupIds = Group::where('group_teacher', Auth::id())
                ->pluck('group_id')
                ->toArray();

            $unauthorized = array_diff($validated['groups_assigned'], $ownedGroupIds);
            if (!empty($unauthorized)) {
                return response()->json([
                    'message'             => 'You do not own all of the specified groups',
                    'unauthorized_groups' => array_values($unauthorized),
                ], 422);
            }
        }

        // Verify students belong to the teacher's groups
        if (!empty($validated['people_assigned'])) {
            $teacherGroupIds  = Group::where('group_teacher', Auth::id())->pluck('group_id')->toArray();
            $teacherStudentIds = empty($teacherGroupIds) ? [] : DB::table('group_student')
                ->whereIn('group_id', $teacherGroupIds)
                ->pluck('student_id')
                ->unique()
                ->toArray();

            $unauthorized = array_diff($validated['people_assigned'], $teacherStudentIds);
            if (!empty($unauthorized)) {
                return response()->json([
                    'message'              => 'Some students do not belong to your groups',
                    'unauthorized_students' => array_values($unauthorized),
                ], 422);
            }
        }

        $homework->update([
            'people_assigned' => $validated['people_assigned'] ?? [],
            'groups_assigned' => $validated['groups_assigned'] ?? [],
        ]);

        return response()->json([
            'message'  => 'Students assigned successfully',
            'homework' => $homework->fresh(),
        ]);
    }
}
