import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { createApiClient } from '../api-client.js';

export function registerSaveCorrection(server: McpServer, getToken: () => string) {
  server.tool(
    'save_correction',
    'Save the AI-generated correction back to the system. Stores an overall score (0-100) and observation on the submission, plus individual scores and observations per question response. Both the overall grade and per-response grades trigger a student notification. Call attach_correction_file separately for any question that needs a detailed written correction document.',
    {
      homework_id: z.number().int().positive().describe('The homework ID'),
      submission_id: z.number().int().positive().describe('The submission ID'),
      overall_score: z
        .number()
        .int()
        .min(0)
        .max(100)
        .describe('Overall score for the entire submission (0-100)'),
      overall_observation: z
        .string()
        .describe('General feedback on the entire submission'),
      response_grades: z
        .array(
          z.object({
            response_id: z.number().int().positive().describe('The question response ID'),
            score: z
              .number()
              .int()
              .min(0)
              .max(100)
              .describe('Score for this question (0-100)'),
            observation: z
              .string()
              .describe('Feedback for this question — highlight mistakes and explain corrections'),
          })
        )
        .min(1)
        .describe('Per-question grades and observations'),
    },
    async ({ homework_id, submission_id, overall_score, overall_observation, response_grades }) => {
      const api = createApiClient(getToken());

      // Step 1: save overall submission grade
      await api.patch(`/homework/${homework_id}/submissions/${submission_id}/grade`, {
        grade: String(overall_score),
        observation: overall_observation,
      });

      // Step 2: save per-response grades
      const responses = response_grades.map((r) => ({
        response_id: r.response_id,
        grade: String(r.score),
        observation: r.observation,
      }));

      await api.patch(
        `/homework/${homework_id}/submissions/${submission_id}/grade-responses`,
        { responses }
      );

      return {
        content: [
          {
            type: 'text' as const,
            text: JSON.stringify({
              success: true,
              message: `Correction saved successfully. Overall score: ${overall_score}/100. Graded ${response_grades.length} question(s). Student has been notified.`,
            }),
          },
        ],
      };
    }
  );
}
