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

## Bilder

| Ordner | Inhalt |
|---|---|
| `img/website/` | Alle Fotos, die auf der Startseite verwendet werden (Auswahl aus dem Fotoshooting) |
| `img/team/` | Fotos für die Team-Seite |
| `img/alt/` | Die alten Fotos der früheren Website (werden nicht mehr verwendet) |
| `img/icons/` | Favicon / App-Icons |

Austauschen: neue Datei **mit gleichem Namen** in den Ordner kopieren. Empfehlung: JPG, lange Seite
ca. 1400–1600 px, unter 400 KB. Die Namen sagen, wo das Bild erscheint (`startbild-eingang.jpg`,
`karte-brot.jpg`, `buffet.jpg` …). Die Galerie „Bilder“ wird über die Liste `PICS` am Ende von `index.html` gesteuert.

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
