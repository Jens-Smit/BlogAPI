# BlogAPI
<<<<<<< HEAD

=======
>>>>>>> 87436c0 (update)
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
<<<<<<< HEAD
- **Composer** zum Verwalten der PHP-Abhängigkeiten
- **Symfony CLI** (optional, aber empfohlen)
- Eine relationale Datenbank (z. B. MySQL, PostgreSQL oder SQLite)
=======
- **Composer**
- **Symfony CLI** (optional)
- Datenbank: MySQL, PostgreSQL oder SQLite
>>>>>>> 87436c0 (update)

## Installation

1. **Repository klonen:**

   ```bash
   git clone https://github.com/Jens-Smit/BlogAPI.git
   cd BlogAPI
<<<<<<< HEAD

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

     
     php -S localhost:8000 -t public
   
## API Endpoints

### Authentifizierung
- **POST** `/api/register`  
  Registriert einen neuen Benutzer. *Hinweis: Dieser Endpunkt erfordert keine E-Mail-Verifizierung.*
      
  ```json
    {
  "email": "YourEmail",
  "password": "YourPassword"
   }
  
- **POST** `/api/login`  
  Authentifiziert den Benutzer und liefert ein JWT zur Nutzung bei nachfolgenden Anfragen zurück.
      
  ```json
    {
  "email": "YourEmail",
  "password": "YourPassword"
   }

### Blog-Beiträge

- **GET** `/api/posts`  
  Listet alle Blog-Beiträge auf.
    ```json
       {
     "email": "YourEmail",
     "password": "YourPassword"
      }

- **POST** `/api/posts`  
  Erstellt einen neuen Blog-Beitrag. *(Erfordert Authentifizierung)*
  **header**
     ```json
          {
        "email": "YourEmail",
        "password": "YourPassword"
         }

 **body**

      {
        "email": "YourEmail",
        "password": "YourPassword"
         }

- **DELETE** `/api/posts/{id}`  
  Löscht den Blog-Beitrag mit der angegebenen ID. *(Erfordert Authentifizierung)*

## Upload Media Endpoint

### URL
`POST /posts/upload`

### Beschreibung
Dieser Endpunkt ermöglicht das Hochladen von Mediendateien (z. B. Bilder, Videos) an den Server. Nach einem erfolgreichen Upload wird eine permanente URL zurückgegeben, unter der die Datei abgerufen werden kann.

### Anfrageparameter

- **file** (erforderlich):  
  Die hochzuladende Datei, die als `UploadedFile` erwartet wird. Diese muss über das Formularfeld `file` gesendet werden.

### Beispiel-Anfrage (cURL)

      
      curl -X POST http://127.0.0.1:8000/posts/upload \
        -F "file=@/pfad/zur/deiner/datei.jpg"

- **Erfolgreiche Antwort
- Statuscode: **201 Created**
- Antwortinhalt (JSON):

      {
        "url": "http://dein-server.de/uploads/media-uniqueid.jpg"
      }

- **Fehlerfälle**

         Keine Datei hochgeladen oder ungültiges Dateiformat

- Statuscode: **400 Bad Request**

- Antwortinhalt (JSON):


      {
        "error": "Keine Datei hochgeladen oder ungültiges Dateiformat."
      }

**Fehler beim Hochladen**

Tritt ein interner Fehler beim Verschieben der Datei in das Upload-Verzeichnis auf, wird folgender Fehler zurückgegeben:

Statuscode: **500 Internal Server Error**

Antwortinhalt (JSON):


            {
              "error": "Fehler beim Hochladen: [Fehlermeldung]"
            }
## Mitwirken ##

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
=======
   ```

2. **Abhängigkeiten installieren:**

   ```bash
   composer install
   ```

3. **Umgebungsvariablen konfigurieren:**

   Passe die `.env`-Datei an deine Datenbank, JWT-Schlüssel und andere Variablen an.

4. **Datenbankmigrationen ausführen:**

   ```bash
   php bin/console doctrine:migrations:migrate
   ```

5. **Server starten:**

   ```bash
   symfony server:start
   ```

   Oder alternativ:

   ```bash
   php -S localhost:8000 -t public
   ```

## API Endpoints

### Authentifizierung

- **POST** `/register`

  ```json
  {
    "email": "YourEmail",
    "password": "YourPassword"
  }
  ```

- **POST** `/login`

  ```json
  {
    "email": "YourEmail",
    "password": "YourPassword"
  }
  ```

### Blog-Beiträge

- **GET** `/posts` – Listet alle Beiträge auf
- **POST** `/posts` – Erstellt einen neuen Beitrag

  **Header:**
  ```json
  {
    "Authorization": "Bearer <TOKEN>"
  }
  ```

  **Body:**
  ```json
  {
    "title": "Mein neuer Blogpost",
    "content": "Hier steht der Inhalt des Posts",
    "titleImage": "file",
    "images": ["file1", "file2"]
  }
  ```

- **DELETE** `/api/posts/{id}` – Löscht einen Beitrag

## Upload Media Endpoint

### POST `/posts/upload`

**Beispiel (cURL):**

```bash
curl -X POST http://127.0.0.1:8000/posts/upload -F "file=@/pfad/zur/deiner/datei.jpg"
```

**Erfolgreiche Antwort:**

```json
{
  "url": "http://dein-server.de/uploads/media-uniqueid.jpg"
}
```

**Fehlerantworten:**
- 400 Bad Request – Keine Datei hochgeladen oder ungültig
- 500 Internal Server Error – Fehler beim Speichern

## Mitwirken

1. Forke das Repository.
2. Erstelle einen neuen Branch (`git checkout -b feature/DeinFeature`).
3. Ändere den Code und füge Tests hinzu.
4. Reiche einen Pull Request ein.

## Lizenz

Dieses Projekt steht unter der MIT-Lizenz – siehe die [LICENSE](LICENSE).

## Kontakt

**Jens Smit** – [j.smit@hotmail.de](mailto:j.smit@hotmail.de)
>>>>>>> 87436c0 (update)
