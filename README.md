Übersicht
BlogAPI ist eine RESTful API, die mit Symfony 6 entwickelt wurde und JSON Web Tokens (JWT) zur Authentifizierung nutzt. Diese API ermöglicht es, Blog-Beiträge zu erstellen, auszulesen, zu aktualisieren und zu löschen. Sie eignet sich ideal als Grundlage für moderne Webanwendungen, die eine sichere, skalierbare API benötigen.

Features
Symfony 6: Modernes PHP-Framework für robuste Anwendungen
JWT-Authentifizierung: Sicherer Zugang zu geschützten Endpunkten
CRUD-Funktionalität: Erstellen, Lesen, Aktualisieren und Löschen von Blog-Beiträgen
Einfache Konfiguration und Erweiterbarkeit: Schnell anpassbar an individuelle Anforderungen
Voraussetzungen
PHP 8.1 oder höher
Composer zum Verwalten der PHP-Abhängigkeiten
Symfony CLI (optional, aber empfohlen)
Eine relationale Datenbank (z. B. MySQL, PostgreSQL oder SQLite)
Installation
Repository klonen:

bash
Kopieren
Bearbeiten
git clone https://github.com/Jens-Smit/BlogAPI.git
cd BlogAPI
Abhängigkeiten installieren:

bash
Kopieren
Bearbeiten
composer install
Umgebungsvariablen konfigurieren:

Erstelle eine lokale Konfigurationsdatei, indem du die .env kopierst:

bash
Kopieren
Bearbeiten
cp .env .env.local
Passe anschließend die Einträge für Datenbankverbindung, JWT-Konfiguration und andere Umgebungsvariablen nach Bedarf an.

Datenbankmigrationen ausführen:

Stelle sicher, dass deine Datenbank korrekt konfiguriert ist, und führe dann die Migrationen aus:

bash
Kopieren
Bearbeiten
php bin/console doctrine:migrations:migrate
Server starten:

Starte den Symfony-Server, um die API lokal zu testen:

bash
Kopieren
Bearbeiten
symfony server:start
Alternativ kannst du auch den eingebauten PHP-Server nutzen:

bash
Kopieren
Bearbeiten
php -S localhost:8000 -t public
API Endpoints
Eine Übersicht der wichtigsten Endpoints:

Authentifizierung
POST /api/login
Authentifiziert den Benutzer und liefert ein JWT zur Nutzung bei nachfolgenden Anfragen zurück.
Blog-Beiträge
GET /api/posts
Listet alle Blog-Beiträge auf.

GET /api/posts/{id}
Zeigt den Blog-Beitrag mit der angegebenen ID an.

POST /api/posts
Erstellt einen neuen Blog-Beitrag.
(Erfordert Authentifizierung)

PUT /api/posts/{id}
Aktualisiert den bestehenden Blog-Beitrag mit der angegebenen ID.
(Erfordert Authentifizierung)

DELETE /api/posts/{id}
Löscht den Blog-Beitrag mit der angegebenen ID.
(Erfordert Authentifizierung)

Hinweis: Die genaue Implementierung und weitere Endpoints können im Code und in der API-Dokumentation nachgelesen werden.

Testing
Um die Tests auszuführen, verwende den folgenden Befehl:

bash
Kopieren
Bearbeiten
php bin/phpunit
Beiträge
Beiträge und Feedback sind herzlich willkommen! So kannst du mitwirken:

Forke das Repository.
Erstelle einen neuen Branch (git checkout -b feature/MeinFeature).
Nimm deine Änderungen vor und schreibe ggf. passende Tests.
Sende einen Pull Request mit einer detaillierten Beschreibung deiner Änderungen.
Lizenz
Dieses Projekt ist unter der MIT-Lizenz lizenziert – siehe die LICENSE Datei für weitere Details.

Kontakt
Für Fragen, Feedback oder weitere Informationen stehe ich gerne zur Verfügung:

Jens Smit – deine.email@beispiel.de
