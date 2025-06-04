<?php
// src/Service/PostService.php

namespace App\Service;

use App\DTO\PostCreateDTO;
use App\DTO\PostUpdateDTO;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostService
{
    private EntityManagerInterface $em;
    private string $projectDir;

    public function __construct(EntityManagerInterface $em, string $projectDir)
    {
        $this->em = $em;
        $this->projectDir = $projectDir;
    }

    public function createPost(PostCreateDTO $dto, UserInterface $user): Post
    {
        $titleImagePath = null;
        $imagePaths = [];

        if ($dto->titleImage instanceof UploadedFile) {
            $titleImagePath = $this->saveFile($dto->titleImage, 'titleImage');
        }

        if (is_array($dto->images)) {
            foreach ($dto->images as $image) {
                if ($image instanceof UploadedFile) {
                    $imagePaths[] = $this->saveFile($image, 'image');
                }
            }
        }

        $post = new Post();
        $post->setTitle($dto->title);
        $post->setContent($dto->content);
        $post->setTitleImage($titleImagePath ? [$titleImagePath] : null);
        $post->setImages($imagePaths);
        $post->setAuthor($user);
        $post->setCreatedAt(new \DateTime());

        $this->em->persist($post);
        $this->em->flush();

        return $post;
    }

    public function updatePost(Post $post, PostUpdateDTO $dto): Post
    {
        $post->setTitle($dto->title);
        $post->setContent($dto->content);

        if ($dto->titleImage) {
            $savedFilePath = $this->saveFile($dto->titleImage);
            $post->setTitleImage([$savedFilePath]);
        }

        if ($dto->images) {
            $savedImages = [];
            foreach ($dto->images as $image) {
                $savedImages[] = $this->saveFile($image);
            }
            $post->setImages($savedImages);
        }

        $this->em->persist($post);
        $this->em->flush();

        return $post;
    }

    protected function saveFile(UploadedFile $file): string
    {
        $uploadDir = $this->projectDir . '/public/uploads';
        $filename = uniqid('upload_') . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        return '/uploads/' . $filename;
    }
}
