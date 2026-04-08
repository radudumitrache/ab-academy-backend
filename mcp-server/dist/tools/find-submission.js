"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.registerFindSubmission = registerFindSubmission;
const zod_1 = require("zod");
const api_client_js_1 = require("../api-client.js");
function registerFindSubmission(server, getToken) {
    server.tool('find_submission', 'Find a specific student\'s submission by student name and homework title. Supports partial, case-insensitive matching. Returns the homework_id and submission_id needed for get_submission_details and save_correction.', {
        student_name: zod_1.z.string().min(1).describe('Student username (partial match supported)'),
        homework_title: zod_1.z.string().min(1).describe('Homework title (partial match supported)'),
    }, async ({ student_name, homework_title }) => {
        const api = (0, api_client_js_1.createApiClient)(getToken());
        // Step 1: find the homework by title
        const hwRes = await api.get('/homework');
        const allHomework = hwRes.data.homework ?? [];
        const titleLower = homework_title.toLowerCase();
        const homework = allHomework.find((hw) => hw.homework_title.toLowerCase().includes(titleLower));
        if (!homework) {
            return {
                content: [
                    {
                        type: 'text',
                        text: JSON.stringify({
                            error: `No homework found matching title: "${homework_title}"`,
                        }),
                    },
                ],
            };
        }
        // Step 2: find the student's submission
        const subRes = await api.get(`/homework/${homework.id}/submissions`);
        const submissions = subRes.data.submissions ?? [];
        const nameLower = student_name.toLowerCase();
        const submission = submissions.find((s) => (s.student?.username ?? '').toLowerCase().includes(nameLower));
        if (!submission) {
            return {
                content: [
                    {
                        type: 'text',
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
                    type: 'text',
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
    });
}
//# sourceMappingURL=find-submission.js.map