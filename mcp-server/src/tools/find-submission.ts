import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { createApiClient } from '../api-client.js';

export function registerFindSubmission(server: McpServer, getToken: () => string) {
  server.tool(
    'find_submission',
    'Find a specific student\'s submission by student name and homework title. Supports partial, case-insensitive matching. Returns the homework_id and submission_id needed for get_submission_details and save_correction.',
    {
      student_name: z.string().min(1).describe('Student username (partial match supported)'),
      homework_title: z.string().min(1).describe('Homework title (partial match supported)'),
    },
    async ({ student_name, homework_title }) => {
      const api = createApiClient(getToken());

      // Step 1: find the homework by title
      const hwRes = await api.get('/homework');
      const allHomework: any[] = hwRes.data.homework ?? [];
      const titleLower = homework_title.toLowerCase();
      const homework = allHomework.find((hw: any) =>
        (hw.homework_title as string).toLowerCase().includes(titleLower)
      );

      if (!homework) {
        return {
          content: [
            {
              type: 'text' as const,
              text: JSON.stringify({
                error: `No homework found matching title: "${homework_title}"`,
              }),
            },
          ],
        };
      }

      // Step 2: find the student's submission
      const subRes = await api.get(`/homework/${homework.id}/submissions`);
      const submissions: any[] = subRes.data.submissions ?? [];
      const nameLower = student_name.toLowerCase();
      const submission = submissions.find((s: any) =>
        (s.student?.username as string ?? '').toLowerCase().includes(nameLower)
      );

      if (!submission) {
        return {
          content: [
            {
              type: 'text' as const,
              text: JSON.stringify({
                error: `No submitted submission found for student "${student_name}" on homework "${homework.homework_title}". The student may not have submitted yet.`,
              }),
            },
          ],
        };
      }

      return {
        content: [
          {
            type: 'text' as const,
            text: JSON.stringify({
              homework_id: homework.id,
              homework_title: homework.homework_title,
              submission_id: submission.id,
              student_id: submission.student_id,
              student_username: submission.student?.username ?? null,
              submitted_at: submission.submitted_at,
              already_graded: submission.grade !== null && submission.grade !== undefined,
              overall_grade: submission.grade ?? null,
            }),
          },
        ],
      };
    }
  );
}
