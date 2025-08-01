<?php

namespace App\Tests\Unit\Service;

use App\Service\CaptchaGeneratorService;
use PHPUnit\Framework\TestCase;

class CaptchaGeneratorServiceTest extends TestCase
{
    private CaptchaGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaptchaGeneratorService();
    }

    public function testGenerateCaptchaImagesReturnsExpectedStructure(): void
    {
        $result = $this->service->generateCaptchaImages();

        // Überprüfen, ob das Ergebnis die erwarteten Schlüssel enthält
        $this->assertArrayHasKey('imageParts', $result);
        $this->assertArrayHasKey('initialRotations', $result);

        // Überprüfen, ob 'imageParts' ein Array von 4 Elementen ist
        $this->assertIsArray($result['imageParts']);
        $this->assertCount(CaptchaGeneratorService::NUM_PARTS, $result['imageParts']);

        // Überprüfen, ob jedes imagePart ein Base64-String ist
        foreach ($result['imageParts'] as $imagePart) {
            $this->assertIsString($imagePart);
            $this->assertStringStartsWith('data:image/png;base64,', $imagePart);
            $this->assertGreaterThan(50, strlen($imagePart)); // Mindestlänge für ein Bild
        }

        // Überprüfen, ob 'initialRotations' ein Array von 4 Elementen ist
        $this->assertIsArray($result['initialRotations']);
        $this->assertCount(CaptchaGeneratorService::NUM_PARTS, $result['initialRotations']);

        // Überprüfen, ob jede initiale Rotation ein Vielfaches von ROTATION_STEP ist
        foreach ($result['initialRotations'] as $rotation) {
            $this->assertIsInt($rotation);
            $this->assertEquals(0, $rotation % CaptchaGeneratorService::ROTATION_STEP, "Rotation should be a multiple of " . CaptchaGeneratorService::ROTATION_STEP);
            $this->assertGreaterThanOrEqual(0, $rotation);
            $this->assertLessThan(360, $rotation); // Rotationen sind 0, 45, ..., 315
        }
    }

    // Dieser Test ist weniger deterministisch, da mt_rand verwendet wird.
    // Er prüft nur, ob die Werte in einem erwarteten Bereich liegen.
    public function testGenerateCaptchaImagesGeneratesRandomValuesWithinBounds(): void
    {
        // Führe den Test mehrmals aus, um die Zufälligkeit zu erhöhen
        for ($i = 0; $i < 10; $i++) {
            $result = $this->service->generateCaptchaImages();
            
            // Da mt_rand() nicht direkt mockbar ist, können wir hier keine spezifischen Werte erwarten,
            // sondern nur prüfen, ob die generierten Rotationen und Skalierungsfaktoren
            // innerhalb der im Service definierten Logik liegen.

            // Überprüfe die Rotationen (bereits im ersten Test abgedeckt, aber hier zur Vollständigkeit)
            foreach ($result['initialRotations'] as $rotation) {
                $this->assertContains($rotation, [0, 45, 90, 135, 180, 225, 270, 315]);
            }

            // Da mainShapeScaleFactor und markerCircleRadius interne Zufallswerte sind,
            // die nicht direkt im Rückgabewert des Services enthalten sind,
            // können wir sie hier nicht direkt prüfen.
            // Um sie zu prüfen, müsste der Service diese Werte im Rückgabe-Array enthalten,
            // oder wir müssten mt_rand() mocken (was komplex ist).
            // Für diesen Unit-Test konzentrieren wir uns auf die überprüfbare Ausgabe.
        }
    }
}