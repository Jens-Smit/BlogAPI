<?php
// src/Service/PostService.php

namespace App\Service;

use App\DTO\PostCreateDTO;
use App\DTO\PostUpdateDTO;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PostService
{
    private EntityManagerInterface $em;
    private string $projectDir;
    private string $uploadDirectory;
    private SluggerInterface $slugger;

    // Korrektur: EntityManagerInterface anstelle von EntityManagerEntityManagerInterface
    public function __construct(EntityManagerInterface $em, string $projectDir, string $uploadDirectory, SluggerInterface $slugger)
    {
        $this->em = $em;
        $this->projectDir = $projectDir;
        $this->uploadDirectory = $uploadDirectory;
        $this->slugger = $slugger;
    }
    public function createPost(PostCreateDTO $dto, UserInterface $user): Post
    {
        $post = new Post();
        $post->setTitle($dto->title);
        $post->setContent($dto->content);
        $post->setAuthor($user);
        $post->setCreatedAt(new \DateTimeImmutable());

        // Behandlung des titleImage-Uploads
        if ($dto->titleImage instanceof UploadedFile) {
            $newFilename = $this->uploadFile($dto->titleImage);
            $post->setTitleImage($newFilename); // Speichern Sie den Dateinamen/Pfad in der Entität
        } else {
            $post->setTitleImage(null); // Sicherstellen, dass es null ist, wenn kein Bild vorhanden
        }

        // Behandlung mehrerer Bild-Uploads
        $imagePaths = [];
        if (is_array($dto->images)) {
            foreach ($dto->images as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFile) {
                    $newFilename = $this->uploadFile($uploadedFile);
                    $imagePaths[] = $newFilename;
                }
            }
        }
        $post->setImages($imagePaths); // Setze das Array der Bildpfade

        $this->em->persist($post);
        $this->em->flush();

        return $post;
    }
    public function updatePost(Post $post, PostUpdateDTO $dto): Post
    {
        // Aktualisieren Sie nur die Felder, die im DTO bereitgestellt werden
        if ($dto->title !== null) {
            $post->setTitle($dto->title);
        }
        if ($dto->content !== null) {
            $post->setContent($dto->content);
        }

        // Behandlung des titleImage-Uploads
        if ($dto->titleImage instanceof UploadedFile) {
            // Optional: Alte Datei löschen, bevor eine neue hochgeladen wird (implementieren Sie dies in einer separaten Methode, z.B. deleteFile)
            // if ($post->getTitleImage()) {
            //     $this->deleteFile($post->getTitleImage());
            // }
            $newFilename = $this->uploadFile($dto->titleImage);
            $post->setTitleImage($newFilename);
        } elseif ($dto->titleImage === null) {
            // Wenn im DTO explizit auf null gesetzt, altes Bild löschen (optional) und Feld leeren
            // if ($post->getTitleImage()) {
            //     $this->deleteFile($post->getTitleImage());
            // }
            $post->setTitleImage(null);
        }
        // Wenn $dto->titleImage weder UploadedFile noch null ist, bleibt der bestehende Wert erhalten.

        // Behandlung mehrerer Bild-Uploads
        if (is_array($dto->images)) {
            $uploadedNewImagePaths = [];
            foreach ($dto->images as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFile) {
                    $uploadedNewImagePaths[] = $this->uploadFile($uploadedFile);
                }
            }

            if (!empty($uploadedNewImagePaths)) {
                // Füge die neuen Bilder zu den bestehenden hinzu (oder ersetze sie, je nach gewünschtem Verhalten)
                $currentImagePaths = $post->getImages() ?? [];
                // Hier entscheiden Sie: array_merge fügt hinzu, $uploadedNewImagePaths überschreibt
                $post->setImages(array_merge($currentImagePaths, $uploadedNewImagePaths));
            } elseif (count($dto->images) === 0) {
                // Wenn ein explizit leeres Array gesendet wurde (d.h. der Benutzer möchte alle Bilder entfernen)
                // Optional: Hier könnten Sie die alten Bilder von der Festplatte löschen
                // foreach ($post->getImages() ?? [] as $oldImage) {
                //     $this->deleteFile($oldImage);
                // }
                $post->setImages([]);
            }
            // Wenn $dto->images nicht leer ist, aber keine UploadedFile-Objekte enthält (z.B. ['existing-image.jpg']),
            // bedeutet das, dass keine neuen Dateien hochgeladen werden, aber die bestehenden beibehalten werden sollen.
            // Die Logik hier lässt die bestehenden unangetastet, es sei denn, es wird ein leeres Array gesendet.
        }

        $this->em->flush();

        return $post;
    }
    private function uploadFile(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            // Korrektur: Vollständigen Pfad verwenden
            $file->move(
                $this->uploadDirectory,
                $newFilename
            );
        } catch (FileException $e) {
            // Fehler im Log festhalten
            error_log('Fehler beim Hochladen der Datei: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new \RuntimeException('Fehler beim Hochladen der Datei: ' . $e->getMessage());
        }

        return $newFilename;
    }
    private function deleteFile(string $filename): void
    {
         $filePath = $this->projectDir . '/' . $this->uploadDirectory . '/' . $filename;
         if (file_exists($filePath)) {
             unlink($filePath);
         }
    }
}