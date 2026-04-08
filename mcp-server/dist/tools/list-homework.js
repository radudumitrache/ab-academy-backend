"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.registerListHomework = registerListHomework;
const api_client_js_1 = require("../api-client.js");
function registerListHomework(server, getToken) {
    server.tool('list_homework', 'List all homework assignments belonging to the authenticated teacher. Use this to browse available homework before correcting submissions.', {}, async () => {
        const api = (0, api_client_js_1.createApiClient)(getToken());
        const res = await api.get('/homework');
        const homeworkList = res.data.homework ?? [];
        const result = homeworkList.map((hw) => ({
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
                    type: 'text',
                    text: JSON.stringify(result, null, 2),
                },
            ],
        };
    });
}
//# sourceMappingURL=list-homework.js.map