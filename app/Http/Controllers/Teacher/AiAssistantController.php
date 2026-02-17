<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\AIProfiles\MedicalAssistant;
use App\Http\Controllers\AIProfiles\NormalAssistant;
use App\Http\Controllers\Controller;
use Anthropic\Core\Exceptions\APIException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Services\ClaudeClientFactory;

class AiAssistantController extends Controller
{
    public function translate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'target_language' => 'required|string|in:english,dutch',
            'profile' => 'required|string|in:normal,medical',
        ]);

        $systemPrompt = $this->resolveSystemPrompt($validated['profile'], $validated['target_language']);

        try {
            $client = ClaudeClientFactory::make();
            $message = $client->messages->create(
                maxTokens: 600,
                messages: [
                    [
                        'role' => 'user',
                        'content' => $validated['text'],
                    ],
                ],
                model: (string) config('services.anthropic.model', 'claude-3-5-sonnet-latest'),
                system: $systemPrompt,
            );
        } catch (APIException $exception) {
            return response()->json([
                'message' => 'Translation request failed.',
                'error' => $exception->getMessage(),
            ], 502);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }

        $translatedText = $this->extractTranslatedText($message);

        if ($translatedText === '') {
            return response()->json([
                'message' => 'Translation response did not contain translated text.',
            ], 502);
        }

        return response()->json([
            'message' => 'Translation successful',
            'data' => [
                'translated_text' => trim($translatedText),
                'target_language' => $validated['target_language'],
                'profile' => $validated['profile'],
                'model' => (string) ($message->model ?? config('services.anthropic.model')),
            ],
        ]);
    }

    private function resolveSystemPrompt(string $profile, string $targetLanguage): string
    {
        return $profile === 'medical'
            ? MedicalAssistant::systemPrompt($targetLanguage)
            : NormalAssistant::systemPrompt($targetLanguage);
    }

    private function extractTranslatedText(object $message): string
    {
        $content = $message->content ?? [];

        if (!is_array($content) || count($content) === 0) {
            return '';
        }

        $firstBlock = $content[0];

        if (is_object($firstBlock)) {
            return (string) ($firstBlock->text ?? '');
        }

        if (is_array($firstBlock)) {
            return (string) ($firstBlock['text'] ?? '');
        }

        return '';
    }
}