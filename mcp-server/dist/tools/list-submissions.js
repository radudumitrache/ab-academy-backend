"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.registerListSubmissions = registerListSubmissions;
const zod_1 = require("zod");
const api_client_js_1 = require("../api-client.js");
function registerListSubmissions(server, getToken) {
    server.tool('list_submissions', 'List all student submissions for a specific homework assignment. Shows who has submitted, their current grading status, and overall grade if already graded.', {
        homework_id: zod_1.z.number().int().positive().describe('The homework ID to list submissions for'),
    }, async ({ homework_id }) => {
        const api = (0, api_client_js_1.createApiClient)(getToken());
        const res = await api.get(`/homework/${homework_id}/submissions`);
        const submissions = res.data.submissions ?? [];
        const result = submissions.map((s) => ({
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
                    type: 'text',
                    text: JSON.stringify(result, null, 2),
                },
            ],
        };
    });
}
//# sourceMappingURL=list-submissions.js.map