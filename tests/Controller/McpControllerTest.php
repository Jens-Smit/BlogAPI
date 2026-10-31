<?php
// tests/Controller/McpControllerTest.php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\McpToken;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Funktionale Tests für den MCP-Endpunkt (POST /api/mcp).
 *
 * Deckt ab: Authentifizierung (Bearer MCP-Token), JSON-RPC-Handshake
 * (initialize, ping), Tool-Discovery (tools/list), den vollständigen
 * Beitrags-Workflow (create_category -> upload_image -> create_blog_post)
 * sowie Fehlerfälle (ungültiger/widerrufener Token, unbekanntes Tool).
 */
class McpControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $em;
    private string $uploadDirectory;
    private array $uploadedFiles = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
        $this->uploadDirectory = $this->client->getContainer()->getParameter('app.upload_directory');

        // Upload-Verzeichnis sicherstellen
        if (!is_dir($this->uploadDirectory)) {
            @mkdir($this->uploadDirectory, 0777, true);
        }

        // Aufräumen: Posts, Categories, McpTokens, Users
        $this->em->createQuery('DELETE FROM App\Entity\Post p')->execute();
        $this->em->getConnection()->executeStatement('UPDATE category SET parent_id = NULL');
        $this->em->createQuery('DELETE FROM App\Entity\Category c')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\McpToken t')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    protected function tearDown(): void
    {
        // Hochgeladene Testbilder entfernen
        foreach ($this->uploadedFiles as $filename) {
            $path = $this->uploadDirectory . '/' . $filename;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        if ($this->em && $this->em->isOpen()) {
            $this->em->close();
        }
        $this->em = null;
        $this->client = null;
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Hilfsmethoden
    // ------------------------------------------------------------------

    private function createAndPersistUser(string $email = null): User
    {
        $email = $email ?? 'mcp_user_' . uniqid() . '@example.com';

        $user = new User();
        $user->setEmail($email);

        if (method_exists($user, 'setPassword')) {
            $user->setPassword('dummyhash');
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Erstellt einen echten MCP-Token für einen Benutzer und gibt den
     * Klartext-Token zurück.
     */
    private function createMcpToken(User $user, string $name = 'TestClient'): string
    {
        $plainToken = McpToken::generateTokenValue();

        $mcpToken = new McpToken();
        $mcpToken->setUser($user);
        $mcpToken->setName($name);
        $mcpToken->setTokenHash(McpToken::hashToken($plainToken));
        $mcpToken->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($mcpToken);
        $this->em->flush();

        return $plainToken;
    }

    /**
     * Sendet eine JSON-RPC-Nachricht an den MCP-Endpunkt.
     *
     * @param array|string $payload
     */
    private function sendRpc($payload, ?string $token = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request(
            'POST',
            '/api/mcp',
            [],
            [],
            $server,
            is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_SLASHES)
        );
    }

    private function decodeResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    private function initializeMessage(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'TestClient', 'version' => '1.0.0'],
            ],
        ];
    }

    /**
     * Ruft ein Tool auf und gibt das dekodierte Tool-Ergebnis zurück.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    private function callTool(string $name, array $arguments, string $token): array
    {
        $this->sendRpc([
            'jsonrpc' => '2.0',
            'id' => 42,
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => (object) $arguments,
            ],
        ], $token);

        $response = $this->decodeResponse();
        $this->assertArrayHasKey('result', $response, 'Tool-Aufruf sollte ein Resultat liefern: ' . json_encode($response));

        return $response['result'];
    }

    // ------------------------------------------------------------------
    // Authentifizierung
    // ------------------------------------------------------------------

    public function testInitializeRequiresAuthentication(): void
    {
        $this->sendRpc($this->initializeMessage());

        $this->assertResponseStatusCodeSame(401);
        $this->assertSame('Bearer', $this->client->getResponse()->headers->get('WWW-Authenticate'));
    }

    public function testInvalidTokenIsRejected(): void
    {
        $this->sendRpc($this->initializeMessage(), 'mcp_invalidtokeninvalidtokeninvalid12');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testTokenWithoutPrefixIsRejected(): void
    {
        $user = $this->createAndPersistUser();
        // Token-Hash ist korrekt, aber das Präfix fehlt -> darf nicht akzeptiert werden
        $plainToken = McpToken::generateTokenValue();

        $mcpToken = new McpToken();
        $mcpToken->setUser($user);
        $mcpToken->setName('no-prefix');
        $mcpToken->setTokenHash(McpToken::hashToken($plainToken));
        $mcpToken->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($mcpToken);
        $this->em->flush();

        $this->sendRpc($this->initializeMessage(), 'wrongprefix_' . substr($plainToken, 4));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRevokedTokenIsRejected(): void
    {
        $user = $this->createAndPersistUser();
        $plainToken = $this->createMcpToken($user);

        /** @var McpToken $mcpToken */
        $mcpToken = $this->em->getRepository(McpToken::class)->findOneBy(['name' => 'TestClient']);
        $mcpToken->revoke();
        $this->em->flush();

        $this->sendRpc($this->initializeMessage(), $plainToken);

        $this->assertResponseStatusCodeSame(401);
    }

    // ------------------------------------------------------------------
    // JSON-RPC Handshake
    // ------------------------------------------------------------------

    public function testInitializeHandshake(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->sendRpc($this->initializeMessage(), $token);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeResponse();

        $this->assertSame('2.0', $response['jsonrpc']);
        $this->assertSame(1, $response['id']);
        $this->assertSame('2025-06-18', $response['result']['protocolVersion']);
        $this->assertSame('BlogAPI MCP', $response['result']['serverInfo']['name']);
        $this->assertArrayHasKey('tools', $response['result']['capabilities']);
    }

    public function testInitializeNegotiatesOlderProtocolVersion(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $message = $this->initializeMessage();
        $message['params']['protocolVersion'] = '2025-03-26';

        $this->sendRpc($message, $token);

        $this->assertResponseIsSuccessful();
        $this->assertSame('2025-03-26', $this->decodeResponse()['result']['protocolVersion']);
    }

    public function testUnsupportedProtocolVersionFallsBackToLatest(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $message = $this->initializeMessage();
        $message['params']['protocolVersion'] = '1999-01-01';

        $this->sendRpc($message, $token);

        $this->assertResponseIsSuccessful();
        $this->assertSame('2025-06-18', $this->decodeResponse()['result']['protocolVersion']);
    }

    public function testPing(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->sendRpc(['jsonrpc' => '2.0', 'id' => 7, 'method' => 'ping'], $token);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeResponse();
        $this->assertSame(7, $response['id']);
        $this->assertSame([], $response['result']);
    }

    public function testNotificationReturnsAcceptedWithoutBody(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->sendRpc(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $token);

        $this->assertResponseStatusCodeSame(202);
        $this->assertSame('', $this->client->getResponse()->getContent());
    }

    public function testUnknownMethodReturnsJsonRpcError(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->sendRpc(['jsonrpc' => '2.0', 'id' => 9, 'method' => 'resources/list'], $token);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeResponse();
        $this->assertSame(-32601, $response['error']['code']);
    }

    public function testInvalidJsonReturnsParseError(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->sendRpc('{invalid json', $token);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(-32700, $this->decodeResponse()['error']['code']);
    }

    public function testGetEndpointReturnsMethodNotAllowed(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->client->request('GET', '/api/mcp', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(405);
    }

    // ------------------------------------------------------------------
    // Tool-Discovery
    // ------------------------------------------------------------------

    public function testToolsListContainsBlogTools(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->sendRpc(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/list'], $token);

        $this->assertResponseIsSuccessful();
        $tools = $this->decodeResponse()['result']['tools'];
        $names = array_column($tools, 'name');

        $this->assertContains('create_blog_post', $names);
        $this->assertContains('upload_image', $names);
        $this->assertContains('list_categories', $names);
        $this->assertContains('create_category', $names);
        $this->assertContains('list_posts', $names);
        $this->assertContains('get_post', $names);
        $this->assertContains('update_blog_post', $names);
        $this->assertContains('delete_blog_post', $names);

        foreach ($tools as $tool) {
            $this->assertArrayHasKey('description', $tool);
            $this->assertArrayHasKey('inputSchema', $tool);
            $this->assertSame('object', $tool['inputSchema']['type']);
        }
    }

    public function testUnknownToolReturnsInvalidParams(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->sendRpc([
            'jsonrpc' => '2.0',
            'id' => 77,
            'method' => 'tools/call',
            'params' => ['name' => 'does_not_exist', 'arguments' => new \stdClass()],
        ], $token);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeResponse();
        $this->assertSame(-32602, $response['error']['code']);
        $this->assertStringContainsString('Unknown tool', $response['error']['message']);
    }

    // ------------------------------------------------------------------
    // Beitrags-Workflow (create_category -> upload_image -> create_blog_post)
    // ------------------------------------------------------------------

    public function testFullBlogPostWorkflow(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        // 1. Kategorie erstellen
        $categoryResult = $this->callTool('create_category', ['name' => 'MCP Testkategorie'], $token);
        $this->assertFalse($categoryResult['isError']);
        $payload = json_decode($categoryResult['content'][0]['text'], true);
        $this->assertNotEmpty($payload['id']);
        $categoryId = $payload['id'];

        // 2. Beitragsbild hochladen (echtes 1x1 PNG als Base64)
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $uploadResult = $this->callTool('upload_image', [
            'base64Data' => $pngBase64,
            'filename' => 'titelbild',
        ], $token);
        $this->assertFalse($uploadResult['isError']);
        $uploadPayload = json_decode($uploadResult['content'][0]['text'], true);
        $this->assertNotEmpty($uploadPayload['filename']);
        $this->assertStringContainsString('/uploads/', $uploadPayload['url']);
        $this->assertFileExists($this->uploadDirectory . '/' . $uploadPayload['filename']);
        $this->uploadedFiles[] = $uploadPayload['filename'];

        // 3. Blogbeitrag erstellen
        $contentHtml = '<p>Hallo <strong>MCP</strong>!</p><p><img src="' . $uploadPayload['url'] . '" alt="Titelbild"></p>';
        $postResult = $this->callTool('create_blog_post', [
            'title' => 'Mein MCP-Beitrag',
            'content' => $contentHtml,
            'categoryId' => $categoryId,
            'titleImageFilename' => $uploadPayload['filename'],
        ], $token);
        $this->assertFalse($postResult['isError'], json_encode($postResult));
        $postPayload = json_decode($postResult['content'][0]['text'], true);
        $this->assertNotEmpty($postPayload['id']);
        $this->assertSame('mein-mcp-beitrag', $postPayload['slug']);
        $this->assertSame($uploadPayload['filename'], basename($postPayload['titleImageUrl']));

        // 4. Persistierung prüfen
        /** @var Post $post */
        $post = $this->em->getRepository(Post::class)->find($postPayload['id']);
        $this->assertNotNull($post);
        $this->assertSame('Mein MCP-Beitrag', $post->getTitle());
        $this->assertSame($user->getId(), $post->getAuthor()->getId());
        $this->assertSame($uploadPayload['filename'], $post->getTitleImage());
        $this->assertStringContainsString('Hallo <strong>MCP</strong>!', $post->getContent());
        $this->assertStringContainsString('<img', $post->getContent());

        // 5. list_posts
        $listResult = $this->callTool('list_posts', ['limit' => 5], $token);
        $listPayload = json_decode($listResult['content'][0]['text'], true);
        $this->assertCount(1, $listPayload['posts']);
        $this->assertSame('Mein MCP-Beitrag', $listPayload['posts'][0]['title']);

        // 6. get_post per Slug
        $getResult = $this->callTool('get_post', ['identifier' => $postPayload['slug']], $token);
        $getPayload = json_decode($getResult['content'][0]['text'], true);
        $this->assertSame('Mein MCP-Beitrag', $getPayload['title']);
        $this->assertSame('MCP Testkategorie', $getPayload['categoryName']);
    }

    public function testCreateBlogPostSanitizesContent(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $category = new Category();
        $category->setName('Sicherheitskategorie');
        $this->em->persist($category);
        $this->em->flush();

        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $uploadResult = $this->callTool('upload_image', ['base64Data' => $pngBase64], $token);
        $uploadPayload = json_decode($uploadResult['content'][0]['text'], true);
        $this->uploadedFiles[] = $uploadPayload['filename'];

        $postResult = $this->callTool('create_blog_post', [
            'title' => 'XSS Schutz Test',
            'content' => '<p>Lt. Test</p><script>alert("xss")</script><p onclick="evil()">Klick</p>',
            'categoryId' => $category->getId(),
            'titleImageFilename' => $uploadPayload['filename'],
        ], $token);
        $this->assertFalse($postResult['isError']);
        $postPayload = json_decode($postResult['content'][0]['text'], true);

        /** @var Post $post */
        $post = $this->em->getRepository(Post::class)->find($postPayload['id']);
        $this->assertStringNotContainsString('script', $post->getContent());
        $this->assertStringNotContainsString('onclick', $post->getContent());
        $this->assertStringContainsString('Lt. Test', $post->getContent());
    }

    public function testUploadImageRejectsInvalidBase64(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $this->sendRpc([
            'jsonrpc' => '2.0',
            'id' => 88,
            'method' => 'tools/call',
            'params' => ['name' => 'upload_image', 'arguments' => ['base64Data' => 'kein-base64-%%!!']],
        ], $token);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeResponse();
        $this->assertSame(-32602, $response['error']['code']);
    }

    public function testCreateBlogPostRequiresTitleImageFilename(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $category = new Category();
        $category->setName('Pflichtfelder');
        $this->em->persist($category);
        $this->em->flush();

        // titleImageFilename fehlt
        $this->sendRpc([
            'jsonrpc' => '2.0',
            'id' => 55,
            'method' => 'tools/call',
            'params' => [
                'name' => 'create_blog_post',
                'arguments' => [
                    'title' => 'Ohne Bild',
                    'content' => '<p>Inhalt</p>',
                    'categoryId' => $category->getId(),
                ],
            ],
        ], $token);

        $response = $this->decodeResponse();
        $this->assertSame(-32602, $response['error']['code']);
    }

    public function testDeleteBlogPostOnlyByAuthor(): void
    {
        $author = $this->createAndPersistUser('author@example.com');
        $otherUser = $this->createAndPersistUser('other@example.com');
        $authorToken = $this->createMcpToken($author);
        $otherToken = $this->createMcpToken($otherUser, 'OtherClient');

        $category = new Category();
        $category->setName('Autorprüfung');
        $this->em->persist($category);

        $post = new Post();
        $post->setTitle('Fremder Beitrag');
        $post->setContent('<p>Inhalt</p>');
        $post->setAuthor($author);
        $post->setSlug('fremder-beitrag');
        $post->setCategory($category);
        $post->setCreatedAt(new \DateTime());
        $this->em->persist($post);
        $this->em->flush();

        // Anderer Benutzer darf nicht löschen
        $result = $this->callTool('delete_blog_post', ['id' => $post->getId()], $otherToken);
        $this->assertTrue($result['isError']);
        $errorPayload = json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('Berechtigung', $errorPayload['error']);

        // Autor darf löschen
        $deleteResult = $this->callTool('delete_blog_post', ['id' => $post->getId()], $authorToken);
        $this->assertFalse($deleteResult['isError']);

        $this->em->clear();
        $this->assertNull($this->em->getRepository(Post::class)->find($post->getId()));
    }

    public function testUpdateBlogPost(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createMcpToken($user);

        $category = new Category();
        $category->setName('Updatekategorie');
        $this->em->persist($category);

        $post = new Post();
        $post->setTitle('Alter Titel');
        $post->setContent('<p>Alter Inhalt</p>');
        $post->setAuthor($user);
        $post->setSlug('alter-titel');
        $post->setCategory($category);
        $post->setCreatedAt(new \DateTime());
        $this->em->persist($post);
        $this->em->flush();

        $result = $this->callTool('update_blog_post', [
            'id' => $post->getId(),
            'title' => 'Neuer Titel',
            'content' => '<p>Neuer Inhalt</p>',
        ], $token);
        $this->assertFalse($result['isError']);

        $this->em->clear();
        /** @var Post $updated */
        $updated = $this->em->getRepository(Post::class)->find($post->getId());
        $this->assertSame('Neuer Titel', $updated->getTitle());
        $this->assertSame('neuer-titel', $updated->getSlug());
        $this->assertStringContainsString('Neuer Inhalt', $updated->getContent());
    }
}
