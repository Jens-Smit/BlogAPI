<?php
// src/DTO/PostUpdateDTO.php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class PostUpdateDTO
{
    public function __construct(
        public int $id,
        #[Assert\NotBlank]
        public string $title,
        public ?string $content,
        public ?int $categoryId = null,
        public readonly ?UploadedFile $titleImage = null,
        /** @var UploadedFile[] */
        public readonly array $images = []
    ) {}
}
