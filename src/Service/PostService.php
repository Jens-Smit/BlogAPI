<?php
// src/Service/PostService.php

namespace App\Service;

use App\DTO\PostCreateDTO;
use App\DTO\PostUpdateDTO;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PostService
{
    private EntityManagerInterface $em;
    private string $projectDir;
    private string $uploadDirectory;
    private SluggerInterface $slugger;
    private string $apiUrl;

    public function __construct(
        EntityManagerInterface $em,
        string $projectDir,
        string $uploadDirectory,
        SluggerInterface $slugger,
        string $apiUrl
    ) {
        $this->em = $em;
        $this->projectDir = $projectDir;
        $this->uploadDirectory = $uploadDirectory;
        $this->slugger = $slugger;
        $this->apiUrl = $apiUrl;
    }
    private function sanitizeContent(string $content): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],img[src|alt|style],h2,h3,ul,ol,li');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);

        // Erstelle einen spezifischen Cache-Ordner im Symfony var/cache Verzeichnis
        $cachePath = $this->projectDir . '/var/cache/htmlpurifier';
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        $config->set('Cache.SerializerPath', $cachePath);
        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($content);
    }
    public function createPost(PostCreateDTO $dto, User $author): Post
    {
        // Validierung: Titelbild ist erforderlich
        if (!$dto->titleImage instanceof UploadedFile) {
            throw new \InvalidArgumentException("Titelbild ist erforderlich.");
        }

        $category = $this->resolveCategory($dto->categoryId);
        $slug = $this->generateUniqueSlug($dto->title);
        $post = new Post();
        $post->setSlug($slug);
        $post->setTitle($dto->title);
        $post->setAuthor($author);
        $post->setCreatedAt(new \DateTime());
        
        if ($category) {
            $post->setCategory($category);
        }

        $uploadedFileMap = [];

        // Verarbeiten des Titelbilds
        $titleImageFilename = $this->uploadFile($dto->titleImage);
        $post->setTitleImage($titleImageFilename);
        $uploadedFileMap[$dto->titleImage->getClientOriginalName()] = $titleImageFilename;

        // Verarbeiten aller anderen Bilder
        foreach ($dto->images as $uploadedImage) {
            if ($uploadedImage instanceof UploadedFile) {
                $newFilename = $this->uploadFile($uploadedImage);
                $uploadedFileMap[$uploadedImage->getClientOriginalName()] = $newFilename;
            }
        }
        
        $finalContent = $this->sanitizeContent($dto->content ?? '');

        // Dekodieren der imageMap
        $imageMap = json_decode($dto->imageMap ?? '{}', true) ?? [];
                
        // Platzhalter im Content ersetzen
        foreach ($imageMap as $placeholderId => $originalFilename) {
            if (isset($uploadedFileMap[$originalFilename])) {
                $newFilename = $uploadedFileMap[$originalFilename];
                $placeholder = "[{$placeholderId}]";
                $mediaHtml = sprintf(
                    '<img src="%s/api/uploads/%s" alt="%s">',
                    $this->apiUrl,
                    $newFilename,
                    htmlspecialchars($originalFilename)
                );
                $finalContent = str_replace($placeholder, $mediaHtml, $finalContent);
            }
        }
                
        $post->setContent($finalContent);
        
        // Speichern aller hochgeladenen Bildpfade
        if (!empty($uploadedFileMap)) {
            $post->setImages(array_values($uploadedFileMap));
        }
        
        $this->em->persist($post);
        $this->em->flush();

        return $post;
    }

    /**
     * Erstellt einen Blogpost auf Basis bereits gespeicherter Bilddateien.
     *
     * Wird vom MCP-Endpunkt genutzt: Der MCP-Client lädt Bilder zuerst
     * separat hoch (storeImageData) und referenziert sie anschließend über
     * ihre Dateinamen. Dadurch kann ein MCP-Client Beiträge samt Beitragsbild
     * in zwei Schritten erstellen, ohne Multipart-Uploads unterstützen zu müssen.
     *
     * @param string[] $additionalImageFilenames
     */
    public function createPostFromStoredImages(
        string $title,
        ?string $content,
        int $categoryId,
        string $titleImageFilename,
        array $additionalImageFilenames,
        User $author
    ): Post {
        if (trim($title) === '') {
            throw new \InvalidArgumentException('Titel ist erforderlich.');
        }

        $category = $this->resolveCategory($categoryId);

        // Titelbild validieren (muss bereits im Upload-Verzeichnis liegen)
        $titleImageFilename = $this->assertStoredImageExists($titleImageFilename);

        $slug = $this->generateUniqueSlug($title);

        $post = new Post();
        $post->setSlug($slug);
        $post->setTitle($title);
        $post->setAuthor($author);
        $post->setCreatedAt(new \DateTime());
        $post->setCategory($category);
        $post->setTitleImage($titleImageFilename);

        $images = [$titleImageFilename];
        foreach ($additionalImageFilenames as $filename) {
            $images[] = $this->assertStoredImageExists((string) $filename);
        }
        $post->setImages($images);

        $post->setContent($this->sanitizeContent($content ?? ''));

        $this->em->persist($post);
        $this->em->flush();

        return $post;
    }

    /**
     * Speichert Bilddaten (z.B. Base64-dekodiert aus einem MCP-Tool-Aufruf)
     * mit derselben Validierung wie reguläre Uploads:
     * MIME-Whitelist, 5MB-Limit, Inhaltsprüfung per finfo.
     *
     * @return string Der generierte, sichere Dateiname im Upload-Verzeichnis.
     */
    public function storeImageData(string $data, string $originalName): string
    {
        // ✅ Größenlimit (5MB)
        if (strlen($data) > 5242880) {
            throw new \RuntimeException('File too large');
        }

        if ($data === '') {
            throw new \RuntimeException('Empty file');
        }

        // ✅ Echten Content-Type prüfen (nicht nur die Angabe des Clients)
        $realMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($data);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($realMime, $allowedMimes, true)) {
            throw new \RuntimeException('Invalid file type');
        }

        $extension = match ($realMime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new \RuntimeException('Unsupported type'),
        };

        $safeFilename = $this->slugger->slug(pathinfo($originalName, PATHINFO_FILENAME));
        if (trim((string) $safeFilename) === '') {
            $safeFilename = $this->slugger->slug('bild');
        }
        $newFilename = $safeFilename . '-' . bin2hex(random_bytes(8)) . '.' . $extension;

        $targetPath = $this->uploadDirectory . '/' . $newFilename;
        if (file_put_contents($targetPath, $data) === false) {
            throw new \RuntimeException('Upload fehlgeschlagen.');
        }

        // ✅ Restriktive Permissions
        chmod($targetPath, 0644);

        return $newFilename;
    }

    /**
     * Prüft, dass ein Dateiname auf eine echte Datei im Upload-Verzeichnis
     * zeigt, und gibt den normalisierten Namen zurück.
     */
    private function assertStoredImageExists(string $filename): string
    {
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            throw new \InvalidArgumentException('Ungültiger Dateiname.');
        }

        $filePath = realpath($this->uploadDirectory . '/' . $filename);

        if ($filePath === false || strpos($filePath, realpath($this->uploadDirectory)) !== 0) {
            throw new \InvalidArgumentException('Bilddatei nicht gefunden: ' . $filename);
        }

        if (!is_file($filePath)) {
            throw new \InvalidArgumentException('Bilddatei nicht gefunden: ' . $filename);
        }

        return $filename;
    }

    private function resolveCategory(?int $categoryId): ?Category
    {
        $category = null;
        if ($categoryId !== null) {
            $category = $this->em->getRepository(Category::class)->find($categoryId);
            if (!$category) {
                throw new \InvalidArgumentException("Kategorie mit ID {$categoryId} nicht gefunden.");
            }
        }

        return $category;
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = strtolower($this->slugger->slug($title));
        $existing = $this->em->getRepository(Post::class)->findOneBy(['slug' => $slug]);
        if ($existing) {
            $slug .= '-' . uniqid();
        }

        return $slug;
    }

    public function updatePost(Post $post, PostUpdateDTO $dto): Post
    {
        // Aktualisiere Titel
        if ($dto->title !== null) {
            $post->setTitle($dto->title);
            $newSlug = strtolower($this->slugger->slug($dto->title));
            $post->setSlug($newSlug);
        }
        
        // Aktualisiere Content
        if ($dto->content !== null) {
            $post->setContent($this->sanitizeContent($dto->content));
        }
       
        // Aktualisiere Kategorie
        if ($dto->categoryId !== null) {
            $category = $this->em->getRepository(Category::class)->find($dto->categoryId);
            if (!$category) {
                throw new \InvalidArgumentException("Kategorie mit ID {$dto->categoryId} nicht gefunden.");
            }
            $post->setCategory($category);
        }

        if ($dto->titleImage instanceof UploadedFile) {
            if ($post->getTitleImage()) {
                $this->deleteFile($post->getTitleImage());
            }

            $post->setTitleImage($this->uploadFile($dto->titleImage));
        }

        if (!empty($dto->images)) {
            $existingImages = $post->getImages() ?? [];
            $uploadedImages = [];

            foreach ($dto->images as $uploadedImage) {
                if ($uploadedImage instanceof UploadedFile) {
                    $uploadedImages[] = $this->uploadFile($uploadedImage);
                }
            }

            $post->setImages(array_values(array_merge($existingImages, $uploadedImages)));
        }

        $this->em->flush();

        return $post;
    }

    /**
     * Löscht einen Post und alle zugehörigen Dateien
     */
    public function deletePost(Post $post): void
    {
        // Lösche Titelbild
        if ($post->getTitleImage()) {
            $this->deleteFile($post->getTitleImage());
        }

        // Lösche alle anderen Bilder
        if ($post->getImages()) {
            foreach ($post->getImages() as $image) {
                $this->deleteFile($image);
            }
        }

        $this->em->remove($post);
        $this->em->flush();
    }

    /**
     * Lädt eine Datei hoch und gibt den neuen Dateinamen zurück
     */
    private function uploadFile(UploadedFile $file): string
    {
        // ✅ MIME-Type Whitelist
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \RuntimeException('Invalid file type');
        }
        
        // ✅ Größenlimit (5MB)
        if ($file->getSize() > 5242880) {
            throw new \RuntimeException('File too large');
        }
        
        // ✅ Validiere echten Content-Type (nicht nur Extension)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);
        
        if ($realMime !== $file->getMimeType()) {
            throw new \RuntimeException('File content mismatch');
        }
        
        // ✅ Sichere Extension-Mapping
        $extension = match($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new \RuntimeException('Unsupported type')
        };
        
        $safeFilename = $this->slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $newFilename = $safeFilename . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        
        $file->move($this->uploadDirectory, $newFilename);
        
        // ✅ Setze restriktive Permissions
        chmod($this->uploadDirectory . '/' . $newFilename, 0644);
        
        return $newFilename;
    }

    /**
     * Löscht eine Datei aus dem Upload-Verzeichnis
     */
    private function deleteFile(string $filename): void
    {
        // ✅ SICHER: Filename validieren
        if (preg_match('/[^a-zA-Z0-9._-]/', $filename)) {
            throw new \InvalidArgumentException('Invalid filename');
        }
        
        $filePath = realpath($this->uploadDirectory . '/' . $filename);
        
        // Stelle sicher, dass Pfad im Upload-Dir liegt
        if ($filePath === false || 
            strpos($filePath, realpath($this->uploadDirectory)) !== 0) {
            throw new \RuntimeException('Invalid file path');
        }
        
        @unlink($filePath);
    }
}