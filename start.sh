#!/usr/bin/env bash
# Startet die Website lokal: http://localhost:8000
# Anfragen aus dem Kontaktformular werden NICHT verschickt, sondern im Ordner "mails/" gespeichert.
# Anderer Port: ./start.sh 8080
set -e
DIR="$(cd "$(dirname "$0")" && pwd)"
PORT="${1:-8000}"

if ! command -v php >/dev/null 2>&1; then
  # Ohne PHP: Seite trotzdem anzeigen (nur das Kontaktformular sendet dann nicht)
  PY="$(command -v python3 || command -v python || true)"
  if [ -z "$PY" ]; then
    echo "Weder PHP noch Python gefunden. Einfach index.html doppelklicken, um die Seite anzusehen."
    exit 1
  fi
  echo ""
  echo "  Speckstüberl läuft auf:  http://localhost:$PORT   (ohne PHP: Formular sendet nicht)"
  echo "  Beenden mit Strg+C"
  echo ""
  ( sleep 1; command -v open >/dev/null && open "http://localhost:$PORT" || command -v xdg-open >/dev/null && xdg-open "http://localhost:$PORT" ) >/dev/null 2>&1 &
  cd "$DIR" && exec "$PY" -m http.server "$PORT" --bind 127.0.0.1
fi
PHP="$(command -v php)"

echo ""
echo "  Mühlviertler Speckstüberl läuft auf:  http://localhost:$PORT"
echo "  Team-Seite:                           http://localhost:$PORT/team.html"
echo "  Formular-Mails landen in:             $DIR/mails/"
echo "  Beenden mit Strg+C"
echo ""

( sleep 1; command -v open >/dev/null && open "http://localhost:$PORT" || command -v xdg-open >/dev/null && xdg-open "http://localhost:$PORT" ) >/dev/null 2>&1 &

exec "$PHP" -S "localhost:$PORT" -t "$DIR" \
  -d "sendmail_path='\"$PHP\" \"$DIR/tools/mail-catcher.php\"'" \
  -d "display_errors=1" \
  "$DIR/tools/router.php"
