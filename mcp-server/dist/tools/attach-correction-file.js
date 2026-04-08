"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.registerAttachCorrectionFile = registerAttachCorrectionFile;
const zod_1 = require("zod");
const form_data_1 = __importDefault(require("form-data"));
const axios_1 = __importDefault(require("axios"));
const dotenv = __importStar(require("dotenv"));
dotenv.config();
const BASE_URL = (process.env.LARAVEL_API_URL ?? 'http://127.0.0.1').replace(/\/$/, '');
function registerAttachCorrectionFile(server, getToken) {
    server.tool('attach_correction_file', 'Upload an AI-generated correction document (plain text or markdown) and attach it to a specific question response. The file is stored in Google Cloud Storage and the student can download it when viewing their results. Use this for writing, rephrase, word_formation, correct, and replace questions where a detailed annotated correction is more useful than a short observation text. Grades already saved via save_correction are preserved — this only adds the file.', {
        homework_id: zod_1.z.number().int().positive().describe('The homework ID'),
        submission_id: zod_1.z.number().int().positive().describe('The submission ID'),
        response_id: zod_1.z.number().int().positive().describe('The specific question response ID to attach the file to'),
        filename: zod_1.z
            .string()
            .min(1)
            .describe('Filename for the correction document, e.g. "writing_correction.txt" or "rephrase_feedback.md"'),
        content: zod_1.z
            .string()
            .min(1)
            .describe('Full text content of the correction document'),
    }, async ({ homework_id, submission_id, response_id, filename, content }) => {
        const token = getToken();
        // Build multipart form — responses field preserves existing grade/observation
        const form = new form_data_1.default();
        form.append('responses', JSON.stringify([{ response_id }]));
        form.append(`files[${response_id}]`, Buffer.from(content, 'utf-8'), { filename, contentType: 'text/plain' });
        const url = `${BASE_URL}/api/teacher/homework/${homework_id}/submissions/${submission_id}/grade-responses`;
        const response = await axios_1.default.patch(url, form, {
            headers: {
                ...form.getHeaders(),
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
            timeout: 30000,
        });
        return {
            content: [
                {
                    type: 'text',
                    text: JSON.stringify({
                        success: true,
                        response_id,
                        message: `Correction file "${filename}" attached successfully to response ${response_id}.`,
                    }),
                },
            ],
        };
    });
}
//# sourceMappingURL=attach-correction-file.js.map