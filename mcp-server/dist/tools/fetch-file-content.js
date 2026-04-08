"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.registerFetchFileContent = registerFetchFileContent;
const zod_1 = require("zod");
const axios_1 = __importDefault(require("axios"));
const READABLE_TYPES = [
    'text/plain',
    'text/markdown',
    'text/html',
    'text/csv',
    'text/xml',
    'application/json',
    'application/xml',
];
function registerFetchFileContent(server) {
    server.tool('fetch_file_content', 'Download a file from a signed Google Cloud Storage URL and return its text content. Call this when get_submission_details returns a student_file_url (student uploaded a file as their answer) or instruction_file_urls (teacher uploaded instruction files for context). Text files (.txt, .md, .csv, .json) are returned as readable text. PDF, Word documents, and images are not directly readable — their URL and content type are returned instead.', {
        url: zod_1.z
            .string()
            .url()
            .describe('The signed GCS URL returned by get_submission_details (student_file_url or an entry in instruction_file_urls)'),
    }, async ({ url }) => {
        const response = await axios_1.default.get(url, {
            responseType: 'arraybuffer',
            timeout: 30000,
            maxContentLength: 10 * 1024 * 1024, // 10 MB cap
        });
        const contentType = (response.headers['content-type'] ?? 'application/octet-stream')
            .split(';')[0]
            .trim()
            .toLowerCase();
        const isReadable = READABLE_TYPES.some((t) => contentType.startsWith(t));
        if (isReadable) {
            const text = Buffer.from(response.data).toString('utf-8');
            return {
                content: [
                    {
                        type: 'text',
                        text: JSON.stringify({
                            readable: true,
                            content_type: contentType,
                            text,
                        }),
                    },
                ],
            };
        }
        // Non-readable: return URL and type so the AI can note it
        let note;
        if (contentType.startsWith('image/')) {
            note = 'This is an image file. If your model supports vision, you can view it directly via the URL.';
        }
        else if (contentType === 'application/pdf') {
            note = 'This is a PDF file and cannot be read as text. Manual review required or consider asking the student to re-submit as a text file.';
        }
        else if (contentType.includes('msword') ||
            contentType.includes('officedocument')) {
            note = 'This is a Word document and cannot be read directly. Manual review required.';
        }
        else {
            note = `Binary file of type "${contentType}". Cannot be read as text.`;
        }
        return {
            content: [
                {
                    type: 'text',
                    text: JSON.stringify({
                        readable: false,
                        content_type: contentType,
                        url,
                        note,
                    }),
                },
            ],
        };
    });
}
//# sourceMappingURL=fetch-file-content.js.map