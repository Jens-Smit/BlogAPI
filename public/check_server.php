<?php
// check_server.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Server-Prüfung für BlogAPI</h1>";

// --- PHP Version ---
echo "<h2>PHP Version:</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "<p style='color:red;'><strong>WARNUNG:</strong> Deine PHP Version ist älter als 7.4. Die API könnte neuere Features benötigen.</p>";
}

// --- Wichtige Verzeichnisse und Dateien ---
echo "<h2>Dateisystem-Prüfung (relativ zu dieser Datei):</h2>";
$baseDir = __DIR__; // Verzeichnis, in dem diese check_server.php Datei liegt
echo "<p>Aktuelles Verzeichnis (SCRIPT_FILENAME): " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Nicht verfügbar') . "</p>";
echo "<p>Aktuelles Arbeitsverzeichnis (getcwd): " . getcwd() . "</p>";
echo "<p>Document Root (SERVER_DOCUMENT_ROOT): " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Nicht verfügbar') . "</p>";

$pathsToCheck = [
    'public/index.php',
    'public/.htaccess',
    'vendor/autoload.php',
    'config/Database.php',
    '.env' // Wichtig für Datenbank-Credentials etc.
];

foreach ($pathsToCheck as $path) {
    $fullPath = $baseDir . '/' . $path;
    // Manchmal liegt check_server.php im Root und das Projekt in einem Unterordner,
    // oder das Projekt ist der Root und check_server.php darin.
    // Hier gehen wir davon aus, dass check_server.php im Projekt-Root liegt.
    if (file_exists($path)) {
        echo "<p style='color:green;'>Datei/Verzeichnis gefunden: <strong>" . $path . "</strong> (Absoluter Pfad geprüft: " . realpath($path) . ")</p>";
    } else {
        echo "<p style='color:red;'>Datei/Verzeichnis NICHT gefunden: <strong>" . $path . "</strong> (Absoluter Pfad geprüft: " . $fullPath . ")</p>";
    }
}

// --- .htaccess und mod_rewrite ---
echo "<h2>Apache Module (falls Apache):</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "<p style='color:green;'><strong>mod_rewrite</strong> ist geladen.</p>";
    } else {
        echo "<p style='color:red;'><strong>mod_rewrite</strong> ist NICHT geladen. Das ist oft ein Problem für "Clean URLs" und Routing.</p>";
    }
} else {
    echo "<p>Konnte Apache-Module nicht prüfen (eventuell kein Apache oder Funktion deaktiviert).</p>";
}
echo "<p>Prüfe manuell, ob die <code>.htaccess</code>-Datei in <code>public/.htaccess</code> korrekt hochgeladen wurde und ob dein Hoster <code>AllowOverride All</code> für dein Verzeichnis gesetzt hat (das kannst du oft nicht selbst einstellen, sondern musst den Support fragen, falls Probleme mit <code>.htaccess</code> bestehen).</p>";


// --- PHP Erweiterungen ---
echo "<h2>PHP Erweiterungen:</h2>";
$requiredExtensions = [
    'pdo_mysql' => 'Für MySQL-Datenbankverbindungen (PDO)',
    'json'      => 'Für JSON Encoding/Decoding',
    'openssl'   => 'Für JWT (php-jwt Bibliothek)',
    'mbstring'  => 'Für Multibyte-String-Operationen',
    // 'dom'       => 'Für Swagger/OpenAPI UI, falls es XML verarbeitet (oft der Fall)',
    // 'xmlreader' => 'Für Swagger/OpenAPI UI',
    // 'xmlwriter' => 'Für Swagger/OpenAPI UI'
];
foreach ($requiredExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "<p style='color:green;'>Erweiterung <strong>" . $ext . "</strong> ist geladen. (" . $desc . ")</p>";
    } else {
        echo "<p style='color:red;'>Erweiterung <strong>" . $ext . "</strong> ist NICHT geladen. (" . $desc . ")</p>";
    }
}

// --- Datenbankverbindung (Versuch) ---
echo "<h2>Datenbankverbindungs-Test:</h2>";
if (file_exists($baseDir . '/.env')) {
    // Vereinfachtes Laden der .env-Variablen für den Test
    $envLines = file($baseDir . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Kommentare ignorieren
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Entferne Anführungszeichen, falls vorhanden
        if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
            $value = substr($value, 1, -1);
        }
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value; // Manchmal werden sie so erwartet
        putenv("$name=$value");   // Oder so
    }
    echo "<p>.env Datei gefunden und geladen (vereinfacht).</p>";
    echo "<p>DB_HOST aus .env: " . ($_ENV['DB_HOST'] ?? 'Nicht gesetzt') . "</p>";
    echo "<p>DB_DATABASE aus .env: " . ($_ENV['DB_DATABASE'] ?? 'Nicht gesetzt') . "</p>";
    echo "<p>DB_USERNAME aus .env: " . ($_ENV['DB_USERNAME'] ?? 'Nicht gesetzt') . "</p>";
    // Passwort nicht anzeigen!
} else {
    echo "<p style='color:orange;'>.env Datei nicht gefunden. Datenbanktest übersprungen. Stelle sicher, dass die .env Datei existiert und die korrekten Zugangsdaten enthält.</p>";
}

if (file_exists($baseDir . '/config/Database.php') && file_exists($baseDir . '/vendor/autoload.php') && file_exists($baseDir . '/.env')) {
    // Autoloader einbinden, damit die Database Klasse gefunden wird
    require_once $baseDir . '/vendor/autoload.php';
    // Database Klasse einbinden
    require_once $baseDir . '/config/Database.php';

    // dotenv laden, damit die Database Klasse die Credentials hat
    try {
        $dotenv = Dotenv\Dotenv::createImmutable($baseDir);
        $dotenv->load();
        echo "<p>PHP dotenv geladen.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Fehler beim Laden von PHP dotenv: " . $e->getMessage() . "</p>";
    }


    $database = new Config\Database(); // Namespace anpassen, falls deiner anders ist!
                                  // In deinem Fall: Config\Database
    try {
        $db_connection = $database->getConnection();
        if ($db_connection) {
            echo "<p style='color:green;'><strong>Datenbankverbindung erfolgreich!</strong></p>";
            $stmt = $db_connection->query("SELECT VERSION()");
            $mysql_version = $stmt->fetchColumn();
            echo "<p>MySQL Version: " . $mysql_version . "</p>";
        } else {
            echo "<p style='color:red;'><strong>Datenbankverbindung fehlgeschlagen!</strong> Keine Exception, aber auch keine Verbindung.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'><strong>Datenbankverbindungsfehler (PDOException):</strong> " . $e->getMessage() . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'><strong>Allgemeiner Fehler beim Datenbankverbindungsversuch:</strong> " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:orange;'>Überspringe Datenbanktest, da `config/Database.php`, `vendor/autoload.php` oder `.env` nicht gefunden wurde.</p>";
}

echo "<h2>Webserver-Konfiguration für `public` Verzeichnis:</h2>";
echo "<p>Der DocumentRoot deines Webservers sollte auf das <code>public</code> Verzeichnis deines Projekts zeigen.</p>";
echo "<p>Aktueller Pfad zu <code>public/index.php</code> relativ zu dieser Prüfdatei: <code>" . $baseDir . "/public/index.php" . "</code></p>";
echo "<p>Wenn du <code>deinedomain.de/index.php</code> aufrufst und einen 404 bekommst, aber <code>deinedomain.de/public/index.php</code> funktioniert (oder auch nicht), dann ist der DocumentRoot wahrscheinlich nicht korrekt auf das <code>public</code> Verzeichnis gesetzt.</p>";
echo "<p>Der vorgesehene Zugriffspunkt ist <code>https://deinedomain.de/</code> (was intern auf <code>public/index.php</code> gemappt werden sollte), nicht <code>https://deinedomain.de/public/</code>.</p>";

echo "<h2>Mögliche nächste Schritte basierend auf den Fehlern:</h2>";
echo "<ul>";
echo "<li><strong>404 für index.php:</strong>
        <ul>
            <li>Stelle sicher, dass alle Dateien korrekt hochgeladen wurden, insbesondere <code>public/index.php</code> und <code>public/.htaccess</code>. FTP-Programme überspringen manchmal versteckte Dateien (wie <code>.htaccess</code>).</li>
            <li>Prüfe, ob dein Webhosting so konfiguriert ist, dass der <strong>DocumentRoot</strong> auf das <code>public/</code> Verzeichnis deiner Anwendung zeigt. Wenn dein Projekt z.B. in <code>/httpdocs/meinprojekt/</code> liegt, dann sollte der DocumentRoot auf <code>/httpdocs/meinprojekt/public/</code> zeigen. Das ist die häufigste Ursache für 404-Fehler bei Projekten mit einem public-Unterverzeichnis. Wenn du das nicht selbst einstellen kannst, muss das der Hoster machen.</li>
            <li>Wenn der DocumentRoot nicht auf `public/` gesetzt werden kann, müsstest du versuchen, die Inhalte von `public/` (also `index.php`, `.htaccess` etc.) in das Hauptverzeichnis (WebRoot) zu verschieben und die Pfade in `index.php` (z.B. für `require '../vendor/autoload.php';`) entsprechend anzupassen. Das ist aber meist komplizierter.</li>
        </ul>
    </li>";
echo "<li><strong>500 bei POST-Requests (z.B. /register):</strong>
        <ul>
            <li>Fehlende PHP-Erweiterungen (siehe oben).</li>
            <li>Fehlerhafte Datenbankverbindung (siehe oben). Prüfe die Zugangsdaten in deiner <code>.env</code> Datei! Stelle sicher, dass die <code>.env</code> Datei auch auf dem Server vorhanden und lesbar ist.</li>
            <li>Fehlende <code>vendor</code> Verzeichnis: Hast du <code>composer install</code> lokal ausgeführt und das <code>vendor</code> Verzeichnis komplett hochgeladen? Ohne SSH kannst du <code>composer install</code> nicht auf dem Server ausführen.</li>
            <li>Fehler im PHP-Code, die erst bei Ausführung sichtbar werden (Syntaxfehler, falsche Pfade in `require` Statements etc.). Die Testdatei sollte einige davon aufdecken.</li>
            <li>Probleme mit der <code>.htaccess</code>-Datei in <code>public/</code> (z.B. wenn <code>mod_rewrite</code> nicht aktiv ist oder bestimmte Direktiven nicht erlaubt sind).</li>
        </ul>
    </li>";
echo "<li><strong>api/doc nicht erreichbar:</strong>
        <ul>
            <li>Das ist wahrscheinlich eine Folge des 404-Fehlers für <code>index.php</code> oder Probleme mit der URL-Rewrite-Logik in <code>public/.htaccess</code>. Wenn <code>index.php</code> nicht korrekt über die Root-URL erreichbar ist, funktionieren auch die Sub-Routen nicht.</li>
            <li>Prüfe, ob das Verzeichnis <code>api/doc/</code> überhaupt existiert und ob die Dateien darin korrekt hochgeladen wurden, falls es statische HTML/JS-Dateien für die Doku sind. Dein Projekt scheint die Doku aber eher dynamisch über Routen zu generieren.</li>
        </ul>
    </li>";
echo "</ul>";

echo "<hr><p><em>Ende der Prüfung.</em></p>";

?>