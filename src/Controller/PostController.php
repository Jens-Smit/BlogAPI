<?php
// src/Controller/PostController.php
namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

class PostController extends AbstractController
{
    
    #[Route('/posts', name: 'get_posts', methods:['GET'])]
    public function index(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $posts = $em->getRepository(Post::class)->findAll();

        // Serialisiere $posts mit Symfony Serializer
        $data = $serializer->serialize($posts, 'json', ['groups' => 'post']); // Assuming you have a 'post' serialization group

        return new JsonResponse($data, 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
        ], true); // third argument is headers, fourth argument is already json
    }

   
    #[Route('/posts', name: 'create_post', methods:['POST'])]
    public function create(Request $request, EntityManagerInterface $em, Security $security): JsonResponse
    {
        $title = $request->request->get('title');
        $content = $request->request->get('content');
        $titleImage = $request->files->get('titleImage');
        $images = $request->files->get('images', []);

        if (empty($title) || !is_string($title)) {
            return new JsonResponse(['error' => 'Titel muss als String angegeben werden.'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        $titleImagePath = null;
        $imagePaths = [];

        // Speichere das Titelbild
        if ($titleImage) {
            $newFilename = 'titleImage-' . time() . '.' . $titleImage->guessExtension();
            $titleImage->move($uploadDir, $newFilename);
            $titleImagePath = '/uploads/' . $newFilename; // Korrekte URL für die Speicherung
        }

        // Speichere zusätzliche Bilder/Videos
        if (!empty($images)) {
            foreach ($images as $image) {
                if ($image instanceof UploadedFile) {
                    $newFilename = 'image-' . uniqid() . '.' . $image->guessExtension();
                    $image->move($uploadDir, $newFilename);
                    $imagePaths[] = '/uploads/' . $newFilename;
                }
            }
        }

        // Neuen Post erstellen
        $post = new Post();
        $post->setTitle($title);
        $post->setContent($content);
        $post->setTitleImage($titleImagePath ? [$titleImagePath] : null);
        $post->setImages($imagePaths); // Stelle sicher, dass dein Post-Entity `setImages()` unterstützt!
        $post->setAuthor($security->getUser());
        $post->setCreatedAt(new \DateTime());

        $em->persist($post);
        $em->flush();

        return new JsonResponse([
            'message' => 'Beitrag erfolgreich erstellt',
            'post' => $post->getId(),
            'titleImage' => $titleImagePath,
            'images' => $imagePaths,
        ], 201);
    }
    #[Route('/posts/upload', name: 'upload_media', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        // Datei aus dem Request auslesen (über "file")
        $file = $request->files->get('file');
        if (!$file || !$file instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Keine Datei hochgeladen oder ungültiges Dateiformat.'], 400);
        }
    
        // Zielverzeichnis definieren
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
    
        // Eindeutigen Dateinamen erstellen
        $newFilename = 'media-' . uniqid() . '.' . $file->guessExtension();
    
        try {
            // Datei in das Upload-Verzeichnis verschieben
            $file->move($uploadDir, $newFilename);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Fehler beim Hochladen: ' . $e->getMessage()], 500);
        }
    
        // Permanente URL zusammenbauen (relative URL)
        $url = 'http://127.0.0.1:8000/uploads/' . $newFilename;
    
        return new JsonResponse(['url' => $url], 201);
    }

    #[Route('/posts/{id}', name: 'delete_post', methods:['DELETE'])]
    
    public function delete($id, EntityManagerInterface $em, Security $security): JsonResponse
    {
        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            return new JsonResponse(['message' => 'Beitrag nicht gefunden'], 404);
        }

        // Überprüfe, ob der angemeldete User auch der Autor ist
        if ($post->getAuthor() !== $security->getUser()) {
            return new JsonResponse(['message' => 'Keine Berechtigung zum Löschen dieses Beitrags'], 403);
        }

        $em->remove($post);
        $em->flush();

        return new JsonResponse(['message' => 'Beitrag erfolgreich gelöscht']);
    }
}
