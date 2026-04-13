<?php

use App\Http\Controllers\Student\HomeworkController;
use App\Services\GcsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Student Homework Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/homework',                     [HomeworkController::class, 'index']);
    Route::get('/homework/{id}',                [HomeworkController::class, 'show']);
    Route::get('/homework/{id}/results',        [HomeworkController::class, 'results']);
    Route::post('/homework/{id}/answers',       [HomeworkController::class, 'saveAnswers']);
    Route::post('/homework/{id}/submit',        [HomeworkController::class, 'submit']);

    Route::get('/submissions/{submissionId}/responses/{responseId}/correction-content', function ($submissionId, $responseId) {
        $studentId = Auth::id();

        // Verify the submission belongs to this student
        $submission = DB::table('homework_submissions')
            ->where('id', $submissionId)
            ->where('student_id', $studentId)
            ->first();

        if (!$submission) {
            abort(404, 'Submission not found');
        }

        $response = DB::table('question_responses')
            ->where('submission_id', $submissionId)
            ->where('response_id', $responseId)
            ->first();

        if (!$response) {
            abort(404, 'Response not found');
        }

        if (!$response->correction_file_path) {
            abort(404, 'No correction file');
        }

        $content = app(GcsService::class)->downloadContent($response->correction_file_path);

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    });
});
