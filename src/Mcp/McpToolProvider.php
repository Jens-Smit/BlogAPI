<?php
// src/Mcp/McpToolProvider.php

namespace App\Mcp;

use App\DTO\CategoryCreateDTO;
use App\DTO\PostUpdateDTO;
use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Service\CategoryService;
use App\Service\PostService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Definiert und implementiert die MCP-Tools der BlogAPI.
 *
 * Alle Tools werden im Kontext des authentifizierten Benutzers ausgeführt
 * (Autor-Prüfungen wie in den REST-Controllern). Die Tool-Definitionen
 * (inputSchema) folgen der JSON-Schema-Spezifikation des MCP-Standards.
 */
class McpToolProvider
{
    private const MAX_POSTS_LIMIT = 50;
    private const MAX_IMAGE_DOWNLOAD_SIZE = 5242880; // 5MB

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PostService $postService,
        private readonly CategoryService $categoryService,
        private readonly string $appUrl
    ) {}

    /**
     * @return array<int, array{name: string, description: string, inputSchema: array}>
     */
    public function definitions(): array
    {
        return [
            [
                'name' => 'list_categories',
                'description' => 'Listet alle Blog-Kategorien mit IDs auf. Die categoryId wird für create_blog_post benötigt.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'create_category',
                'description' => 'Erstellt eine neue Blog-Kategorie. Gibt die ID zurück.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'Name der Kategorie (2-255 Zeichen)'],
                        'parentId' => ['type' => 'integer', 'description' => 'Optionale ID einer Eltern-Kategorie'],
                    ],
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'upload_image',
                'description' => 'Lädt ein Bild hoch und gibt Dateiname sowie URL zurück. Das Bild wird als Base64-Zeichenkette (base64Data) oder als http(s)-URL (imageUrl) übergeben. Erlaubte Typen: JPEG, PNG, GIF, WebP. Maximal 5 MB. Der zurückgegebene filename wird als titleImageFilename für create_blog_post verwendet.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'base64Data' => ['type' => 'string', 'description' => 'Bildinhalt als Base64-kodierte Zeichenkette'],
                        'imageUrl' => ['type' => 'string', 'description' => 'http(s)-URL eines direkt herunterladbaren Bildes'],
                        'filename' => ['type' => 'string', 'description' => 'Optional: gewünschter Basis-Dateiname (ohne Endung), z.B. "beitragsbild"'],
                    ],
                ],
            ],
            [
                'name' => 'create_blog_post',
                'description' => 'Erstellt einen neuen Blogbeitrag mit Titel, HTML-Inhalt, Kategorie und Beitragsbild. Das Beitragsbild muss zuvor mit upload_image hochgeladen worden sein; übergebe dessen filename als titleImageFilename. Bilder im Inhalt können über <img src="{url}"> eingebunden werden, wobei {url} die von upload_image zurückgegebene URL ist. Erlaubte HTML-Tags: p, br, strong, em, u, a[href], img[src|alt|style], h2, h3, ul, ol, li.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'Titel des Beitrags'],
                        'content' => ['type' => 'string', 'description' => 'Inhalt des Beitrags als HTML'],
                        'categoryId' => ['type' => 'integer', 'description' => 'ID der Kategorie (siehe list_categories)'],
                        'titleImageFilename' => ['type' => 'string', 'description' => 'Dateiname des Beitragsbilds (Rückgabewert von upload_image)'],
                        'additionalImageFilenames' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Optionale weitere, bereits hochgeladene Bilder',
                        ],
                    ],
                    'required' => ['title', 'content', 'categoryId', 'titleImageFilename'],
                ],
            ],
            [
                'name' => 'list_posts',
                'description' => 'Listet die neuesten Blogbeiträge mit ID, Titel, Slug, Kategorie und Datum auf.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'description' => 'Maximale Anzahl (1-50, Standard 10)'],
                    ],
                ],
            ],
            [
                'name' => 'get_post',
                'description' => 'Ruft einen einzelnen Blogbeitrag per ID oder Slug ab, inklusive Inhalt.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'identifier' => ['type' => 'string', 'description' => 'ID oder Slug des Beitrags'],
                    ],
                    'required' => ['identifier'],
                ],
            ],
            [
                'name' => 'update_blog_post',
                'description' => 'Aktualisiert einen eigenen Blogbeitrag (Titel, Inhalt und/oder Kategorie). Nur der Autor kann seinen Beitrag ändern.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'ID des Beitrags'],
                        'title' => ['type' => 'string', 'description' => 'Neuer Titel (optional)'],
                        'content' => ['type' => 'string', 'description' => 'Neuer HTML-Inhalt (optional)'],
                        'categoryId' => ['type' => 'integer', 'description' => 'Neue Kategorie-ID (optional)'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'delete_blog_post',
                'description' => 'Löscht einen eigenen Blogbeitrag inklusive aller zugehörigen Bilddateien. Nur der Autor kann seinen Beitrag löschen.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'ID des Beitrags'],
                    ],
                    'required' => ['id'],
                ],
            ],
        ];
    }

    /**
     * Führt ein Tool aus und gibt das Ergebnis als Array zurück.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     * @throws \InvalidArgumentException bei ungültigen oder unvollständigen Argumenten
     * @throws \RuntimeException bei fachlichen Fehlern (wird als Tool-Fehler gemeldet)
     */
    public function call(string $name, array $arguments, User $user): array
    {
        switch ($name) {
            case 'list_categories':
                return $this->listCategories();

            case 'create_category':
                return $this->createCategory($arguments);

            case 'upload_image':
                return $this->uploadImage($arguments);

            case 'create_blog_post':
                return $this->createBlogPost($arguments, $user);

            case 'list_posts':
                return $this->listPosts($arguments);

            case 'get_post':
                return $this->getPost($arguments);

            case 'update_blog_post':
                return $this->updateBlogPost($arguments, $user);

            case 'delete_blog_post':
                return $this->deleteBlogPost($arguments, $user);
        }

        throw new \InvalidArgumentException(sprintf('Unknown tool: %s', $name));
    }

    // ------------------------------------------------------------------
    // Tool-Implementierungen
    // ------------------------------------------------------------------

    private function listCategories(): array
    {
        $categories = $this->em->getRepository(Category::class)->findAll();

        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'parentId' => $category->getParent()?->getId(),
                'postCount' => $category->getPosts()->count(),
            ];
        }

        return ['categories' => $result];
    }

    private function createCategory(array $arguments): array
    {
        $name = $arguments['name'] ?? null;
        if (!is_string($name) || trim($name) === '' || mb_strlen($name) < 2) {
            throw new \InvalidArgumentException('name: Zeichenkette mit mindestens 2 Zeichen erforderlich.');
        }

        $parentId = $arguments['parentId'] ?? null;
        if ($parentId !== null && !is_int($parentId)) {
            throw new \InvalidArgumentException('parentId: Ganzzahl erwartet.');
        }

        $category = $this->categoryService->createCategory(
            new CategoryCreateDTO(trim($name), $parentId)
        );

        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'parentId' => $category->getParent()?->getId(),
        ];
    }

    private function uploadImage(array $arguments): array
    {
        $base64Data = $arguments['base64Data'] ?? null;
        $imageUrl = $arguments['imageUrl'] ?? null;

        $hasBase64 = is_string($base64Data) && $base64Data !== '';
        $hasUrl = is_string($imageUrl) && $imageUrl !== '';

        if ($hasBase64 === $hasUrl) {
            throw new \InvalidArgumentException('Genau eines von base64Data oder imageUrl ist erforderlich.');
        }

        $filename = is_string($arguments['filename'] ?? null) && $arguments['filename'] !== ''
            ? (string) $arguments['filename']
            : 'bild';

        $data = null;

        if ($hasBase64) {
            $data = base64_decode($base64Data, true);
            if ($data === false) {
                throw new \InvalidArgumentException('base64Data: Ungültige Base64-Zeichenkette.');
            }
        } else {
            $data = $this->downloadImage($imageUrl);
        }

        $storedFilename = $this->postService->storeImageData($data, $filename);

        return [
            'filename' => $storedFilename,
            'url' => $this->publicUploadUrl($storedFilename),
        ];
    }

    private function createBlogPost(array $arguments, User $user): array
    {
        $title = $arguments['title'] ?? null;
        if (!is_string($title) || trim($title) === '') {
            throw new \InvalidArgumentException('title: Nicht-leere Zeichenkette erforderlich.');
        }

        $content = $arguments['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            throw new \InvalidArgumentException('content: Nicht-leere Zeichenkette erforderlich.');
        }

        $categoryId = $arguments['categoryId'] ?? null;
        if (!is_int($categoryId) || $categoryId < 1) {
            throw new \InvalidArgumentException('categoryId: Ganzzahl erforderlich (siehe list_categories).');
        }

        $titleImageFilename = $arguments['titleImageFilename'] ?? null;
        if (!is_string($titleImageFilename) || $titleImageFilename === '') {
            throw new \InvalidArgumentException('titleImageFilename: Zeichenkette erforderlich (Rückgabewert von upload_image).');
        }

        $additional = $arguments['additionalImageFilenames'] ?? [];
        if (!is_array($additional)) {
            throw new \InvalidArgumentException('additionalImageFilenames: Array von Dateinamen erwartet.');
        }

        $post = $this->postService->createPostFromStoredImages(
            trim($title),
            $content,
            $categoryId,
            $titleImageFilename,
            array_values(array_map('strval', $additional)),
            $user
        );

        return [
            'id' => $post->getId(),
            'slug' => $post->getSlug(),
            'title' => $post->getTitle(),
            'titleImageUrl' => $post->getTitleImage() ? $this->publicUploadUrl($post->getTitleImage()) : null,
            'message' => 'Blogbeitrag erfolgreich erstellt.',
        ];
    }

    private function listPosts(array $arguments): array
    {
        $limit = $arguments['limit'] ?? 10;
        if (!is_int($limit) || $limit < 1) {
            $limit = 10;
        }
        $limit = min($limit, self::MAX_POSTS_LIMIT);

        $posts = $this->em->getRepository(Post::class)->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit
        );

        return ['posts' => array_map(fn (Post $post) => $this->postSummary($post), $posts)];
    }

    private function getPost(array $arguments): array
    {
        $identifier = $arguments['identifier'] ?? null;
        if (!is_string($identifier) || $identifier === '') {
            throw new \InvalidArgumentException('identifier: ID oder Slug als Zeichenkette erforderlich.');
        }

        $post = $this->findPost($identifier);
        if (!$post) {
            throw new \RuntimeException('Beitrag nicht gefunden: ' . $identifier);
        }

        return array_merge($this->postSummary($post), [
            'content' => $post->getContent(),
            'images' => array_map(
                fn (string $filename) => $this->publicUploadUrl($filename),
                $post->getImages() ?? []
            ),
        ]);
    }

    private function updateBlogPost(array $arguments, User $user): array
    {
        $id = $arguments['id'] ?? null;
        if (!is_int($id) || $id < 1) {
            throw new \InvalidArgumentException('id: Ganzzahl erforderlich.');
        }

        $post = $this->em->getRepository(Post::class)->find($id);
        if (!$post) {
            throw new \RuntimeException('Beitrag nicht gefunden: ' . $id);
        }

        if ($post->getAuthor() !== $user) {
            throw new \RuntimeException('Keine Berechtigung: Nur der Autor kann den Beitrag bearbeiten.');
        }

        $title = $arguments['title'] ?? $post->getTitle();
        if (!is_string($title) || trim($title) === '') {
            throw new \InvalidArgumentException('title: Nicht-leere Zeichenkette erforderlich.');
        }

        $content = $arguments['content'] ?? $post->getContent();
        if (!is_string($content) || trim($content) === '') {
            throw new \InvalidArgumentException('content: Nicht-leere Zeichenkette erforderlich.');
        }

        $categoryId = $arguments['categoryId'] ?? null;
        if ($categoryId !== null && !is_int($categoryId)) {
            throw new \InvalidArgumentException('categoryId: Ganzzahl erwartet.');
        }

        $updatedPost = $this->postService->updatePost(
            $post,
            new PostUpdateDTO($id, trim($title), $content, $categoryId, null, [])
        );

        return [
            'id' => $updatedPost->getId(),
            'slug' => $updatedPost->getSlug(),
            'title' => $updatedPost->getTitle(),
            'message' => 'Blogbeitrag erfolgreich aktualisiert.',
        ];
    }

    private function deleteBlogPost(array $arguments, User $user): array
    {
        $id = $arguments['id'] ?? null;
        if (!is_int($id) || $id < 1) {
            throw new \InvalidArgumentException('id: Ganzzahl erforderlich.');
        }

        $post = $this->em->getRepository(Post::class)->find($id);
        if (!$post) {
            throw new \RuntimeException('Beitrag nicht gefunden: ' . $id);
        }

        if ($post->getAuthor() !== $user) {
            throw new \RuntimeException('Keine Berechtigung: Nur der Autor kann den Beitrag löschen.');
        }

        $slug = $post->getSlug();
        $this->postService->deletePost($post);

        return [
            'id' => $id,
            'slug' => $slug,
            'message' => 'Blogbeitrag erfolgreich gelöscht.',
        ];
    }

    // ------------------------------------------------------------------
    // Hilfsmethoden
    // ------------------------------------------------------------------

    private function findPost(string $identifier): ?Post
    {
        if (ctype_digit($identifier)) {
            return $this->em->getRepository(Post::class)->find((int) $identifier);
        }

        return $this->em->getRepository(Post::class)->findOneBy(['slug' => $identifier]);
    }

    /**
     * @return array<string, mixed>
     */
    private function postSummary(Post $post): array
    {
        return [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'slug' => $post->getSlug(),
            'categoryId' => $post->getCategory()?->getId(),
            'categoryName' => $post->getCategory()?->getName(),
            'createdAt' => $post->getCreatedAt()?->format('Y-m-d H:i:s'),
            'titleImageUrl' => $post->getTitleImage() ? $this->publicUploadUrl($post->getTitleImage()) : null,
        ];
    }

    private function publicUploadUrl(string $filename): string
    {
        return rtrim($this->appUrl, '/') . '/uploads/' . $filename;
    }

    /**
     * Lädt ein Bild von einer http(s)-URL herunter.
     *
     * Sicherheitsmaßnahmen: nur http/https, harte Größenbegrenzung,
     * Weiterleitungsbegrenzung und Zeitlimit.
     */
    private function downloadImage(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('imageUrl: Nur http(s)-URLs sind erlaubt.');
        }

        $client = HttpClient::create([
            'timeout' => 15,
            'max_duration' => 20,
        ]);

        try {
            $response = $client->request('GET', $url);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException(sprintf('Download fehlgeschlagen: HTTP %d', $response->getStatusCode()));
            }

            // getContent(false): keine Exception bei HTTP-Fehlern (Status bereits geprüft)
            $content = $response->getContent(false);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            throw new \RuntimeException('Download fehlgeschlagen: ' . $e->getMessage());
        }

        if (strlen($content) > self::MAX_IMAGE_DOWNLOAD_SIZE) {
            throw new \RuntimeException('Bild überschreitet die maximale Größe von 5 MB.');
        }

        return $content;
    }
}
