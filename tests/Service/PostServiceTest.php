<?php

namespace App\Tests\Unit\Service;

use App\DTO\PostCreateDTO;
use App\DTO\PostUpdateDTO;
use App\Entity\Post;
use App\Entity\User;
use App\Service\PostService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\TextUI\XmlConfiguration\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;
    private PostService $postService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->slugger = $this->createMock(SluggerInterface::class);

        $this->slugger->method('slug')
            ->willReturnCallback(fn($string) => new UnicodeString($string));

        // Erstelle einen temporären Ordner
        $tempUploadDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploads_test';
        if (!is_dir($tempUploadDir)) {
            mkdir($tempUploadDir, 0777, true);
        }

        $this->postService = new PostService(
            $this->entityManager,
            $tempUploadDir,   // basePath
            $tempUploadDir,   // uploadPath
            $this->slugger
        );
    }

   private function createMockUploadedFile(string $name): UploadedFile
    {
        $mock = $this->createMock(UploadedFile::class);

        $mock->method('getClientOriginalName')->willReturn($name);
        $mock->method('guessExtension')->willReturn(pathinfo($name, PATHINFO_EXTENSION));
        $mock->method('getMimeType')->willReturn('image/jpeg');
        
        // Erstelle ein tatsächliches temporäres File, das zurückgegeben wird
        $mock->method('move')->willReturnCallback(function ($directory, $name) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            file_put_contents($path, 'dummy'); // Dummy-Datei
            return new \Symfony\Component\HttpFoundation\File\File($path);
        });

        return $mock;
    }

    public function testCreatePost(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $dto = new PostCreateDTO(
            'Titel',
            'Content',
            $this->createMockUploadedFile('title.jpg'),
            [$this->createMockUploadedFile('image1.jpg')]
        );

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $post = $this->postService->createPost($dto, $user);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('Titel', $post->getTitle());
        $this->assertEquals('Content', $post->getContent());
    }

    public function testUpdatePost(): void
    {
        $post = new Post();
        $post->setTitle('Old');
        $post->setContent('Old');
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setAuthor($this->createMock(User::class));
        $post->setTitleImage('old.jpg');
        $post->setImages(['old1.jpg', 'old2.jpg']);

        $dto = new PostUpdateDTO(
            1,
            'New',
            'Updated',
            $this->createMockUploadedFile('new.jpg'),
            [$this->createMockUploadedFile('new1.jpg')]
        );

        $this->entityManager->expects($this->once())->method('flush');

        $updated = $this->postService->updatePost($post, $dto);

        $this->assertEquals('New', $updated->getTitle());
        $this->assertEquals('Updated', $updated->getContent());
    }

    public function testUpdatePostRemovesTitleImage(): void
    {
        $post = new Post();
        $post->setTitle('T');
        $post->setContent('C');
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setAuthor($this->createMock(User::class));
        $post->setTitleImage('title.jpg');

        $dto = new PostUpdateDTO(1, 'T', 'C', null, null);

        $this->entityManager->expects($this->once())->method('flush');

        $updated = $this->postService->updatePost($post, $dto);

        $this->assertNull($updated->getTitleImage());
    }

    public function testUpdatePostRemovesAllImages(): void
    {
        $post = new Post();
        $post->setTitle('T');
        $post->setContent('C');
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setAuthor($this->createMock(User::class));
        $post->setImages(['img1.jpg', 'img2.jpg']);

        $dto = new PostUpdateDTO(1, 'T', 'C', null, []);

        $this->entityManager->expects($this->once())->method('flush');

        $updated = $this->postService->updatePost($post, $dto);

        $this->assertSame([], $updated->getImages());
    }
}
