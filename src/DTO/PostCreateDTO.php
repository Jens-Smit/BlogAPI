<?php
namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostCreateDTO
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $content,
        public readonly ?UploadedFile $titleImage,
        /** @var UploadedFile[] */
        public readonly array $images = []
    ) {}
}
