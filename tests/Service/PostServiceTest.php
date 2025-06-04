<?php
namespace App\Tests\Service;

use App\DTO\PostCreateDTO;
use App\DTO\PostUpdateDTO; 
use App\Entity\Post;
use App\Service\PostService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostServiceTest extends TestCase
{
    private $entityManager;
    private PostService $postService;
    private string $projectDir = '/tmp'; // z.B. temp Ordner

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // PostService mit gemocktem EntityManager und Pfad
        $this->postService = $this->getMockBuilder(PostService::class)
            ->setConstructorArgs([$this->entityManager, $this->projectDir])
            ->onlyMethods(['saveFile']) // saveFile mocken, damit keine echten Dateien gespeichert werden
            ->getMock();
    }

    public function testCreatePost(): void
    {
        $user = $this->createMock(\App\Entity\User::class);

        $titleImage = $this->createMock(UploadedFile::class);
        $image1 = $this->createMock(UploadedFile::class);
        $image2 = $this->createMock(UploadedFile::class);

        $dto = new PostCreateDTO(
            title: 'Test Titel',
            content: 'Test Inhalt',
            titleImage: $titleImage,
            images: [$image1, $image2]
        );

        // Erwartung: saveFile wird 3x aufgerufen (1x titleImage, 2x images)
        $this->postService->expects($this->exactly(3))
            ->method('saveFile')
            ->willReturnOnConsecutiveCalls(
                '/uploads/titleImage-123.jpg',
                '/uploads/image-234.jpg',
                '/uploads/image-345.jpg'
            );

        // entityManager persist und flush sollen aufgerufen werden
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function(Post $post) use ($dto, $user) {
                return
                    $post->getTitle() === $dto->title &&
                    $post->getContent() === $dto->content &&
                    $post->getTitleImage() === ['/uploads/titleImage-123.jpg'] &&
                    $post->getImages() === ['/uploads/image-234.jpg', '/uploads/image-345.jpg'] &&
                    $post->getAuthor() === $user;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $post = $this->postService->createPost($dto, $user);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals($dto->title, $post->getTitle());
        $this->assertEquals($dto->content, $post->getContent());
    }
    public function testUpdatePost(): void
    {
        $post = new Post();
        $post->setTitle('Alter Titel');
        $post->setContent('Alter Inhalt');

        $titleImage = $this->createMock(UploadedFile::class);
        $image1 = $this->createMock(UploadedFile::class);

        $dto = new PostUpdateDTO(
            id: 1,
            title: 'Neuer Titel',
            content: 'Neuer Inhalt',
            titleImage: $titleImage,
            images: [$image1]
        );

        // saveFile soll 2x aufgerufen werden (Titelbild + ein weiteres Bild)
        $this->postService->expects($this->exactly(2))
            ->method('saveFile')
            ->willReturnOnConsecutiveCalls(
                '/uploads/titleImage-123.jpg',
                '/uploads/image-234.jpg'
            );

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($post);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updatedPost = $this->postService->updatePost($post, $dto);

        $this->assertSame($post, $updatedPost);
        $this->assertEquals('Neuer Titel', $updatedPost->getTitle());
        $this->assertEquals('Neuer Inhalt', $updatedPost->getContent());
        $this->assertEquals(['/uploads/titleImage-123.jpg'], $updatedPost->getTitleImage());
        $this->assertEquals(['/uploads/image-234.jpg'], $updatedPost->getImages());
    }


}
