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

- `kontakt.php` nimmt die Anfrage entgegen, prüft sie und schickt sie mit PHP `mail()` an die
  Adresse aus `config.php` (derzeit `speckstueberl@aon.at`).
- Antworten-Knopf im Mailprogramm geht direkt an den Gast (Reply-To).
- Spam-Schutz: unsichtbares Honeypot-Feld, Mindest-Ausfülldauer, max. 5 Anfragen pro Besucher in 10 Minuten.
- Funktioniert auch ohne JavaScript.

**Beim Hosting beachten:** In `config.php` den `absender` auf eine Adresse der eigenen Domain setzen
(z. B. `website@muehlviertler-speckstueberl.at`), sonst landen die Mails leicht im Spam.
Die meisten österreichischen Webhoster (World4You, easyname, …) unterstützen `mail()` direkt.

## Bilder austauschen

Alle Fotos liegen in `img/`. Einfach eine neue Datei **mit gleichem Namen** hineinkopieren, dann ist sie
überall auf der Seite ausgetauscht. Empfehlung: JPG, lange Seite ca. 1600 px, unter 400 KB.

| Datei | Wo auf der Seite |
|---|---|
| `eingang-weinlaub.jpg` | Startbild groß (Hero) |
| `karreespeck.jpg` | Startbild klein, rund |
| `gemischte-platte.jpg`, `hausbrot-speck.jpg`, `kaeseplatte.jpg` | „Unsere Schmankerl“-Collage |
| `gemischte-platte.jpg`, `belegtes-brot.jpg`, `hausbrot-speck.jpg`, `fleischplatte.jpg`, `stube.jpg` | Speisekarte (je Reiter) |
| `kaltes-buffet.jpg` | Hintergrund „Feste & Buffet“ |
| `haus-frueher.jpg`, `fleischerei-schuster.jpg`, `haus-heute.jpg` | Geschichte |
| `gastgeberin.jpg` | Geschichte + Team-Seite (Sabine) |
| Liste `PICS` am Ende von `index.html` | Galerie „Bilder“ |

## Team-Seite pflegen

`team.html` öffnen – oben in der Datei steht eine kurze Anleitung. Fotos kommen nach `img/team/`.
Die Service- und Küchen-Karten sind noch **Platzhalter** („Vorname Nachname“) und müssen ausgefüllt werden.

## Dateien

```
index.html          Startseite
team.html           Unser Team
kontakt.php         Formular → E-Mail
config.php          Empfänger, Absender, Spam-Limit
css/style.css       Gestaltung (für alle Seiten)
js/common.js        Menü, Header, Dialoge (für alle Seiten)
favicon.ico/.svg    Favicon, dazu img/icons/ und site.webmanifest
start.sh/start.bat  lokaler Server
tools/              nur für den lokalen Server (Router + Mail-Fänger)
.htaccess           Schutz interner Dateien auf Apache-Hosting
```
