# Mühlviertler Speckstüberl – Website

## Lokal ansehen

Voraussetzung: **PHP 8** (wegen des Kontaktformulars).

| System  | PHP installieren                         | Starten                         |
|---------|------------------------------------------|---------------------------------|
| Windows | `winget install PHP.PHP.8.3`             | Doppelklick auf **start.bat**   |
| macOS   | `brew install php`                       | `./start.sh` im Terminal        |
| Linux   | `sudo apt install php-cli`               | `./start.sh` im Terminal        |

Ohne PHP startet automatisch ein einfacher Server (Python), dann funktioniert nur das Formular nicht.
Noch einfacher: `index.html` doppelklicken.

Danach im Browser: **http://localhost:8000** (Team-Seite: http://localhost:8000/team.html).
Anderer Port: `./start.sh 8080` bzw. `start.bat 8080`.

Lokal werden die Mails aus dem Formular **nicht verschickt**, sondern als `.eml`-Datei im Ordner
`mails/` gespeichert. Die lassen sich mit Outlook, Thunderbird, Apple Mail oder einem Texteditor öffnen.

## Kontaktformular

Ganz einfach: `kontakt.php` schickt die Angaben aus dem Formular mit der PHP-Funktion `mail()`
des Webhosters an **schuster@indinger.email** (auch als Absender). Kein Passwort, keine Zusatzdateien.
Adressen ändern: oben in `kontakt.php`.

- „Antworten“ im Mailprogramm geht direkt an den Gast (wenn er eine E-Mail angegeben hat).
- Unsichtbarer Spam-Schutz (Honeypot-Feld).
- Funktioniert nur auf einem Webserver mit PHP (Webhoster). In der Claude-Vorschau läuft kein PHP;
  lokal mit `start.sh`/`start.bat` landen die Mails im Ordner `mails/`.

## Bilder

| Ordner | Inhalt |
|---|---|
| `img/website/` | Alle Fotos der Startseite (Auswahl aus dem Fotoshooting), im schnellen **WebP**-Format |
| `img/website/klein/` | Kleine Vorschaubilder für die Galerie (das große Bild lädt erst beim Anklicken) |
| `img/team/` | Fotos für die Team-Seite |
| `img/alt/` | Die alten Fotos der früheren Website (werden nicht mehr verwendet) |
| `img/icons/` | Favicon / App-Icons |

**Bild austauschen:** neue Datei **mit gleichem Namen** in den Ordner legen. Damit die Seite schnell
bleibt, Fotos vorher verkleinern und als WebP speichern, z. B. kostenlos mit https://squoosh.app:
- Startseite/Speisekarte: ca. 900–1300 px breit, Qualität ~70
- Galerie: groß 1600 px in `img/website/`, klein 640 px in `img/website/klein/`
- Team: ca. 800 px hoch

Die Galerie wird über die Liste `PICS` am Ende von `index.html` gesteuert.
`img/website/vorschau.jpg` ist das Bild, das WhatsApp/Facebook beim Teilen des Links anzeigt.

## Team-Seite pflegen

`team.html` öffnen – oben in der Datei steht eine kurze Anleitung. Fotos kommen nach `img/team/`.
Die Service- und Küchen-Karten sind noch **Platzhalter** („Vorname Nachname“) und müssen ausgefüllt werden.

## Dateien

```
index.html          Startseite
team.html           Unser Team
kontakt.php         Formular → E-Mail an schuster@indinger.email
                    (Empfänger/Absender stehen oben in kontakt.php)
css/style.css       Gestaltung (für alle Seiten)
css/fonts.css       Schriften – lokal aus fonts/, keine Verbindung zu Google
js/common.js        Menü, Header, Dialoge (für alle Seiten)
favicon.ico/.svg    Favicon, dazu img/icons/ und site.webmanifest
start.sh/start.bat  lokaler Server
tools/              nur für den lokalen Server (Router + Mail-Fänger)
.htaccess           Schutz interner Dateien auf Apache-Hosting
```
