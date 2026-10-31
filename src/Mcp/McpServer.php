<?php
// src/Mcp/McpServer.php

namespace App\Mcp;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Implementiert den MCP-Streamable-HTTP-Transport (JSON-RPC 2.0).
 *
 * Unterstützte Methoden:
 *   - initialize              (Handshake, Protokollversion aushandeln)
 *   - notifications/initialized (Bestätigung, keine Antwort)
 *   - ping                    (Lebenszeichen)
 *   - tools/list              (Tool-Definitionen inkl. JSON-Schema)
 *   - tools/call              (Tool-Ausführung im Kontext des Benutzers)
 *
 * Der Server ist bewusst zustandslos (keine Server-Sessions, kein SSE-Stream
 * für GET): Jede Anfrage wird authentifiziert und eigenständig verarbeitet.
 * Das ist mit dem MCP-Standard kompatibel und vereinfacht Betrieb und Tests.
 */
class McpServer
{
    public const SERVER_NAME = 'BlogAPI MCP';
    public const SERVER_VERSION = '1.0.0';
    public const LATEST_PROTOCOL_VERSION = '2025-06-18';
    private const SUPPORTED_PROTOCOL_VERSIONS = [
        self::LATEST_PROTOCOL_VERSION,
        '2025-03-26',
        '2024-11-05',
    ];

    public function __construct(
        private readonly McpToolProvider $toolProvider
    ) {}

    /**
     * Verarbeitet eine einzelne JSON-RPC-Nachricht.
     *
     * @param array<string, mixed> $message
     * @return array<string, mixed>|null Antwort-Objekt oder null bei Notification
     */
    public function handleMessage(array $message, ?UserInterface $user): ?array
    {
        // JSON-RPC 2.0 Struktur prüfen
        if (($message['jsonrpc'] ?? null) !== '2.0' || !isset($message['method']) || !is_string($message['method'])) {
            return $this->error($message['id'] ?? null, -32600, 'Ungültige JSON-RPC-Nachricht.');
        }

        $method = $message['method'];
        $id = $message['id'] ?? null;
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        // Notifications (keine id) bekommen keine Antwort
        if ($id === null) {
            return null;
        }

        try {
            switch ($method) {
                case 'initialize':
                    return $this->result($id, $this->initialize($params));

                case 'notifications/initialized':
                    // Notification mit id: leeres Resultat gemäß Spezifikation
                    return $this->result($id, new \stdClass());

                case 'ping':
                    return $this->result($id, new \stdClass());

                case 'tools/list':
                    return $this->result($id, ['tools' => $this->toolProvider->definitions()]);

                case 'tools/call':
                    return $this->result($id, $this->callTool($params, $user));

                default:
                    return $this->error($id, -32601, sprintf('Methode nicht unterstützt: %s', $method));
            }
        } catch (\InvalidArgumentException $e) {
            // Ungültige Argumente (inkl. "Unknown tool")
            return $this->error($id, -32602, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->error($id, -32603, 'Interner Serverfehler.');
        }
    }

    /**
     * Verarbeitet eine JSON-RPC-Batch-Nachricht (Array von Nachrichten).
     *
     * @param array<int, array<string, mixed>> $messages
     * @return array<int, array<string, mixed>> Antworten (leer, wenn nur Notifications)
     */
    public function handleBatch(array $messages, ?UserInterface $user): array
    {
        $responses = [];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }
            $response = $this->handleMessage($message, $user);
            if ($response !== null) {
                $responses[] = $response;
            }
        }

        return $responses;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function initialize(array $params): array
    {
        $requestedVersion = $params['protocolVersion'] ?? null;
        $protocolVersion = is_string($requestedVersion) && in_array($requestedVersion, self::SUPPORTED_PROTOCOL_VERSIONS, true)
            ? $requestedVersion
            : self::LATEST_PROTOCOL_VERSION;

        return [
            'protocolVersion' => $protocolVersion,
            'capabilities' => [
                'tools' => [
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
            'instructions' => 'BlogAPI MCP-Server: Ermöglicht das Erstellen und Verwalten von Blogbeiträgen inklusive Beitragsbildern. Typischer Ablauf: list_categories -> upload_image -> create_blog_post.',
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function callTool(array $params, ?UserInterface $user): array
    {
        if (!$user instanceof User) {
            // Durch die Firewall abgesichert; Sicherheitsnetz
            return $this->toolError('Authentifizierung erforderlich.');
        }

        $name = $params['name'] ?? null;
        if (!is_string($name) || $name === '') {
            throw new \InvalidArgumentException('params.name: Tool-Name erforderlich.');
        }

        $arguments = $params['arguments'] ?? [];
        if (!is_array($arguments)) {
            throw new \InvalidArgumentException('params.arguments: Objekt erwartet.');
        }

        try {
            $result = $this->toolProvider->call($name, $arguments, $user);

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ],
                ],
                'isError' => false,
            ];
        } catch (\RuntimeException $e) {
            // Fachlicher Fehler im Tool: als Fehlerergebnis melden (kein Transportfehler)
            return $this->toolError($e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function toolError(string $message): array
    {
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode(['error' => $message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ],
            'isError' => true,
        ];
    }

    /**
     * @param int|string|null $id
     * @return array<string, mixed>
     */
    private function result($id, object|array $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    /**
     * @param int|string|null $id
     * @return array<string, mixed>
     */
    private function error($id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
