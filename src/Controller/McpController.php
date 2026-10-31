<?php
// src/Controller/McpController.php

namespace App\Controller;

use App\Mcp\McpServer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * MCP-Endpunkt (Streamable HTTP Transport, MCP-Spezifikation 2025-06-18).
 *
 *   POST   /api/mcp  JSON-RPC-Anfragen (initialize, tools/list, tools/call, ...)
 *   GET    /api/mcp  405 (Server bietet keinen SSE-Stream an)
 *   DELETE /api/mcp  405 (Server ist zustandslos, keine Sessions)
 *
 * Die Authentifizierung erfolgt über den Authorization-Header
 * (Bearer <mcp_token>) durch den McpTokenAuthenticator; die Firewall
 * und access_control sichern den Endpunkt vollständig ab.
 */
class McpController extends AbstractController
{
    public function __construct(
        private readonly Security $security
    ) {}

    #[Route('/api/mcp', name: 'mcp_endpoint', methods: ['POST'])]
    public function __invoke(Request $request, McpServer $server): Response
    {
        $content = $request->getContent();

        if ($content === '') {
            return $this->jsonRpcError(null, -32600, 'Leere Anfrage.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $message = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->jsonRpcError(null, -32700, 'Parse-Fehler: Ungültiges JSON.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->security->getUser();

        // Batch (Array von Nachrichten) vs. Einzelne Nachricht
        if (is_array($message) && array_is_list($message) && $message !== []) {
            $responses = $server->handleBatch($message, $user);

            if ($responses === []) {
                // Nur Notifications enthalten: keine Antwort
                return new Response('', Response::HTTP_ACCEPTED);
            }

            return new JsonResponse($responses, Response::HTTP_OK, ['MCP-Protocol-Version' => McpServer::LATEST_PROTOCOL_VERSION]);
        }

        if (!is_array($message)) {
            return $this->jsonRpcError(null, -32600, 'Ungültige JSON-RPC-Nachricht.', Response::HTTP_BAD_REQUEST);
        }

        $response = $server->handleMessage($message, $user);

        if ($response === null) {
            // Notification: keine Antwort senden
            return new Response('', Response::HTTP_ACCEPTED);
        }

        return new JsonResponse(
            $response,
            Response::HTTP_OK,
            ['MCP-Protocol-Version' => McpServer::LATEST_PROTOCOL_VERSION]
        );
    }

    #[Route('/api/mcp', name: 'mcp_endpoint_get', methods: ['GET'])]
    public function getEndpoint(): Response
    {
        // Kein server-initiierter SSE-Stream: 405 gemäß Streamable-HTTP-Spezifikation
        return new JsonResponse(
            ['error' => 'GET wird nicht unterstützt. Verwende POST mit JSON-RPC-Nachrichten.'],
            Response::HTTP_METHOD_NOT_ALLOWED,
            ['Allow' => 'POST']
        );
    }

    #[Route('/api/mcp', name: 'mcp_endpoint_delete', methods: ['DELETE'])]
    public function terminate(): Response
    {
        // Zustandsloser Server ohne Sessions: 405 gemäß Streamable-HTTP-Spezifikation
        return new JsonResponse(
            ['error' => 'Der Server verwaltet keine Sessions.'],
            Response::HTTP_METHOD_NOT_ALLOWED,
            ['Allow' => 'POST']
        );
    }

    /**
     * @param int|string|null $id
     */
    private function jsonRpcError($id, int $code, string $message, int $status): JsonResponse
    {
        return new JsonResponse(
            [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ],
            $status
        );
    }
}
