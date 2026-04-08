import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { createApiClient, extractToken } from '../api-client.js';

export function registerListHomework(server: McpServer, getToken: () => string) {
  server.tool(
    'list_homework',
    'List all homework assignments belonging to the authenticated teacher. Use this to browse available homework before correcting submissions.',
    {},
    async () => {
      const api = createApiClient(getToken());
      const res = await api.get('/homework');
      const homeworkList = res.data.homework ?? [];

      const result = homeworkList.map((hw: any) => ({
        homework_id: hw.id,
        title: hw.homework_title,
        description: hw.homework_description ?? null,
        due_date: hw.due_date,
        status: hw.status,
        question_count: hw.all_questions_count ?? 0,
        created_at: hw.created_at,
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
