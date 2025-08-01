<?php

namespace App\Tests\Unit\Service; // Wichtig: Der Namespace sollte App\Tests\Unit\Service sein, wenn der Test unter tests/Unit/Service liegt.

use App\DTO\PostCreateDTO;
use App\DTO\PostUpdateDTO;
use App\Entity\Post;
use App\Entity\User; // Stellen Sie sicher, dass User importiert ist
use App\Service\PostService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface; // <-- Neu: SluggerInterface importieren

class PostServiceTest extends TestCase
{
    private EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManagerMock;
    private SluggerInterface|\PHPUnit\Framework\MockObject\MockObject $sluggerMock; // <-- Neu: Mock für Slugger
    private PostService $postService;
    private string $uploadTestDir; // <-- Neu: Temporäres Upload-Verzeichnis für Tests

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->sluggerMock = $this->createMock(SluggerInterface::class); // <-- Neu: Slugger Mock erstellen

        // Ein temporäres Verzeichnis für Test-Uploads erstellen
        $this->uploadTestDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_uploads_' . uniqid();
        if (!is_dir($this->uploadTestDir)) {
            mkdir($this->uploadTestDir, 0777, true);
        }

        // PostService mit allen benötigten Mock-Objekten und Pfaden initialisieren
        // WICHTIG: Hier keine Mock-Methoden auf PostService selbst setzen,
        // da wir die echte uploadFile-Methode testen wollen oder nur die Abhängigkeiten mocken.
        // Wenn du uploadFile mocken möchtest (wie im Originalversuch mit saveFile),
        // müsstest du PostService als MockBuilder erstellen und dort onlyMethods verwenden.
        // Für diesen Testfall gehen wir davon aus, dass wir PostService als echtes Objekt testen.
        $this->postService = new PostService(
            $this->entityManagerMock,
            sys_get_temp_dir(), // projectDir
            $this->uploadTestDir, // uploadDirectory
            $this->sluggerMock // Slugger
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Aufräumen des Test-Upload-Verzeichnisses nach jedem Test
        if (is_dir($this->uploadTestDir)) {
            foreach (glob($this->uploadTestDir . '/*') as $file) {
                @unlink($file); // @ unterdrückt Fehler, falls Datei nicht existiert oder Rechte fehlen
            }
            @rmdir($this->uploadTestDir);
        }
    }

    /**
     * Hilfsfunktion zum Erstellen eines Mock-UploadedFile.
     * Muss die move()-Methode mocken, damit sie nicht wirklich verschiebt.
     */
    private function createMockUploadedFile(string $originalFilename, string $mimeType = 'image/jpeg'): UploadedFile|\PHPUnit\Framework\MockObject\MockObject
    {
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->method('getClientOriginalName')->willReturn($originalFilename);
        $mockFile->method('guessExtension')->willReturn(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $mockFile->method('getMimeType')->willReturn($mimeType);
        
        // Wichtig: Mocken der move-Methode, damit sie nichts tut oder einen Erfolg simuliert.
        // Die reale move-Methode wird im PostService.php aufgerufen,
        // der Test muss jedoch verhindern, dass echte Dateien im Testablauf verschoben werden,
        // es sei denn, es ist ein funktionaler Test.
        // Für Unit Tests des Services wollen wir das Dateispeichern isoliert testen oder mocken.
        // Da der Service selbst die private uploadFile-Methode aufruft,
        // können wir entweder diese private Methode nicht testen (und nur die öffentlichen)
        // oder wir müssen den Service selbst teilweise mocken.
        // Die ursprüngliche Idee war, 'saveFile' zu mocken. Da es 'uploadFile' ist,
        // können wir den PostService selbst zum Teil-Mock machen.
        $mockFile->method('move')->willReturn(true); // Simuliert, dass die Verschiebung erfolgreich war

        return $mockFile;
    }

    public function testCreatePost(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $mockTitleImage = $this->createMockUploadedFile('test_title.jpg');
        $mockImage1 = $this->createMockUploadedFile('test_image1.png');

        $dto = new PostCreateDTO(
            title: 'Test Titel',
            content: 'Test Inhalt',
            titleImage: $mockTitleImage,
            images: [$mockImage1]
        );

        // Erwartungen an den Slugger-Mock: Er soll den übergebenen String einfach zurückgeben.
        $this->sluggerMock->expects($this->atLeastOnce())
            ->method('slug')
            ->willReturnCallback(fn(string $s) => $s); // Gibt den String unverändert zurück oder slugged ihn einfach

        // Erwartungen an den EntityManager
        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Post::class));
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $post = $this->postService->createPost($dto, $user);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('Test Titel', $post->getTitle());
        $this->assertEquals('Test Inhalt', $post->getContent());
        $this->assertEquals($user, $post->getAuthor());
        $this->assertNotNull($post->getCreatedAt());

        // Überprüfen, ob die Dateinamen korrekt gesetzt wurden (Strings, keine UploadedFile-Objekte)
        // Die uploadFile-Methode des Service wird die Dateinamen generieren
        $this->assertIsString($post->getTitleImage());
        $this->assertStringContainsString('test_title-', $post->getTitleImage()); // Prüfen auf Slug und Uniqid
        $this->assertStringEndsWith('.jpg', $post->getTitleImage());

        $this->assertIsArray($post->getImages());
        $this->assertCount(1, $post->getImages());
        $this->assertStringContainsString('test_image1-', $post->getImages()[0]);
        $this->assertStringEndsWith('.png', $post->getImages()[0]);
    }

    public function testUpdatePost(): void
    {
        $user = $this->createMock(User::class);
        $post = new Post();
        $post->setTitle('Alter Titel');
        $post->setContent('Alter Inhalt');
        $post->setAuthor($user);
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setTitleImage('old_title_image.jpg'); // Simuliere ein bestehendes Bild
        $post->setImages(['old_image1.png', 'old_image2.jpg']); // Simuliere bestehende Bilder

        $mockNewTitleImage = $this->createMockUploadedFile('new_title.gif');
        $mockNewImage1 = $this->createMockUploadedFile('new_image.jpeg');

        $dto = new PostUpdateDTO(
            id: 1,
            title: 'Neuer Titel',
            content: 'Neuer Inhalt',
            titleImage: $mockNewTitleImage,
            images: [$mockNewImage1] // Ersetzt alte Bilder
        );

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // Erwartungen an den Slugger-Mock
        $this->sluggerMock->expects($this->atLeastOnce())
            ->method('slug')
            ->willReturnCallback(fn(string $s) => $s);

        $updatedPost = $this->postService->updatePost($post, $dto);

        $this->assertInstanceOf(Post::class, $updatedPost);
        $this->assertEquals('Neuer Titel', $updatedPost->getTitle());
        $this->assertEquals('Neuer Inhalt', $updatedPost->getContent());

        // Überprüfen des aktualisierten Titelbildes
        $this->assertIsString($updatedPost->getTitleImage());
        $this->assertStringContainsString('new_title-', $updatedPost->getTitleImage());
        $this->assertStringEndsWith('.gif', $updatedPost->getTitleImage());

        // Überprüfen der aktualisierten Bilder (hier: alte wurden entfernt, neue hinzugefügt)
        $this->assertIsArray($updatedPost->getImages());
        $this->assertCount(1, $updatedPost->getImages());
        $this->assertStringContainsString('new_image-', $updatedPost->getImages()[0]);
        $this->assertStringEndsWith('.jpeg', $updatedPost->getImages()[0]);
    }

    // Testfall: Titelbild entfernen
    public function testUpdatePostRemovesTitleImage(): void
    {
        $user = $this->createMock(User::class);
        $post = new Post();
        $post->setTitle('Test');
        $post->setContent('Content');
        $post->setAuthor($user);
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setTitleImage('existing_title.jpg'); // Bestehendes Bild

        $dto = new PostUpdateDTO(
            id: 1,
            title: null,
            content: null,
            titleImage: null, // Explizit null setzen
            images: null
        );

        $this->entityManagerMock->expects($this->once())->method('flush');

        $updatedPost = $this->postService->updatePost($post, $dto);

        $this->assertNull($updatedPost->getTitleImage());
        // Optional: Überprüfen, ob die Datei auch physich gelöscht wurde
        // Dies wäre ein Integrationstest, da es Dateisystemzugriff beinhaltet.
        // Für diesen Unit Test reicht die Überprüfung des Entitätsstatus.
    }

    // Testfall: Alle zusätzlichen Bilder entfernen
    public function testUpdatePostRemovesAllImages(): void
    {
        $user = $this->createMock(User::class);
        $post = new Post();
        $post->setTitle('Test');
        $post->setContent('Content');
        $post->setAuthor($user);
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setImages(['img1.jpg', 'img2.png']); // Bestehende Bilder

        $dto = new PostUpdateDTO(
            id: 1,
            title: null,
            content: null,
            titleImage: null,
            images: [] // Leeres Array, um alle Bilder zu entfernen
        );

        $this->entityManagerMock->expects($this->once())->method('flush');

        $updatedPost = $this->postService->updatePost($post, $dto);

        $this->assertIsArray($updatedPost->getImages());
        $this->assertEmpty($updatedPost->getImages());
        // Optional: Überprüfen, ob die Dateien auch physich gelöscht wurden.
    }
}