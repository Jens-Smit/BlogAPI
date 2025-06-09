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
        $post->setCreatedAt(new \DateTimeImmutable()); // Besser DateTimeImmutable für Entitäten

        // Setze die URLs direkt in die Entity
        // Stelle sicher, dass deine Post Entity Methoden hat, die Strings/Arrays akzeptieren.
        $post->setTitleImage($dto->titleImage ? [$dto->titleImage] : null); // Annahme: TitleImage ist ein Array, auch wenn es nur ein Element hat.
        $post->setImages($dto->images);

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
            $newFilename = $this->uploadFile($dto->titleImage);
            $post->setTitleImage($newFilename); // Speichern Sie den Dateinamen/Pfad in der Entität
        } elseif ($dto->titleImage === null && $post->getTitleImage() !== null) {
            // Wenn titleImage im DTO explizit auf null gesetzt ist und zuvor gesetzt war, entfernen Sie es.
            // Sie könnten hier die alte Datei von der Festplatte löschen.
            $post->setTitleImage(null);
        }
        // Wenn $dto->titleImage keine UploadedFile und nicht null ist, bedeutet dies, dass keine neue Datei bereitgestellt wurde
        // und die bestehende beibehalten werden soll.

        // Behandlung mehrerer Bild-Uploads
        if (is_array($dto->images) && !empty($dto->images)) {
            $imagePaths = $post->getImages() ?? []; // Vorhandene Bilder abrufen
            foreach ($dto->images as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFile) {
                    $newFilename = $this->uploadFile($uploadedFile);
                    $imagePaths[] = $newFilename; // Neuen Bildpfad hinzufügen
                }
            }
            $post->setImages($imagePaths);
        } elseif (is_array($dto->images) && empty($dto->images)) {
            // Wenn ein leeres Array für Bilder explizit gesendet wird, löschen Sie diese.
            // Sie könnten hier alte Dateien von der Festplatte löschen.
            $post->setImages([]);
        }

        $this->em->flush();

        return $post;
    }
    private function uploadFile(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // Dies ist notwendig, um den Dateinamen sicher als Teil der URL einzuschließen
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                $this->uploadDirectory,
                $newFilename
            );
        } catch (\FileException $e) {
            throw new \RuntimeException('Fehler beim Hochladen der Datei: ' . $e->getMessage());
        }

        return $newFilename;
    }
    
}
