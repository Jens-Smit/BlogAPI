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

    public function createPost(PostCreateDTO $dto, User $author): Post
    {
        // Validierung: Titelbild ist erforderlich
        if (!$dto->titleImage instanceof UploadedFile) {
            throw new \InvalidArgumentException("Titelbild ist erforderlich.");
        }

        // Validierung: Kategorie muss existieren, wenn angegeben
        $category = null;
        if ($dto->categoryId !== null) {
            $category = $this->em->getRepository(Category::class)->find($dto->categoryId);
            if (!$category) {
                throw new \InvalidArgumentException("Kategorie mit ID {$dto->categoryId} nicht gefunden.");
            }
        }

        $post = new Post();
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
        
        $finalContent = $dto->content ?? '';

        // Dekodieren der imageMap
        $imageMap = json_decode($dto->imageMap ?? '{}', true) ?? [];
                
        // Platzhalter im Content ersetzen
        foreach ($imageMap as $placeholderId => $originalFilename) {
            if (isset($uploadedFileMap[$originalFilename])) {
                $newFilename = $uploadedFileMap[$originalFilename];
                $placeholder = "[{$placeholderId}]";
                $mediaHtml = sprintf(
                    '<img src="%s/api/public/uploads/%s" alt="%s">',
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

    public function updatePost(Post $post, PostUpdateDTO $dto): Post
    {
        // Aktualisiere Titel
        if ($dto->title !== null) {
            $post->setTitle($dto->title);
        }
        
        // Aktualisiere Content
        if ($dto->content !== null) {
            $post->setContent($dto->content);
        }
       
        // Aktualisiere Kategorie
        if ($dto->categoryId !== null) {
            $category = $this->em->getRepository(Category::class)->find($dto->categoryId);
            if (!$category) {
                throw new \InvalidArgumentException("Kategorie mit ID {$dto->categoryId} nicht gefunden.");
            }
            $post->setCategory($category);
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
    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFilename = $this->slugger->slug($originalFilename);

    // Endung bestimmen
    $extension = $file->guessExtension();
    if (!$extension || $extension === 'txt') {
        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
    }

    $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

    try {
        $file->move($this->uploadDirectory, $newFilename);
    } catch (FileException $e) {
        throw new \RuntimeException('Fehler beim Hochladen der Datei: ' . $e->getMessage());
    }

    return $newFilename;
}

    /**
     * Löscht eine Datei aus dem Upload-Verzeichnis
     */
    private function deleteFile(string $filename): void
    {
        $filePath = $this->uploadDirectory . '/' . $filename;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}