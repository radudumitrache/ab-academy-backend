import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { createApiClient } from '../api-client.js';

export function registerGetSubmission(server: McpServer, getToken: () => string) {
  server.tool(
    'get_submission_details',
    `Fetch the full details of a student's homework submission, grouped by section.
Returns all questions with their type-specific context (correct answers, sample answers, question variants, reading passages, listening transcripts, teacher instruction text and file URLs) alongside the student's answers and any uploaded file URLs.

Use this before grading. For any question where student_file_url is set, call fetch_file_content to read the student's uploaded answer. For any section with instruction_file_urls, call fetch_file_content to read the teacher's instructions.

Grading guidance by question type:
- multiple_choice / reading_multiple_choice / listening_multiple_choice: compare student_answer (index) to correct_answer (text) — 100 if correct, 0 if not
- gap_fill / text_completion: each blank vs correct_answers array — score = correct / total * 100
- correlation: student pairs vs correct_pairs — score = correct / total * 100
- rephrase / word_formation / word_derivation / correct / replace: AI judges degree of correctness using sample_answer as benchmark — partial credit allowed
- writing_question: evaluate task achievement, grammar, vocabulary, coherence — use sample_answer as benchmark
- reading_question: analyze section passage + question — evaluate comprehension; if correct_answers set compare directly
- mixed_question: use sample_answer as benchmark
- speaking_question: SKIP — set grade to null, observation to "Speaking questions require manual review"
- Listening sections with transcript: grade normally using transcript as context; without transcript: note "Audio transcript not available"`,
    {
      homework_id: z.number().int().positive().describe('The homework ID'),
      submission_id: z.number().int().positive().describe('The submission ID'),
    },
    async ({ homework_id, submission_id }) => {
      const api = createApiClient(getToken());

      // Parallel: fetch submission + sections
      const [subRes, sectionsRes] = await Promise.all([
        api.get(`/homework/${homework_id}/submissions/${submission_id}`),
        api.get(`/homework/${homework_id}/sections`),
      ]);

      const submission = subRes.data.submission;
      const sections: any[] = sectionsRes.data.sections ?? [];

      // Build section map and resolve instruction file URLs in parallel
      const materialIdSet = new Set<number>();
      for (const section of sections) {
        const files: number[] = section.instruction_files ?? [];
        files.forEach((id) => materialIdSet.add(id));
      }

      const materialUrls: Record<number, string> = {};
      if (materialIdSet.size > 0) {
        const materialFetches = Array.from(materialIdSet).map(async (id) => {
          try {
            const matRes = await api.get(`/materials/${id}`);
            materialUrls[id] = matRes.data.download_url ?? '';
          } catch {
            materialUrls[id] = '';
          }
        });
        await Promise.all(materialFetches);
      }

      // Build section lookup by id
      const sectionMap: Record<number, any> = {};
      for (const s of sections) {
        sectionMap[s.id] = s;
      }

      // Group responses by section
      const sectionQuestions: Record<number, any[]> = {};
      const responses: any[] = submission.responses ?? [];

      for (const response of responses) {
        const q = response.question;
        if (!q) continue;
        const sectionId: number = q.section_id;
        if (!sectionQuestions[sectionId]) sectionQuestions[sectionId] = [];

        const questionEntry: Record<string, any> = {
          response_id: response.response_id,
          question_id: q.question_id,
          question_text: q.question_text,
          question_type: q.question_type,
          student_answer: response.answer ?? null,
          student_file_url: response.file_url ?? null,
          correct_answer: response.correct_answer ?? null,
          sample_answer: null,
          existing_grade: response.grade ?? null,
          existing_observation: response.observation ?? null,
        };

        // Populate type-specific fields
        switch (q.question_type) {
          case 'multiple_choice':
          case 'reading_multiple_choice':
          case 'listening_multiple_choice': {
            const details = q.multiple_choice_details ?? q.multipleChoiceDetails ?? {};
            questionEntry.variants = details.variants ?? [];
            // correct_answer already resolved by formatSubmission
            break;
          }
          case 'gap_fill': {
            const details = q.gap_fill_details ?? q.gapFillDetails ?? {};
            questionEntry.with_variants = details.with_variants ?? false;
            questionEntry.gap_variants = details.variants ?? [];
            questionEntry.correct_answer = details.correct_answers ?? null;
            break;
          }
          case 'text_completion': {
            const details = q.text_completion_details ?? q.textCompletionDetails ?? {};
            questionEntry.full_text = details.full_text ?? null;
            questionEntry.correct_answer = details.correct_answers ?? null;
            break;
          }
          case 'correlation': {
            const details = q.correlation_details ?? q.correlationDetails ?? {};
            questionEntry.column_a = details.column_a ?? [];
            questionEntry.column_b = details.column_b ?? [];
            questionEntry.correct_answer = details.correct_pairs ?? null;
            break;
          }
          case 'correct': {
            const details = q.correct_details ?? q.correctDetails ?? {};
            questionEntry.incorrect_text = details.incorrect_text ?? null;
            questionEntry.sample_answer = details.sample_answer ?? null;
            break;
          }
          case 'word_formation': {
            const details = q.word_formation_details ?? q.wordFormationDetails ?? {};
            questionEntry.base_word = details.base_word ?? null;
            questionEntry.sample_answer = details.sample_answer ?? null;
            break;
          }
          case 'word_derivation': {
            const details = q.word_derivation_details ?? q.wordDerivationDetails ?? {};
            questionEntry.root_word = details.root_word ?? null;
            questionEntry.sample_answer = details.sample_answer ?? null;
            break;
          }
          case 'rephrase': {
            const details = q.rephrase_details ?? q.rephraseDetails ?? {};
            questionEntry.sample_answer = details.sample_answer ?? null;
            break;
          }
          case 'replace': {
            const details = q.replace_details ?? q.replaceDetails ?? {};
            questionEntry.original_text = details.original_text ?? null;
            questionEntry.sample_answer = details.sample_answer ?? null;
            break;
          }
          case 'writing_question': {
            const details = q.writing_question_details ?? q.writingQuestionDetails ?? {};
            questionEntry.sample_answer = details.sample_answer ?? null;
            break;
          }
          case 'reading_question': {
            const details = q.reading_question_details ?? q.readingQuestionDetails ?? {};
            questionEntry.sample_answer = details.sample_answer ?? null;
            questionEntry.correct_answer = details.correct_answers ?? null;
            break;
          }
          case 'speaking_question': {
            const details = q.speaking_question_details ?? q.speakingQuestionDetails ?? {};
            questionEntry.sample_answer = details.sample_answer ?? null;
            break;
          }
          case 'mixed_question': {
            const details = q.mixed_question_details ?? q.mixedQuestionDetails ?? {};
            questionEntry.sample_answer = details.sample_answer ?? null;
            break;
          }
        }

        sectionQuestions[sectionId].push(questionEntry);
      }

      // Build final section list (only sections that have responses)
      const sectionIds = Object.keys(sectionQuestions).map(Number);
      const builtSections = sectionIds.map((sectionId) => {
        const s = sectionMap[sectionId] ?? {};
        const instructionFileUrls = (s.instruction_files ?? [])
          .map((id: number) => materialUrls[id])
          .filter(Boolean);

        return {
          section_id: sectionId,
          section_type: s.section_type ?? null,
          title: s.title ?? null,
          instruction_text: s.instruction_text ?? null,
          instruction_file_urls: instructionFileUrls,
          passage: s.passage ?? null,
          transcript: s.transcript ?? null,
          questions: sectionQuestions[sectionId],
        };
      });

      const result = {
        submission_id: submission.id,
        homework_id,
        student_username: submission.student?.username ?? null,
        student_email: submission.student?.email ?? null,
        submitted_at: submission.submitted_at,
        already_graded: submission.grade !== null && submission.grade !== undefined,
        overall_grade: submission.grade ?? null,
        overall_observation: submission.observation ?? null,
        sections: builtSections,
      };

      return {
        content: [
          {
            type: 'text' as const,
            text: JSON.stringify(result, null, 2),
          },
        ],
      };
    }
  );
}
