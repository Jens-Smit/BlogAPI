<?php
// src/Security/McpTokenAuthenticator.php

namespace App\Security;

use App\Entity\McpToken;
use App\Repository\McpTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authentifiziert MCP-Clients über den Authorization-Header:
 *
 *     Authorization: Bearer mcp_<token>
 *
 * Der Klartext-Token wird nie gespeichert; geprüft wird der SHA-256-Hash
 * gegen die mcp_token-Tabelle. Diese Authentifizierung ist bewusst vom
 * Cookie-basierten JwtTokenAuthenticator getrennt, da MCP-Clients (z.B.
 * Vibe/Mistral, Claude, Cursor) keine Cookies verwalten und statische,
 * langlebige Bearer-Credentials benötigen.
 */
class McpTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly McpTokenRepository $mcpTokenRepository,
        private readonly UserProviderInterface $userProvider,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function supports(Request $request): ?bool
    {
        $authorization = $request->headers->get('Authorization', '');

        return preg_match('/^Bearer\s+\S+$/i', $authorization) === 1;
    }

    public function authenticate(Request $request): Passport
    {
        $authorization = $request->headers->get('Authorization', '');
        $plainToken = trim(preg_replace('/^Bearer\s+/i', '', $authorization));

        if (!str_starts_with($plainToken, McpToken::TOKEN_PREFIX)) {
            throw new CustomUserMessageAuthenticationException('Ungültiger MCP-Token.');
        }

        $mcpToken = $this->mcpTokenRepository->findActiveByHash(
            McpToken::hashToken($plainToken)
        );

        if (!$mcpToken) {
            throw new CustomUserMessageAuthenticationException('Ungültiger oder widerrufener MCP-Token.');
        }

        $user = $mcpToken->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw new CustomUserMessageAuthenticationException('Dem MCP-Token ist kein Benutzer zugeordnet.');
        }

        // Letzte Nutzung protokollieren (Auditierbarkeit)
        $mcpToken->setLastUsedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new SelfValidatingPassport(
            new UserBadge($user->getEmail(), function (string $userIdentifier) {
                return $this->userProvider->loadUserByIdentifier($userIdentifier);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Authentifizierung erfolgreich: Request normal weiterverarbeiten
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->unauthorizedResponse();
    }

    /**
     * Wird aufgerufen, wenn ein geschützter Endpunkt ohne gültige
     * Credentials aufgerufen wird. Der WWW-Authenticate-Header erlaubt
     * Clients (z.B. Vibe/Mistral) die automatische Erkennung der
     * Bearer-Token-Authentifizierung.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return $this->unauthorizedResponse();
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'Authentifizierung erforderlich', 'message' => 'Gültiger MCP-Token im Authorization-Header (Bearer) erforderlich.'],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => 'Bearer']
        );
    }
}
