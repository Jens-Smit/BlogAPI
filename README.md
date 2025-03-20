BlogAPI – Simple Symfony 6 API mit JWT-Authentifizierung

Eine schlanke API-Basis, die mit Symfony 6 realisiert wurde und JSON Web Tokens (JWT) zur Authentifizierung verwendet. Die BlogAPI dient als Backend-Lösung für Blog-Anwendungen, bei denen Nutzer sich registrieren, anmelden und Inhalte verwalten können.

Inhalt

Über das Projekt

Voraussetzungen

Installation

Konfiguration

Verwendung

Endpoints

Tests

Mitwirken

Lizenz

Kontakt


Über das Projekt

Die BlogAPI ist eine RESTful-API, die auf Symfony 6 basiert. Sie bietet Endpunkte zur Verwaltung von Blogbeiträgen, Benutzerregistrierung und –anmeldung sowie zum Schutz von Ressourcen mittels JWT. Dieses Projekt eignet sich als Ausgangsbasis für die Entwicklung moderner Webanwendungen, die eine robuste Authentifizierung benötigen.

Features:

Benutzerregistrierung und -anmeldung

Geschützte Endpunkte mit JWT-Authentifizierung

CRUD-Funktionalitäten für Blogbeiträge

Einsatz moderner Symfony 6-Komponenten und Best Practices


Voraussetzungen

PHP ≥ 8.1

Composer (Dependency Manager für PHP)

Symfony CLI (optional, aber empfohlen)

Datenbank (z. B. MySQL, PostgreSQL oder SQLite)


Installation

1. Repository klonen:

git clone https://github.com/Jens-Smit/BlogAPI.git
cd BlogAPI


2. Abhängigkeiten installieren:

composer install


3. Datenbank konfigurieren:

Passe deine Datenbankeinstellungen in der .env‑Datei an (z. B. DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/blog_api").


4. Datenbank erstellen und Migrationen ausführen:

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate



Konfiguration

JWT-Authentifizierung:
Stelle sicher, dass du den privaten und öffentlichen Schlüssel für JWT generierst. Dies kannst du mit dem folgenden Befehl erledigen:

php bin/console lexik:jwt:generate-keypair

Umgebungsvariablen:
Neben den Datenbankeinstellungen solltest du in der .env‑Datei weitere Konfigurationswerte (z. B. den JWT-Passphrase) festlegen.


Verwendung

Starte den Symfony-Server:

symfony server:start

Die API ist dann unter http://localhost:8000 erreichbar.

Beispiel: Anmeldung und JWT erhalten

1. Sende eine POST-Anfrage an /api/login_check mit deinen Benutzerdaten (z. B. JSON):

{
  "username": "deinBenutzername",
  "password": "deinPasswort"
}


2. Bei erfolgreicher Authentifizierung erhältst du ein JWT, das du in den Headern weiterer Anfragen verwenden kannst:

Authorization: Bearer <dein_jwt_token>



Endpoints

Einige der wichtigsten Endpunkte:

POST /api/login_check
Authentifizierung und JWT-Erstellung.

GET /api/posts
Abrufen aller Blogbeiträge (öffentlicher Endpunkt).

POST /api/posts
Erstellen eines neuen Blogbeitrags (geschützt – JWT erforderlich).

PUT /api/posts/{id}
Aktualisieren eines Blogbeitrags (geschützt).

DELETE /api/posts/{id}
Löschen eines Blogbeitrags (geschützt).


Weitere Endpunkte und deren Dokumentation findest du in der API-Dokumentation (optional: Link anpassen).

Tests

Um die automatisierten Tests auszuführen, nutze folgenden Befehl:

php bin/phpunit

So kannst du sicherstellen, dass alle Funktionen wie erwartet arbeiten.

Mitwirken

Beiträge sind herzlich willkommen! Wenn du Fehler findest oder neue Funktionen einbringen möchtest, erstelle bitte ein Issue oder einen Pull Request. Bitte beachte unsere Beitragsrichtlinien und den Verhaltenskodex.

Lizenz

Dieses Projekt ist unter der MIT-Lizenz lizenziert.

Kontakt

Wenn du Fragen oder Feedback hast, kannst du mich unter folgender E-Mail-Adresse erreichen:

Jens Smit – jens.smit@example.com


