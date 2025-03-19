<?php
// src/Controller/AuthController.php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController
{
    
    #[Route('/register', name: 'user_register', methods:['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        
        // Hier sollte ein UserRepository abgefragt werden, ob die E-Mail schon existiert.
        // Beispiel: if ($em->getRepository(User::class)->findOneBy(['email' => $email])) { ... }

        $user = new User();
        
        $user->setEmail($email);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'Benutzer erfolgreich registriert'], 201);
    }

    // Der Login wird oft durch das Bundle (über die Firewall) abgewickelt.
}
