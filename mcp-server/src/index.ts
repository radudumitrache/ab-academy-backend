import 'dotenv/config';
import express, { Request, Response } from 'express';
import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import { extractToken } from './api-client.js';
import { registerListHomework } from './tools/list-homework.js';
import { registerListSubmissions } from './tools/list-submissions.js';
import { registerFindSubmission } from './tools/find-submission.js';
import { registerGetSubmission } from './tools/get-submission.js';
import { registerSaveCorrection } from './tools/save-correction.js';
import { registerAttachCorrectionFile } from './tools/attach-correction-file.js';
import { registerFetchFileContent } from './tools/fetch-file-content.js';

const PORT = parseInt(process.env.PORT ?? '3001', 10);

const app = express();
app.use(express.json());

app.post('/mcp', async (req: Request, res: Response) => {
  // Extract teacher token from Authorization header on every request
  let token: string;
  try {
    token = extractToken(req.headers.authorization);
  } catch (err: any) {
    res.status(401).json({ error: err.message });
    return;
  }

  // Create a fresh MCP server per request (stateless Streamable HTTP)
  const server = new McpServer({
    name: 'ab-academy-homework-corrector',
    version: '1.0.0',
  });

  // Register all tools, passing a closure that returns the token for this request
  const getToken = () => token;

  registerListHomework(server, getToken);
  registerListSubmissions(server, getToken);
  registerFindSubmission(server, getToken);
  registerGetSubmission(server, getToken);
  registerSaveCorrection(server, getToken);
  registerAttachCorrectionFile(server, getToken);
  registerFetchFileContent(server);

  // Stateless transport — no session management needed
  const transport = new StreamableHTTPServerTransport({
    sessionIdGenerator: undefined,
  });

  res.on('close', () => {
    transport.close();
    server.close();
  });

  await server.connect(transport);
  await transport.handleRequest(req, res, req.body);
});

// Health check
app.get('/health', (_req: Request, res: Response) => {
  res.json({ status: 'ok', service: 'ab-academy-mcp-server' });
});

app.listen(PORT, () => {
  console.log(`AB Academy MCP server running on port ${PORT}`);
  console.log(`Endpoint: POST http://localhost:${PORT}/mcp`);
  console.log(`Health:   GET  http://localhost:${PORT}/health`);
});
