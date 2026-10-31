import React, { useState, useEffect, useCallback } from 'react';
import { getMcpTokens, createMcpToken, revokeMcpToken } from '../services/mcpTokens';

/**
 * MCP-Token-Verwaltung (nur für eingeloggte Benutzer).
 *
 * Der Klartext-Token wird bei der Erstellung genau einmal angezeigt und ist
 * danach nicht mehr rekonstruierbar (gespeichert wird nur ein SHA-256-Hash).
 */
const McpTokens = () => {
  const [tokens, setTokens] = useState([]);
  const [name, setName] = useState('');
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);
  const [revokingId, setRevokingId] = useState(null);
  const [error, setError] = useState(null);
  const [notice, setNotice] = useState(null);
  const [newToken, setNewToken] = useState(null);
  const [copied, setCopied] = useState(false);

  const loadTokens = useCallback(async () => {
    try {
      const response = await getMcpTokens();
      setTokens(response.data.tokens || []);
      setError(null);
    } catch (err) {
      setError('Tokens konnten nicht geladen werden (HTTP ' + (err.response?.status ?? 'Netzwerk') + ').');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadTokens();
  }, [loadTokens]);

  const handleCreate = async (event) => {
    event.preventDefault();
    const trimmed = name.trim();

    if (trimmed.length < 2) {
      setError('Der Name muss mindestens 2 Zeichen lang sein.');
      return;
    }

    try {
      setCreating(true);
      setError(null);
      setNotice(null);
      const response = await createMcpToken(trimmed);
      setNewToken(response.data);
      setCopied(false);
      setName('');
      await loadTokens();
    } catch (err) {
      const message = err.response?.data?.error;
      setError(message || 'Token konnte nicht erstellt werden.');
    } finally {
      setCreating(false);
    }
  };

  const handleRevoke = async (id, tokenName) => {
    const confirmed = window.confirm(
      'Token "' + tokenName + '" widerrufen? Verbundene Clients verlieren sofort den Zugriff.'
    );
    if (!confirmed) {
      return;
    }

    try {
      setRevokingId(id);
      setError(null);
      setNotice(null);
      await revokeMcpToken(id);
      setNewToken(null);
      await loadTokens();
      setNotice('Token "' + tokenName + '" wurde widerrufen.');
    } catch (err) {
      setError('Token konnte nicht widerrufen werden (HTTP ' + (err.response?.status ?? 'Netzwerk') + ').');
    } finally {
      setRevokingId(null);
    }
  };

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(newToken.token);
      setCopied(true);
    } catch (err) {
      setCopied(false);
    }
  };

  const formatDate = (value) => (value ? value.replace(' ', ', ') : 'nie');

  return (
    <div className="max-w-4xl mx-auto px-4 py-10">
      <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">MCP-Token</h1>
      <p className="text-gray-600 dark:text-gray-400 mb-8">
        Tokens ermöglichen KI-Clients (z.B. Vibe, Claude, Cursor) den
        authentifizierten Zugriff auf den MCP-Endpunkt. Der Klartext wird nur
        einmal angezeigt.
      </p>

      {error && (
        <div className="mb-4 p-4 rounded-md bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">
          {error}
        </div>
      )}
      {notice && (
        <div className="mb-4 p-4 rounded-md bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm">
          {notice}
        </div>
      )}

      {newToken && (
        <div className="mb-8 p-5 rounded-lg border-2 border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20">
          <h2 className="font-semibold text-gray-900 dark:text-white mb-2">Neuer Token erstellt</h2>
          <p className="text-sm text-gray-700 dark:text-gray-300 mb-3">
            Bitte jetzt sicher speichern. Aus Sicherheitsgründen kann der
            Klartext später nicht erneut angezeigt werden.
          </p>
          <div className="flex items-center gap-2">
            <code className="flex-1 block px-3 py-2 rounded bg-gray-900 dark:bg-gray-800 text-green-400 font-mono text-sm break-all">
              {newToken.token}
            </code>
            <button
              type="button"
              onClick={handleCopy}
              className="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
            >
              {copied ? 'Kopiert' : 'Kopieren'}
            </button>
          </div>
        </div>
      )}

      <form onSubmit={handleCreate} className="mb-8 flex flex-col sm:flex-row gap-2">
        <input
          type="text"
          value={name}
          onChange={(event) => setName(event.target.value)}
          placeholder="Bezeichnung, z.B. Vibe"
          maxLength={255}
          className="flex-1 px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <button
          type="submit"
          disabled={creating}
          className="px-5 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
        >
          {creating ? 'Erstelle ...' : 'Token erstellen'}
        </button>
      </form>

      {loading ? (
        <p className="text-gray-500 dark:text-gray-400">Lade Tokens ...</p>
      ) : tokens.length === 0 ? (
        <p className="text-gray-500 dark:text-gray-400">Noch keine Tokens vorhanden.</p>
      ) : (
        <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
              <tr>
                <th className="px-4 py-3 text-left font-medium">Name</th>
                <th className="px-4 py-3 text-left font-medium">Erstellt</th>
                <th className="px-4 py-3 text-left font-medium">Zuletzt genutzt</th>
                <th className="px-4 py-3 text-right font-medium">Aktion</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
              {tokens.map((token) => (
                <tr key={token.id}>
                  <td className="px-4 py-3 text-gray-900 dark:text-white font-medium">{token.name}</td>
                  <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{formatDate(token.createdAt)}</td>
                  <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{formatDate(token.lastUsedAt)}</td>
                  <td className="px-4 py-3 text-right">
                    <button
                      type="button"
                      onClick={() => handleRevoke(token.id, token.name)}
                      disabled={revokingId === token.id}
                      className="text-red-600 dark:text-red-400 hover:underline disabled:opacity-50 text-sm font-medium"
                    >
                      {revokingId === token.id ? 'Widerrufe ...' : 'Widerrufen'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default McpTokens;
