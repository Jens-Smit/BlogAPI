<?php
// src/Controller/PostController.php

namespace App\Controller;

use OpenApi\Annotations as OA;
use App\DTO\PostCreateDTO;
use App\DTO\PostUpdateDTO;
use App\Entity\Post;
use App\Service\PostService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use Nelmio\ApiDocBundle\Annotation\Model;

class PostController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/posts",
     *     summary="Alle Blogposts abrufen",
     *     tags={"Posts"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste aller Posts",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref=@Model(type=Post::class, groups={"post"}))
     *         )
     *     )
     * )
     */
    #[Route('/posts', name: 'get_posts', methods: ['GET'])]
    public function index(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $posts = $em->getRepository(Post::class)->findAll();
        $json = $serializer->serialize($posts, 'json', ['groups' => 'post']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
    /**
     * @OA\Post(
     *     path="/posts",
     *     summary="Neuen Blogpost erstellen",
     *     tags={"Posts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", example="Mein erster Post"),
     *                 @OA\Property(property="content", type="string", example="Das ist der Inhalt"),
     *                 @OA\Property(property="titleImage", type="string", format="binary"),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Post erfolgreich erstellt"),
     *     @OA\Response(response=400, description="Titel ist erforderlich"),
     *     @OA\Response(response=500, description="Serverfehler"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    #[Route('/posts', name: 'create_post', methods: ['POST'])]
    public function create(Request $request, PostService $postService, Security $security): JsonResponse
    {
        $dto = new PostCreateDTO(
            $request->request->get('title', ''),
            $request->request->get('content', null),
            $request->files->get('titleImage'),
            $request->files->get('images', [])
        );

        if (!$dto->title) {
            return new JsonResponse(['error' => 'Titel ist erforderlich.'], 400);
        }

        try {
            $post = $postService->createPost($dto, $security->getUser());
            return new JsonResponse(['message' => 'Post erfolgreich erstellt', 'id' => $post->getId()], 201);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Put(
     *     path="/posts/{id}",
     *     summary="Blogpost aktualisieren",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID des Posts",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", example="Aktualisierter Titel"),
     *                 @OA\Property(property="content", type="string", example="Neuer Inhalt"),
     *                 @OA\Property(property="titleImage", type="string", format="binary"),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Post erfolgreich aktualisiert"),
     *     @OA\Response(response=400, description="Titel ist erforderlich"),
     *     @OA\Response(response=403, description="Keine Berechtigung"),
     *     @OA\Response(response=404, description="Post nicht gefunden"),
     *     @OA\Response(response=500, description="Serverfehler"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    #[Route('/posts/{id}', name: 'update_post', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em, PostService $postService, Security $security): JsonResponse
    {
        $post = $em->getRepository(Post::class)->find($id);

        if (!$post) {
            return new JsonResponse(['error' => 'Post nicht gefunden'], 404);
        }

        if ($post->getAuthor() !== $security->getUser()) {
            return new JsonResponse(['error' => 'Keine Berechtigung'], 403);
        }

        $dto = new PostUpdateDTO(
            id: $id,
            title: $request->request->get('title', ''),
            content: $request->request->get('content'),
            titleImage: $request->files->get('titleImage'),
            images: $request->files->get('images')
        );

        if (!$dto->title) {
            return new JsonResponse(['error' => 'Titel ist erforderlich.'], 400);
        }

        try {
            $updatedPost = $postService->updatePost($post, $dto);
            return new JsonResponse(['message' => 'Post erfolgreich aktualisiert', 'id' => $updatedPost->getId()], 200);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/posts/upload",
     *     summary="Mediendatei hochladen",
     *     tags={"Uploads"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Upload erfolgreich, URL der Datei zurückgegeben"),
     *     @OA\Response(response=400, description="Keine Datei vorhanden"),
     *     @OA\Response(response=500, description="Fehler beim Upload")
     * )
     */
    #[Route('/posts/upload', name: 'upload_media', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'Keine Datei vorhanden.'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        $filename = 'media-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($uploadDir, $filename);
            $url = $request->getSchemeAndHttpHost() . '/uploads/' . $filename;
            return new JsonResponse(['url' => $url], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Fehler beim Upload: ' . $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Delete(
     *     path="/posts/{id}",
     *     summary="Blogpost löschen",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID des zu löschenden Posts",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Post gelöscht"),
     *     @OA\Response(response=403, description="Keine Berechtigung"),
     *     @OA\Response(response=404, description="Post nicht gefunden")
     * )
     */
    #[Route('/posts/{id}', name: 'delete_post', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, Security $security): JsonResponse
    {
        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post nicht gefunden'], 404);
        }

        if ($post->getAuthor() !== $security->getUser()) {
            return new JsonResponse(['error' => 'Keine Berechtigung'], 403);
        }

        $em->remove($post);
        $em->flush();

        return new JsonResponse(['message' => 'Post gelöscht']);
    }
}
