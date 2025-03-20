# BlogAPI

_Einfache Symfony 6 API mit JWT-Authentifizierung_

## Übersicht

**BlogAPI** ist eine RESTful API, die mit Symfony 6 entwickelt wurde und JSON Web Tokens (JWT) zur Authentifizierung nutzt. Diese API ermöglicht es, Blog-Beiträge zu erstellen, auszulesen, zu aktualisieren und zu löschen – ideal als Grundlage für moderne Webanwendungen, die eine sichere, skalierbare API benötigen.

## Features

- **Symfony 6:** Modernes PHP-Framework für robuste Anwendungen.
- **JWT-Authentifizierung:** Sicherer Zugang zu geschützten Endpunkten.
- **CRUD-Funktionalität:** Erstellen, Lesen, Aktualisieren und Löschen von Blog-Beiträgen.
- **Einfache Konfiguration und Erweiterbarkeit:** Schnell anpassbar an individuelle Anforderungen.

## Voraussetzungen

- **PHP 8.1** oder höher
- **Composer** zum Verwalten der PHP-Abhängigkeiten
- **Symfony CLI** (optional, aber empfohlen)
- Eine relationale Datenbank (z. B. MySQL, PostgreSQL oder SQLite)

## Installation

1. **Repository klonen:**

   ```bash
   git clone https://github.com/Jens-Smit/BlogAPI.git
   cd BlogAPI

2. *Abhängigkeiten installieren:**

   ```bash
   composer install

3. *Umgebungsvariablen konfigurieren:**

Passe in der .env die Einträge für Datenbankverbindung, JWT-Konfiguration und andere Umgebungsvariablen nach Bedarf an


4. **Datenbankmigrationen ausführen:**
Stelle sicher, dass deine Datenbank korrekt konfiguriert ist, und führe dann die Migrationen aus:
   ```bash
   php bin/console doctrine:migrations:migrate

4. **Server starten:**
Stelle sicher, dass deine Datenbank korrekt konfiguriert ist, und führe dann die Migrationen aus:
   ```bash
   symfony server:start

Alternativ kannst du auch den eingebauten PHP-Server nutzen:

     ```bash
     php -S localhost:8000 -t public
   
## API Endpoints

### Authentifizierung

- **POST** `/api/login`  
  Authentifiziert den Benutzer und liefert ein JWT zur Nutzung bei nachfolgenden Anfragen zurück.

### Blog-Beiträge

- **GET** `/api/posts`  
  Listet alle Blog-Beiträge auf.

- **GET** `/api/posts/{id}`  
  Zeigt den Blog-Beitrag mit der angegebenen ID an.

- **POST** `/api/posts`  
  Erstellt einen neuen Blog-Beitrag. *(Erfordert Authentifizierung)*

- **DELETE** `/api/posts/{id}`  
  Löscht den Blog-Beitrag mit der angegebenen ID. *(Erfordert Authentifizierung)*

> **Hinweis:** Weitere Details und zusätzliche Endpoints findest du in der API-Dokumentation oder im Code.

## Mitwirken

Deine Ideen, Verbesserungen und Korrekturen sind herzlich willkommen! Wenn du das Projekt unterstützen und gemeinsam weiterentwickeln möchtest, folge diesen einfachen Schritten:

1. Forke das Repository.
2. Erstelle einen neuen Branch (z. B. `git checkout -b feature/DeinFeature`).
3. Nimm deine Änderungen vor und füge, falls nötig, passende Tests hinzu.
4. Reiche einen Pull Request ein und beschreibe deine Änderungen ausführlich.

Jeder Beitrag, ob groß oder klein, trägt dazu bei, die API weiter zu verbessern. Vielen Dank für dein Engagement und deine Unterstützung!

## Lizenz

Dieses Projekt steht unter der MIT-Lizenz – siehe die [LICENSE](LICENSE) Datei für weitere Details.

## Kontakt

Bei Fragen, Anregungen oder Feedback kannst du mich jederzeit kontaktieren:

- **Jens Smit** – [j.smit@hotmail.de](mailto:j.smit@hotmail.de)
