<?php
// src/DTO/CategoryCreateDTO.php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryCreateDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public readonly string $name,
        public readonly ?int $parentId = null
    ) {}
}