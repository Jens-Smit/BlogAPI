<?php
// tests/Controller/McpTokenControllerTest.php

namespace App\Tests\Controller;

use App\Entity\McpToken;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Funktionale Tests für die Frontend-Verwaltung der eigenen MCP-Tokens
 * (POST/GET/DELETE /api/mcp/tokens, JWT-Cookie-Authentifizierung).
 *
 * Deckt ab: Authentifizierungszwang, einmalige Rückgabe des Klartext-Tokens,
 * Validierung, Besitzprüfung (kein Zugriff auf fremde Tokens) sowie die
 * Wirkung eines Widerrufs auf den MCP-Endpunkt.
 *
 * Hinweis zur Teststruktur: Die api-Firewall ist stateless. Der über
 * loginUser() gesetzte Token liegt im untracked_token_storage des aktuellen
 * Containers und überlebt den Kernel-Reboot zwischen zwei Requests nicht.
 * Jeder Test stellt daher maximal EINEN authentifizierten Request -
 * identisch zur Konvention in PostControllerTest und McpControllerTest.
 */
class McpTokenControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();

        // Aufräumen in FK-sicherer Reihenfolge: Posts referenzieren User,
        // Kategorien können sich selbst referenzieren (parent_id).
        $this->em->createQuery('DELETE FROM App\Entity\Post p')->execute();
        $this->em->getConnection()->executeStatement('UPDATE category SET parent_id = NULL');
        $this->em->createQuery('DELETE FROM App\Entity\Category c')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\McpToken t')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    protected function tearDown(): void
    {
        if ($this->em && $this->em->isOpen()) {
            $this->em->close();
        }
        $this->em = null;
        $this->client = null;
        parent::tearDown();
    }

    private function createAndPersistUser(string $email = null): User
    {
        $email = $email ?? 'mcp_api_user_' . uniqid() . '@example.com';

        $user = new User();
        $user->setEmail($email);
        $user->setUsername('user_' . uniqid());

        if (method_exists($user, 'setPassword')) {
            $user->setPassword('dummyhash');
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function jsonRequest(string $method, string $uri, ?array $body = null): void
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body !== null ? json_encode($body) : null
        );
    }

    public function testCreateTokenRequiresAuthentication(): void
    {
        $this->jsonRequest('POST', '/api/mcp/tokens', ['name' => 'Vibe']);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateTokenReturnsPlaintextOnce(): void
    {
        $user = $this->createAndPersistUser();
        $this->client->loginUser($user);

        $this->jsonRequest('POST', '/api/mcp/tokens', ['name' => 'Vibe']);

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);
        $this->assertStringStartsWith('mcp_', $data['token']);
        $this->assertSame(52, strlen($data['token']));
        $this->assertSame('Vibe', $data['name']);
        $this->assertArrayHasKey('createdAt', $data);

        // In der Datenbank liegt nur der SHA-256-Hash, nie der Klartext.
        $stored = $this->em->getRepository(McpToken::class)->find($data['id']);
        $this->assertNotNull($stored);
        $this->assertSame(hash('sha256', $data['token']), $stored->getTokenHash());
        $this->assertSame($user->getId(), $stored->getUser()->getId());
    }

    public function testCreateTokenRejectsTooShortName(): void
    {
        $user = $this->createAndPersistUser();
        $this->client->loginUser($user);

        $this->jsonRequest('POST', '/api/mcp/tokens', ['name' => 'a']);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('name', $this->client->getResponse()->getContent());
    }

    public function testCreateTokenRejectsMissingName(): void
    {
        $user = $this->createAndPersistUser();
        $this->client->loginUser($user);

        $this->jsonRequest('POST', '/api/mcp/tokens', []);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateTokenRejectsInvalidJson(): void
    {
        $user = $this->createAndPersistUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/api/mcp/tokens', [], [], ['CONTENT_TYPE' => 'application/json'], 'kein json');

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testListTokensReturnsOnlyOwnActiveTokens(): void
    {
        $user1 = $this->createAndPersistUser('owner1@example.com');
        $user2 = $this->createAndPersistUser('owner2@example.com');

        foreach (['Client A', 'Client B'] as $tokenName) {
            $mcpToken = new McpToken();
            $mcpToken->setUser($user1);
            $mcpToken->setName($tokenName);
            $mcpToken->setTokenHash(McpToken::hashToken(McpToken::generateTokenValue()));
            $mcpToken->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($mcpToken);
        }

        $foreignToken = new McpToken();
        $foreignToken->setUser($user2);
        $foreignToken->setName('Fremd');
        $foreignToken->setTokenHash(McpToken::hashToken(McpToken::generateTokenValue()));
        $foreignToken->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($foreignToken);

        $this->em->flush();

        $this->client->loginUser($user1);
        $this->jsonRequest('GET', '/api/mcp/tokens');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $data['tokens']);
        $names = array_column($data['tokens'], 'name');
        sort($names);
        $this->assertSame(['Client A', 'Client B'], $names);

        foreach ($data['tokens'] as $token) {
            $this->assertArrayNotHasKey('token', $token);
            $this->assertArrayHasKey('createdAt', $token);
        }
    }

    public function testListTokensRequiresAuthentication(): void
    {
        $this->jsonRequest('GET', '/api/mcp/tokens');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testRevokeOwnTokenInvalidatesMcpAccess(): void
    {
        $user = $this->createAndPersistUser();
        $plainToken = McpToken::generateTokenValue();

        $mcpToken = new McpToken();
        $mcpToken->setUser($user);
        $mcpToken->setName('Zu widerrufen');
        $mcpToken->setTokenHash(McpToken::hashToken($plainToken));
        $mcpToken->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($mcpToken);
        $this->em->flush();

        // Widerruf über die Frontend-API. Dies ist der einzige authentifizierte
        // Request des Tests (stateless Firewall, siehe Klassen-DocBlock); dass
        // der Token vorher am MCP-Endpunkt gültig war, deckt der
        // McpControllerTest ab.
        $this->client->loginUser($user);
        $this->client->request('DELETE', '/api/mcp/tokens/' . $mcpToken->getId());
        $this->assertResponseIsSuccessful();

        // Nach dem Widerruf weist der MCP-Endpunkt den Token ab. Die
        // Bearer-Authentifizierung am MCP-Endpunkt ist von loginUser()
        // unabhängig und funktioniert auch nach dem Kernel-Reboot.
        $this->client->request('POST', '/api/mcp', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $plainToken,
        ], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping']));
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testRevokeForeignTokenReturns404(): void
    {
        $owner = $this->createAndPersistUser('token-owner@example.com');
        $attacker = $this->createAndPersistUser('attacker@example.com');

        $mcpToken = new McpToken();
        $mcpToken->setUser($owner);
        $mcpToken->setName('Owner-Token');
        $mcpToken->setTokenHash(McpToken::hashToken(McpToken::generateTokenValue()));
        $mcpToken->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($mcpToken);
        $this->em->flush();

        $this->client->loginUser($attacker);
        $this->client->request('DELETE', '/api/mcp/tokens/' . $mcpToken->getId());

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        // Der Token des Owners bleibt unberührt und aktiv.
        $this->em->clear();
        $stored = $this->em->getRepository(McpToken::class)->find($mcpToken->getId());
        $this->assertFalse($stored->isRevoked());
    }

    public function testRevokeUnknownTokenReturns404(): void
    {
        $user = $this->createAndPersistUser();
        $this->client->loginUser($user);

        $this->client->request('DELETE', '/api/mcp/tokens/99999');

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
