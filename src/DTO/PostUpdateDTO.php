<?php
// src/DTO/PostUpdateDTO.php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostUpdateDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $content,
        public readonly ?UploadedFile $titleImage = null,
        /** @var UploadedFile[]|null */
        public readonly ?array $images = null
    ) {}
}
