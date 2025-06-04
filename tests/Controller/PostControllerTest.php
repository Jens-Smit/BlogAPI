<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\User;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;

class PostControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $user;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        // DB vor jedem Test bereinigen
        $this->em->createQuery('DELETE FROM App\Entity\Post')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User')->execute();

        // Test‐User anlegen und speichern
        $user = new User();
        $user->setEmail('testuser@example.com');
        $user->setPassword('testpassword'); // Falls Hashing nötig, hier anpassen!
        $this->em->persist($user);
        $this->em->flush();

        $this->user = $user;
    }

    /**
     * Hilfsfunktion: Erzeugt und speichert eine kleine JPEG‐Datei im System‐Temp‐Ordner.
     * Gibt den vollständigen Pfad zurück.
     */
    private function createTempImage(string $filename = 'temp.jpg'): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $image = imagecreatetruecolor(10, 10);
        $bg = imagecolorallocate($image, 255, 0, 0); // rotes Quadrat
        imagefill($image, 0, 0, $bg);
        imagejpeg($image, $path);
        imagedestroy($image);

        return $path;
    }

    public function testCreatePost(): void
    {
        // 1) Test‐User einloggen
        $this->client->loginUser($this->user);

        // 2) Zwei temporäre Bilder erzeugen
        $titleImagePath = $this->createTempImage('title_image.jpg');
        $image1Path     = $this->createTempImage('image1.jpg');

        $titleImage = new UploadedFile(
            $titleImagePath,
            'title_image.jpg',
            'image/jpeg',
            null,
            true
        );
        $image1 = new UploadedFile(
            $image1Path,
            'image1.jpg',
            'image/jpeg',
            null,
            true
        );

        // 3) POST an /posts schicken
        $this->client->request(
            'POST',
            '/posts',
            [
                'title'   => 'Mein erster Test-Post',
                'content' => 'Das ist ein Test-Content',
            ],
            [
                'titleImage' => $titleImage,
                'images'     => [$image1],
            ]
        );

        // 4) Response prüfen
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertIsInt($data['id']);

        // 5) In der DB nachsehen, ob der Post existiert
        $post = $this->em->getRepository(Post::class)->find($data['id']);
        $this->assertNotNull($post);
        $this->assertEquals('Mein erster Test-Post', $post->getTitle());

        // 6) Temporäre Dateien löschen
        @unlink($titleImagePath);
        @unlink($image1Path);
    }

    public function testIndexReturnsPosts(): void
    {
        // 1) Einen Post in die DB schreiben
        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Content');
        $post->setAuthor($this->user);
        $post->setCreatedAt(new \DateTime());
        $this->em->persist($post);
        $this->em->flush();

        // 2) GET /posts aufrufen
        $this->client->request('GET', '/posts');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // 3) Rückgabe prüfen
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Test Post', $data[0]['title']);
    }

    public function testDeletePost(): void
    {
        // 1) Post in der DB anlegen
        $post = new Post();
        $post->setTitle('Post to delete');
        $post->setContent('Content');
        $post->setAuthor($this->user);
        $post->setCreatedAt(new \DateTime());
        $this->em->persist($post);
        $this->em->flush();

        $postId = $post->getId();
        $this->assertNotNull($postId);

        // 2) Test‐User einloggen
        $this->client->loginUser($this->user);

        // 3) DELETE an /posts/{id} senden
        $this->client->request('DELETE', '/posts/' . $postId);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Post gelöscht', $content['message']);

        // 4) Sicherstellen, dass der Post nun wirklich gelöscht ist
        $deletedPost = $this->em->getRepository(Post::class)->find($postId);
        $this->assertNull($deletedPost);
    }

    public function testUploadReturnsUrl(): void
    {
        // 1) Temporäre Datei erzeugen
        $filePath = $this->createTempImage('upload_test.jpg');
        $file = new UploadedFile(
            $filePath,
            'upload_test.jpg',
            'image/jpeg',
            null,
            true
        );

        // 2) POST an /posts/upload senden
        $this->client->request('POST', '/posts/upload', [], ['file' => $file]);

        // 3) Antwort prüfen
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('url', $data);
        $this->assertStringContainsString('/uploads/', $data['url']);

        // 4) Temporäre Datei löschen
        @unlink($filePath);
    }
    public function testUpdatePost(): void
    {
        // Post anlegen
        $post = new Post();
        $post->setTitle('Alter Titel');
        $post->setContent('Alter Inhalt');
        $post->setAuthor($this->user);
        $post->setCreatedAt(new \DateTime());
        $this->em->persist($post);
        $this->em->flush();

        $postId = $post->getId();

        $this->client->loginUser($this->user);

        $titleImagePath = $this->createTempImage('update_title.jpg');
        $titleImage = new UploadedFile($titleImagePath, 'update_title.jpg', 'image/jpeg', null, true);

        $this->client->request(
            'PUT',
            '/posts/' . $postId,
            [
                'title' => 'Neuer Titel',
                'content' => 'Neuer Inhalt',
            ],
            [
                'titleImage' => $titleImage,
            ]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Post erfolgreich aktualisiert', $data['message']);

        // Temporäre Datei löschen
        @unlink($titleImagePath);
    }


}
