<?php
// src/Controller/McpTokenController.php

namespace App\Controller;

use App\Entity\McpToken;
use App\Entity\User;
use App\Repository\McpTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Verwaltung der eigenen MCP-Tokens über das Frontend (JWT-Cookie-Authentifizierung).
 *
 *   POST   /api/mcp/tokens      Token erstellen (Klartext nur einmal in der Antwort)
 *   GET    /api/mcp/tokens      Eigene aktive Tokens auflisten (ohne Klartext)
 *   DELETE /api/mcp/tokens/{id} Eigenen Token widerrufen
 *
 * Der Pfad /api/mcp/tokens wird von der MCP-Firewall (Pattern ^/api/mcp$)
 * nicht erfasst und läuft über die normale api-Firewall (JwtTokenAuthenticator).
 * Jeder Benutzer sieht und verwaltet ausschließlich seine eigenen Tokens.
 */
class McpTokenController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly McpTokenRepository $mcpTokenRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/mcp/tokens', name: 'mcp_token_create', methods: ['POST'])]
    public function createToken(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentifizierung erforderlich.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return $this->json(['error' => 'Ungültiger Request-Body: JSON-Objekt erwartet.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $name = isset($body['name']) && is_string($body['name']) ? trim($body['name']) : '';

        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            return $this->json(['error' => 'name: Zeichenkette mit 2 bis 255 Zeichen erforderlich.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $plainToken = McpToken::generateTokenValue();

        $mcpToken = new McpToken();
        $mcpToken->setUser($user);
        $mcpToken->setName($name);
        $mcpToken->setTokenHash(McpToken::hashToken($plainToken));
        $mcpToken->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($mcpToken);
        $this->entityManager->flush();

        // Der Klartext-Token wird genau einmal (in dieser Antwort) zurückgegeben.
        return $this->json([
            'id' => $mcpToken->getId(),
            'name' => $mcpToken->getName(),
            'token' => $plainToken,
            'createdAt' => $mcpToken->getCreatedAt()->format('Y-m-d H:i:s'),
            'message' => 'Token erstellt. Der Klartext ist nur jetzt sichtbar; bitte sofort sicher speichern.',
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/mcp/tokens', name: 'mcp_token_list', methods: ['GET'])]
    public function listTokens(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentifizierung erforderlich.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $tokens = $this->mcpTokenRepository->findActiveByUser($user);

        return $this->json([
            'tokens' => array_map(static fn (McpToken $token): array => [
                'id' => $token->getId(),
                'name' => $token->getName(),
                'createdAt' => $token->getCreatedAt()?->format('Y-m-d H:i:s'),
                'lastUsedAt' => $token->getLastUsedAt()?->format('Y-m-d H:i:s'),
            ], $tokens),
        ]);
    }

    #[Route('/api/mcp/tokens/{id}', name: 'mcp_token_revoke', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function revokeToken(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentifizierung erforderlich.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $mcpToken = $this->mcpTokenRepository->find($id);

        // Fremde Tokens werden wie nicht vorhanden behandelt (keine Enumeration).
        if (!$mcpToken || $mcpToken->getUser() !== $user || $mcpToken->isRevoked()) {
            return $this->json(['error' => 'Token nicht gefunden.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $mcpToken->revoke();
        $this->entityManager->flush();

        return $this->json(['message' => 'Token wurde widerrufen und ist ab sofort ungültig.']);
    }
}
