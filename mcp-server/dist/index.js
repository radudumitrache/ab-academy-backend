"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
require("dotenv/config");
const express_1 = __importDefault(require("express"));
const mcp_js_1 = require("@modelcontextprotocol/sdk/server/mcp.js");
const streamableHttp_js_1 = require("@modelcontextprotocol/sdk/server/streamableHttp.js");
const api_client_js_1 = require("./api-client.js");
const list_homework_js_1 = require("./tools/list-homework.js");
const list_submissions_js_1 = require("./tools/list-submissions.js");
const find_submission_js_1 = require("./tools/find-submission.js");
const get_submission_js_1 = require("./tools/get-submission.js");
const save_correction_js_1 = require("./tools/save-correction.js");
const attach_correction_file_js_1 = require("./tools/attach-correction-file.js");
const fetch_file_content_js_1 = require("./tools/fetch-file-content.js");
const PORT = parseInt(process.env.PORT ?? '3001', 10);
const app = (0, express_1.default)();
app.use(express_1.default.json());
app.post('/mcp', async (req, res) => {
    // Extract teacher token from Authorization header on every request
    let token;
    try {
        token = (0, api_client_js_1.extractToken)(req.headers.authorization);
    }
    catch (err) {
        res.status(401).json({ error: err.message });
        return;
    }
    // Create a fresh MCP server per request (stateless Streamable HTTP)
    const server = new mcp_js_1.McpServer({
        name: 'ab-academy-homework-corrector',
        version: '1.0.0',
    });
    // Register all tools, passing a closure that returns the token for this request
    const getToken = () => token;
    (0, list_homework_js_1.registerListHomework)(server, getToken);
    (0, list_submissions_js_1.registerListSubmissions)(server, getToken);
    (0, find_submission_js_1.registerFindSubmission)(server, getToken);
    (0, get_submission_js_1.registerGetSubmission)(server, getToken);
    (0, save_correction_js_1.registerSaveCorrection)(server, getToken);
    (0, attach_correction_file_js_1.registerAttachCorrectionFile)(server, getToken);
    (0, fetch_file_content_js_1.registerFetchFileContent)(server);
    // Stateless transport — no session management needed
    const transport = new streamableHttp_js_1.StreamableHTTPServerTransport({
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
app.get('/health', (_req, res) => {
    res.json({ status: 'ok', service: 'ab-academy-mcp-server' });
});
app.listen(PORT, () => {
    console.log(`AB Academy MCP server running on port ${PORT}`);
    console.log(`Endpoint: POST http://localhost:${PORT}/mcp`);
    console.log(`Health:   GET  http://localhost:${PORT}/health`);
});
//# sourceMappingURL=index.js.map