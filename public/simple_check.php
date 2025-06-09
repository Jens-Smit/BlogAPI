<?php
// simple_check.php

// Zeigt alle PHP-Fehler direkt im Browser an.
// Das ist entscheidend für die Fehlersuche ohne SSH-Zugang.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Einfacher Server-Check für BlogAPI</h1>";
echo "<p>Dieses Skript soll prüfen, ob grundlegende PHP-Funktionen auf deinem Webserver laufen.</p>";

echo "---";

// --- 1. PHP Version prüfen ---
echo "<h2>1. PHP Version:</h2>";
$phpVersion = phpversion();
echo "<p>Deine PHP Version ist: <strong>" . $phpVersion . "</strong></p>";

if (version_compare($phpVersion, '7.4.0', '<')) {
    echo "<p style='color:orange;'><strong>Hinweis:</strong> PHP Version 7.4 oder neuer wird für viele moderne PHP-Projekte empfohlen. Deine Version könnte älter sein.</p>";
}

echo "---";

// --- 2. Grundlegende Dateizugriffe testen ---
echo "<h2>2. Dateizugriffe:</h2>";

// Testet, ob das aktuelle Verzeichnis lesbar ist
echo "<p>Aktuelles Verzeichnis: <strong>" . getcwd() . "</strong></p>";

// Testet, ob die .env Datei existiert (wichtig für Konfiguration)
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "<p style='color:green;'>Die <code>.env</code> Datei wurde gefunden.</p>";
} else {
    echo "<p style='color:orange;'>Die <code>.env</code> Datei wurde NICHT gefunden unter: " . $envPath . "</p>";
    echo "<p><strong>WICHTIG:</strong> Wenn deine BlogAPI eine <code>.env</code> Datei für Konfiguration (z.B. Datenbankzugriff) benötigt, muss diese im Hauptverzeichnis deines Projekts liegen.</p>";
}

// Testet, ob die public/index.php existiert (oft der Haupteinstiegspunkt)
$publicIndexPath = __DIR__ . '/public/index.php';
if (file_exists($publicIndexPath)) {
    echo "<p style='color:green;'>Die <code>public/index.php</code> Datei wurde gefunden.</p>";
} else {
    echo "<p style='color:red;'>Die <code>public/index.php</code> Datei wurde NICHT gefunden unter: " . $publicIndexPath . "</p>";
    echo "<p>Dies ist oft die Ursache für den 404-Fehler. Stelle sicher, dass die Datei hochgeladen wurde und der Pfad stimmt.</p>";
}

echo "---";

// --- 3. Wichtige PHP Erweiterungen prüfen ---
echo "<h2>3. PHP Erweiterungen:</h2>";
echo "<p>Die BlogAPI benötigt bestimmte PHP-Erweiterungen. Hier sind einige der wichtigsten:</p>";

$extensions = [
    'pdo_mysql' => 'Für die Verbindung zu MySQL-Datenbanken.',
    'json'      => 'Für das Verarbeiten von JSON-Daten (APIs verwenden dies oft).',
    'openssl'   => 'Wird oft für Sicherheit, z.B. bei JWTs (Tokens), benötigt.',
    'mbstring'  => 'Für die korrekte Handhabung von Text mit Sonderzeichen (z.B. Umlaute).'
];

foreach ($extensions as $ext => $description) {
    if (extension_loaded($ext)) {
        echo "<p style='color:green;'>&#10003; Erweiterung <strong>" . $ext . "</strong> ist geladen. (" . $description . ")</p>";
    } else {
        echo "<p style='color:red;'>&#10007; Erweiterung <strong>" . $ext . "</strong> ist NICHT geladen. (" . $description . ")</p>";
    }
}

echo "---";

// --- 4. mod_rewrite prüfen (falls Apache) ---
echo "<h2>4. Apache mod_rewrite (falls zutreffend):</h2>";
if (function_exists('apache_get_modules')) {
    if (in_array('mod_rewrite', apache_get_modules())) {
        echo "<p style='color:green;'>&#10003; Apache <strong>mod_rewrite</strong> ist geladen. Das ist gut für "Clean URLs" und Routing.</p>";
    } else {
        echo "<p style='color:red;'>&#10007; Apache <strong>mod_rewrite</strong> ist NICHT geladen. Dies könnte der Grund sein, warum deine API-Routen (wie /register) nicht funktionieren und 404-Fehler verursachen, wenn sie über .htaccess umgeschrieben werden sollen.</p>";
    }
} else {
    echo "<p>Konnte Apache-Module nicht prüfen (dein Server ist eventuell kein Apache oder die Funktion ist deaktiviert).</p>";
}
echo "<p><strong>Tipp:</strong> Stelle sicher, dass deine <code>public/.htaccess</code>-Datei korrekt hochgeladen wurde. Manchmal werden versteckte Dateien (beginnend mit einem Punkt) vom FTP-Programm ignoriert.</p>";

echo "---";

echo "<h2>Zusammenfassung & Nächste Schritte:</h2>";
echo "<ul>";
echo "<li>Überprüfe die **PHP-Version** und installiere ggf. eine neuere über dein Hoster-Panel.</li>";
echo "<li>Prüfe die **Dateipfade** und stelle sicher, dass alle wichtigen Dateien (besonders `.env` und `public/index.php`) korrekt hochgeladen wurden und an den erwarteten Stellen liegen.</li>";
echo "<li>Stelle sicher, dass alle benötigten **PHP-Erweiterungen** aktiviert sind. Das kannst du oft im PHP-Manager oder über den Support deines Hosters einstellen.</li>";
echo "<li>Wenn `mod_rewrite` fehlt, wende dich an deinen Hoster. Das ist eine Hauptursache für Routing-Probleme.</li>";
echo "<li>Der **DocumentRoot** deines Webservers sollte auf das **`public/`**-Verzeichnis deines Projekts zeigen. Wenn das nicht der Fall ist, funktionieren die URLs deiner API nicht richtig (z.B. `deinedomain.de/api/register` wird zu `deinedomain.de/public/api/register`). Dies ist der häufigste Grund für 404-Fehler bei Frameworks. Kläre das mit deinem Hoster!</li>";
echo "</ul>";

echo "<hr><p><em>Ende der Prüfung.</em></p>";
?>