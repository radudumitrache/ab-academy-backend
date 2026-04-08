import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { createApiClient, extractToken } from '../api-client.js';

export function registerListSubmissions(server: McpServer, getToken: () => string) {
  server.tool(
    'list_submissions',
    'List all student submissions for a specific homework assignment. Shows who has submitted, their current grading status, and overall grade if already graded.',
    {
      homework_id: z.number().int().positive().describe('The homework ID to list submissions for'),
    },
    async ({ homework_id }) => {
      const api = createApiClient(getToken());
      const res = await api.get(`/homework/${homework_id}/submissions`);
      const submissions = res.data.submissions ?? [];

      const result = submissions.map((s: any) => ({
        submission_id: s.id,
        student_id: s.student_id,
        student_username: s.student?.username ?? null,
        student_email: s.student?.email ?? null,
        submitted_at: s.submitted_at,
        already_graded: s.grade !== null && s.grade !== undefined,
        overall_grade: s.grade ?? null,
      }));

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
