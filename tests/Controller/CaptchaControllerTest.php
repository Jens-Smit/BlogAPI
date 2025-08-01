<?php

namespace App\Tests\Unit\Controller;

use App\Controller\CaptchaController;
use App\Service\CaptchaGeneratorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CaptchaControllerTest extends TestCase
{
    private CaptchaGeneratorService|\PHPUnit\Framework\MockObject\MockObject $captchaGeneratorServiceMock;
    private SessionInterface|\PHPUnit\Framework\MockObject\MockObject $sessionMock;
    private CaptchaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Mocks für die Services erstellen
        $this->captchaGeneratorServiceMock = $this->createMock(CaptchaGeneratorService::class);
        $this->sessionMock = $this->createMock(SessionInterface::class);

        // Den Controller mit den Mocks instanziieren
        $this->controller = new CaptchaController($this->captchaGeneratorServiceMock);
    }

    public function testGenerateCaptcha(): void
    {
        // Erwartete Daten vom Service-Mock
        $mockImageParts = [
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
        ];
        $mockInitialRotations = [0, 90, 180, 270];

        $this->captchaGeneratorServiceMock->expects($this->once())
            ->method('generateCaptchaImages')
            ->willReturn([
                'imageParts' => $mockImageParts,
                'initialRotations' => $mockInitialRotations,
            ]);

        // Erwarteter Aufruf der Session::set Methode
        $this->sessionMock->expects($this->once())
            ->method('set')
            ->with(
                $this->stringStartsWith('captcha_initial_rotations_'), // Prüft, ob der Schlüssel mit 'captcha_initial_rotations_' beginnt
                $mockInitialRotations
            );

        // Controller-Methode aufrufen
        $response = $this->controller->generateCaptcha($this->sessionMock);

        // Überprüfen der Antwort
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('captchaId', $responseData);
        $this->assertIsString($responseData['captchaId']);
        $this->assertNotEmpty($responseData['captchaId']);

        $this->assertArrayHasKey('imageParts', $responseData);
        $this->assertEquals($mockImageParts, $responseData['imageParts']);

        $this->assertArrayHasKey('initialRotations', $responseData);
        $this->assertEquals($mockInitialRotations, $responseData['initialRotations']);
    }

    public function testVerifyCaptchaSuccess(): void
    {
        $captchaId = 'test_captcha_id';
        $storedInitialRotations = [0, 90, 180, 270]; // Die vom Server gespeicherten Rotationen
        // Die Klicks des Benutzers, die die Rotationen auf 0 zurücksetzen
        $userClicks = [0, 2, 4, 6]; // 0*(-45)=0, 2*(-45)=-90, 4*(-45)=-180, 6*(-45)=-270

        // Mocken des Session::get Aufrufs
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('captcha_initial_rotations_' . $captchaId)
            ->willReturn($storedInitialRotations);

        // Mocken des Session::remove Aufrufs
        $this->sessionMock->expects($this->once())
            ->method('remove')
            ->with('captcha_initial_rotations_' . $captchaId);

        // Die Konstanten des Services (NUM_PARTS, ROTATION_STEP) sind public const
        // und werden direkt über die Klasse (CaptchaGeneratorService::NUM_PARTS) abgerufen.
        // Sie müssen NICHT am Mock-Objekt konfiguriert werden, da PHP sie direkt auflöst.
        // Die folgenden Zeilen sind überflüssig und verursachen den Fehler:
        /*
        $this->captchaGeneratorServiceMock->method('__get')
            ->willReturnMap([
                ['NUM_PARTS', CaptchaGeneratorService::NUM_PARTS],
                ['ROTATION_STEP', CaptchaGeneratorService::ROTATION_STEP],
            ]);
        */

        // Request erstellen
        $request = Request::create(
            '/api/captcha/verify',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'captchaId' => $captchaId,
                'userClicks' => $userClicks,
            ])
        );

        // Controller-Methode aufrufen
        $response = $this->controller->verifyCaptcha($request, $this->sessionMock);

        // Überprüfen der Antwort
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('CAPTCHA erfolgreich gelöst.', $responseData['message']);
    }

    public function testVerifyCaptchaFailureIncorrectClicks(): void
    {
        $captchaId = 'test_captcha_id';
        $storedInitialRotations = [0, 90, 180, 270];
        // Falsche Klicks des Benutzers
        $userClicks = [1, 2, 3, 4]; // Führt zu falschen finalen Rotationen

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('captcha_initial_rotations_' . $captchaId)
            ->willReturn($storedInitialRotations);

        $this->sessionMock->expects($this->once())
            ->method('remove')
            ->with('captcha_initial_rotations_' . $captchaId);
        
        // Konstanten-Mocking entfernt
        /*
        $this->captchaGeneratorServiceMock->method('__get')
            ->willReturnMap([
                ['NUM_PARTS', CaptchaGeneratorService::NUM_PARTS],
                ['ROTATION_STEP', CaptchaGeneratorService::ROTATION_STEP],
            ]);
        */

        $request = Request::create(
            '/api/captcha/verify',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'captchaId' => $captchaId,
                'userClicks' => $userClicks,
            ])
        );

        $response = $this->controller->verifyCaptcha($request, $this->sessionMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Falsche CAPTCHA-Lösung. Bitte versuchen Sie es erneut.', $responseData['message']);
    }

    public function testVerifyCaptchaMissingId(): void
    {
        $request = Request::create(
            '/api/captcha/verify',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'userClicks' => [0, 0, 0, 0],
            ])
        );

        $response = $this->controller->verifyCaptcha($request, $this->sessionMock); // SessionMock wird nicht aufgerufen, ist aber ok

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('CAPTCHA ID fehlt.', $responseData['message']);
    }

    public function testVerifyCaptchaExpiredOrNotFound(): void
    {
        $captchaId = 'expired_captcha_id';
        // Session::get gibt null zurück, wenn CAPTCHA nicht gefunden
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('captcha_initial_rotations_' . $captchaId)
            ->willReturn(null);

        // Session::remove sollte nicht aufgerufen werden, wenn CAPTCHA nicht gefunden
        $this->sessionMock->expects($this->never())
            ->method('remove');

        $request = Request::create(
            '/api/captcha/verify',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'captchaId' => $captchaId,
                'userClicks' => [0, 0, 0, 0],
            ])
        );

        $response = $this->controller->verifyCaptcha($request, $this->sessionMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('CAPTCHA nicht gefunden oder abgelaufen.', $responseData['message']);
    }

    public function testVerifyCaptchaInvalidPartCount(): void
    {
        $captchaId = 'test_captcha_id';
        $storedInitialRotations = [0, 90, 180, 270];
        // Falsche Anzahl von Klicks
        $userClicks = [0, 0]; 

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('captcha_initial_rotations_' . $captchaId)
            ->willReturn($storedInitialRotations);
        
        // Konstanten-Mocking entfernt
        /*
        $this->captchaGeneratorServiceMock->method('__get')
            ->willReturnMap([
                ['NUM_PARTS', CaptchaGeneratorService::NUM_PARTS],
                ['ROTATION_STEP', CaptchaGeneratorService::ROTATION_STEP],
            ]);
        */

        $request = Request::create(
            '/api/captcha/verify',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'captchaId' => $captchaId,
                'userClicks' => $userClicks,
            ])
        );

        $response = $this->controller->verifyCaptcha($request, $this->sessionMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Ungültige Anzahl von CAPTCHA-Teilen.', $responseData['message']);
    }
}