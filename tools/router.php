<?php
/**
 * Router für den eingebauten PHP-Server (nur lokal, siehe start.sh / start.bat).
 * Sperrt interne Dateien und lässt alles andere normal ausliefern.
 */
$pfad = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if (preg_match('#^/(tools|mails)(/|$)|^/config(\.local|\.local\.example)?\.php$|/\.#', $pfad)) {
    http_response_code(403);
    echo 'Kein Zugriff.';
    return true;
}
if ($pfad === '/team') {           // schöne URL: /team -> team.html
    readfile(dirname(__DIR__) . '/team.html');
    return true;
}
return false; // Datei ganz normal ausliefern
