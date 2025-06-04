<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Entity\User;

class AuthControllerTest extends WebTestCase
{
   protected static function createKernel(array $options = []): KernelInterface
{
    return new \App\Kernel('test', true);
}
    public function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testRegisterCreatesNewUser(): void
    {
        $client = static::createClient();

        // Bereinige Testdatenbank vor dem Test (optional, falls nötig)
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository(User::class);

        // Falls User mit dieser Email existiert, löschen
        $existingUser = $userRepository->findOneBy(['email' => 'newuser@example.com']);
        if ($existingUser) {
            $entityManager->remove($existingUser);
            $entityManager->flush();
        }

        // POST Request an /register senden
        $client->request('POST', '/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ]));

        // Statuscode 201 prüfen (Created)
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // Antwortinhalt prüfen
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Benutzer erfolgreich registriert.', $responseData['message']);

        // Prüfen, ob User wirklich in der Datenbank ist
        $user = $userRepository->findOneBy(['email' => 'newuser@example.com']);
        $this->assertNotNull($user, 'Der User wurde nicht in der Datenbank gefunden.');
        $this->assertEquals('newuser@example.com', $user->getEmail());
    }

    public function testRegisterFailsIfEmailExists(): void
    {
        $client = static::createClient();

        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository(User::class);

        // Einen User mit der Email anlegen (falls noch nicht vorhanden)
        $existingUser = $userRepository->findOneBy(['email' => 'existing@example.com']);
        if (!$existingUser) {
            $existingUser = new User();
            $existingUser->setEmail('existing@example.com');
            // Wichtig: Passwort darf nicht leer sein, aber kann Dummy sein
            $existingUser->setPassword('dummyhashedpassword');
            $entityManager->persist($existingUser);
            $entityManager->flush();
        }

        // POST Request mit existierender Email
        $client->request('POST', '/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'existing@example.com',
            'password' => 'password123'
        ]));

        // Statuscode 400 prüfen (Bad Request)
        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('E-Mail ist bereits registriert.', $responseData['error']);
    }
}
