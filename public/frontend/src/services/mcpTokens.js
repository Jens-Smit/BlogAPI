import api from './api';

// Verwaltung der eigenen MCP-Tokens (Endpunkte hinter /api/mcp/tokens).
// Der Klartext-Token wird nur von createMcpToken genau einmal zurückgegeben.

export const getMcpTokens = () => api.get('/mcp/tokens');

export const createMcpToken = (name) => api.post('/mcp/tokens', { name });

export const revokeMcpToken = (id) => api.delete('/mcp/tokens/' + id);
