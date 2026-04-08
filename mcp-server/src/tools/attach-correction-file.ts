import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import FormData from 'form-data';
import axios from 'axios';
import * as dotenv from 'dotenv';
import { extractToken } from '../api-client.js';

dotenv.config();

const BASE_URL = (process.env.LARAVEL_API_URL ?? 'http://127.0.0.1').replace(/\/$/, '');

export function registerAttachCorrectionFile(server: McpServer, getToken: () => string) {
  server.tool(
    'attach_correction_file',
    'Upload an AI-generated correction document (plain text or markdown) and attach it to a specific question response. The file is stored in Google Cloud Storage and the student can download it when viewing their results. Use this for writing, rephrase, word_formation, correct, and replace questions where a detailed annotated correction is more useful than a short observation text. Grades already saved via save_correction are preserved — this only adds the file.',
    {
      homework_id: z.number().int().positive().describe('The homework ID'),
      submission_id: z.number().int().positive().describe('The submission ID'),
      response_id: z.number().int().positive().describe('The specific question response ID to attach the file to'),
      filename: z
        .string()
        .min(1)
        .describe('Filename for the correction document, e.g. "writing_correction.txt" or "rephrase_feedback.md"'),
      content: z
        .string()
        .min(1)
        .describe('Full text content of the correction document'),
    },
    async ({ homework_id, submission_id, response_id, filename, content }) => {
      const token = getToken();

      // Build multipart form — responses field preserves existing grade/observation
      const form = new FormData();
      form.append('responses', JSON.stringify([{ response_id }]));
      form.append(
        `files[${response_id}]`,
        Buffer.from(content, 'utf-8'),
        { filename, contentType: 'text/plain' }
      );

      const url = `${BASE_URL}/api/teacher/homework/${homework_id}/submissions/${submission_id}/grade-responses`;

      const response = await axios.patch(url, form, {
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
            type: 'text' as const,
            text: JSON.stringify({
              success: true,
              response_id,
              message: `Correction file "${filename}" attached successfully to response ${response_id}.`,
            }),
          },
        ],
      };
    }
  );
}
