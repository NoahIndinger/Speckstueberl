<?php
/**
 * Anfrageformular -> E-Mail an das Speckstüberl.
 *
 * Wird per fetch() aus index.html aufgerufen (Antwort als JSON).
 * Ohne JavaScript funktioniert es trotzdem: dann leitet das Skript
 * zurück auf die Startseite und zeigt dort eine Meldung an.
 */
declare(strict_types=1);

date_default_timezone_set('Europe/Vienna');
$config = require __DIR__ . '/config.php';

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

function antwort(bool $ok, string $meldung, bool $isAjax, int $status = 200): never
{
    if ($isAjax) {
        http_response_code($ok ? 200 : $status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $meldung], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: index.html?anfrage=' . ($ok ? 'ok' : 'fehler') . '&msg=' . rawurlencode($meldung) . '#anfrage', true, 303);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    antwort(false, 'Bitte verwenden Sie das Formular auf der Startseite.', $isAjax, 405);
}

/* ---------- Hilfsfunktionen (ohne mbstring, läuft auf jedem PHP) ---------- */
function kuerzen(string $v, int $max): string
{
    return preg_match('/^.{0,' . $max . '}/su', $v, $m) ? $m[0] : '';
}
function laenge(string $v): int
{
    return (int) preg_match_all('/./su', $v);
}
// Umlaute in Mail-Kopfzeilen (Betreff, Namen) korrekt kodieren.
function kopf(string $v): string
{
    return preg_match('/[^\x20-\x7e]/', $v) ? '=?UTF-8?B?' . base64_encode($v) . '?=' : $v;
}

/**
 * Schickt eine Mail über einen SMTP-Server (z. B. das Postfach schuster@indinger.email).
 * Gibt null zurück, wenn alles geklappt hat, sonst eine Fehlerbeschreibung fürs Server-Log.
 */
function smtp_senden(array $s, string $von, string $an, string $betreff, string $text, array $headers): ?string
{
    $port = (int) ($s['port'] ?? 587);
    $verschl = strtolower((string) ($s['verschluesselung'] ?? 'tls'));
    $ziel = ($verschl === 'ssl' ? 'ssl://' : 'tcp://') . $s['host'] . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $s['host']]]);
    $fp = @stream_socket_client($ziel, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return "Verbindung zu $ziel fehlgeschlagen: $errstr ($errno)";
    stream_set_timeout($fp, 15);

    $lesen = function () use ($fp): string {
        $antwort = '';
        while (($zeile = fgets($fp, 1024)) !== false) {
            $antwort .= $zeile;
            if (strlen($zeile) < 4 || $zeile[3] !== '-') break; // letzte Zeile einer Antwort
        }
        return $antwort;
    };
    $befehl = function (?string $cmd, array $ok, ?string $name = null) use ($fp, $lesen): ?string {
        if ($cmd !== null) fwrite($fp, $cmd . "\r\n");
        $a = $lesen();
        $code = (int) substr($a, 0, 3);
        if (!in_array($code, $ok, true)) {
            $zeigen = $name ?? $cmd ?? 'Begrüßung'; // Zugangsdaten nie ins Log schreiben
            return "$zeigen → " . trim($a);
        }
        return null;
    };

    $hostname = preg_replace('/[^a-z0-9.-]/i', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
    $schritte = function () use ($befehl, $fp, $verschl, $hostname, $s, $von, $an, $betreff, $text, $headers): ?string {
        if ($f = $befehl(null, [220])) return $f;
        if ($f = $befehl("EHLO $hostname", [250])) return $f;
        if ($verschl === 'tls') {
            if ($f = $befehl('STARTTLS', [220])) return $f;
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                return 'TLS konnte nicht gestartet werden';
            }
            if ($f = $befehl("EHLO $hostname", [250])) return $f;
        }
        if ($f = $befehl('AUTH LOGIN', [334])) return $f;
        if ($f = $befehl(base64_encode((string) $s['benutzer']), [334], 'Benutzername')) return 'Benutzername abgelehnt: ' . $f;
        if ($f = $befehl(base64_encode((string) $s['passwort']), [235], 'Passwort')) return 'Anmeldung fehlgeschlagen (Passwort?): ' . $f;
        if ($f = $befehl("MAIL FROM:<$von>", [250])) return $f;
        if ($f = $befehl("RCPT TO:<$an>", [250, 251])) return $f;
        if ($f = $befehl('DATA', [354])) return $f;

        $kopf = "Date: " . date('r') . "\r\n"
              . "To: <$an>\r\n"
              . "Subject: $betreff\r\n"
              . 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . substr(strrchr($von, '@'), 1) . ">\r\n";
        foreach ($headers as $k => $v) $kopf .= "$k: $v\r\n";
        // Zeilen, die mit einem Punkt beginnen, verdoppeln (SMTP-Regel)
        $inhalt = preg_replace('/^\./m', '..', $text);
        fwrite($fp, $kopf . "\r\n" . $inhalt . "\r\n.\r\n");
        if ($f = $befehl(null, [250])) return $f;
        $befehl('QUIT', [221]);
        return null;
    };
    $ergebnis = $schritte();
    fclose($fp);
    return $ergebnis;
}

/* ---------- Eingaben einlesen ---------- */
function feld(string $name, int $max = 200): string
{
    $v = $_POST[$name] ?? '';
    if (!is_string($v)) return '';
    if (!preg_match('//u', $v)) return ''; // kein gültiges UTF-8
    return kuerzen(trim(str_replace("\0", '', $v)), $max);
}
// Für alles, was in einer Kopfzeile oder einzeiligen Angabe landet: keine Zeilenumbrüche.
function einzeilig(string $v): string
{
    return trim(preg_replace('/[\r\n\t]+/', ' ', $v));
}

$name     = einzeilig(feld('name', 100));
$email    = einzeilig(feld('email', 150));
$tel      = einzeilig(feld('tel', 40));
$anlass   = einzeilig(feld('anlass', 60));
$datum    = feld('datum', 10);
$zeit     = feld('zeit', 5);
$personen = feld('personen', 3);
$msg      = feld('msg', 3000);

/* ---------- Spam-Schutz ---------- */
// 1) Honeypot: dieses Feld ist für Menschen unsichtbar.
if (feld('website') !== '') {
    antwort(true, 'Danke! Ihre Anfrage ist bei uns angekommen.', $isAjax); // Bots nichts verraten
}
// 2) Zu schnell ausgefüllt (unter 3 Sekunden) = sehr wahrscheinlich ein Bot.
$start = (int) feld('t', 20);
if ($start > 0 && (int) (microtime(true) * 1000) - $start < 3000) {
    antwort(false, 'Das ging etwas zu schnell. Bitte versuchen Sie es noch einmal.', $isAjax, 429);
}
// 3) Begrenzung pro Besucher.
session_start();
$jetzt = time();
$_SESSION['anfragen'] = array_filter($_SESSION['anfragen'] ?? [], fn($t) => $t > $jetzt - 600);
if (count($_SESSION['anfragen']) >= (int) $config['max_pro_10min']) {
    antwort(false, 'Sie haben bereits mehrere Anfragen gesendet. Bitte rufen Sie uns an: 07216 / 61 05.', $isAjax, 429);
}

/* ---------- Prüfen ---------- */
$fehler = [];
if (laenge($name) < 2) {
    $fehler[] = 'Bitte geben Sie Ihren Namen an.';
}
if ($email === '' && $tel === '') {
    $fehler[] = 'Bitte geben Sie eine E-Mail-Adresse oder Telefonnummer an, damit wir Sie erreichen.';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fehler[] = 'Die E-Mail-Adresse scheint nicht korrekt zu sein.';
}
if ($tel !== '' && !preg_match('/^[0-9 +\/()\-.]{5,40}$/', $tel)) {
    $fehler[] = 'Die Telefonnummer scheint nicht korrekt zu sein.';
}
$erlaubteAnlaesse = ['Tischreservierung', 'Feier / Buffet', 'Platten zum Abholen', 'Ripperl / Stelzen vorbestellen', 'Sonstiges'];
if (!in_array($anlass, $erlaubteAnlaesse, true)) {
    $anlass = 'Sonstiges';
}
$datumText = '–';
if ($datum !== '') {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $datum);
    if (!$d || $d->format('Y-m-d') !== $datum) {
        $fehler[] = 'Das Datum ist ungültig.';
    } else {
        $tage = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        $datumText = $tage[(int) $d->format('w')] . ', ' . $d->format('d.m.Y');
    }
}
if ($zeit !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $zeit)) {
    $fehler[] = 'Die Uhrzeit ist ungültig.';
}
if ($personen !== '' && (!ctype_digit($personen) || (int) $personen < 1 || (int) $personen > 80)) {
    $fehler[] = 'Bitte geben Sie eine Personenanzahl zwischen 1 und 80 an.';
}
if ($fehler) {
    antwort(false, implode(' ', $fehler), $isAjax, 422);
}

/* ---------- Mail zusammenbauen ---------- */
$text = "Neue Anfrage über die Website\n"
      . "==============================\n\n"
      . "Name:     $name\n"
      . 'E-Mail:   ' . ($email ?: '–') . "\n"
      . 'Telefon:  ' . ($tel ?: '–') . "\n"
      . "Anlass:   $anlass\n"
      . "Datum:    $datumText\n"
      . 'Uhrzeit:  ' . ($zeit ? "$zeit Uhr" : '–') . "\n"
      . 'Personen: ' . ($personen ?: '–') . "\n\n"
      . "Nachricht:\n" . ($msg !== '' ? $msg : '–') . "\n\n"
      . "--\nGesendet am " . date('d.m.Y \u\m H:i') . ' Uhr über das Formular auf der Website.'
      . ($email !== '' ? "\nEinfach auf diese Mail antworten, um $name zu schreiben." : '') . "\n";
$text = preg_replace("/\r\n|\r|\n/", "\r\n", $text);

$betreff = trim($config['betreff_prefix'] . ' Anfrage: ' . $anlass . ' – ' . $name);

$headers = [
    'From'                      => kopf($config['absender_name']) . ' <' . $config['absender'] . '>',
    'MIME-Version'              => '1.0',
    'Content-Type'              => 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => '8bit',
    'X-Mailer'                  => 'Speckstueberl-Website',
];
if ($email !== '') {
    $headers['Reply-To'] = '=?UTF-8?B?' . base64_encode($name) . '?=' . ' <' . $email . '>';
}

$smtp = $config['smtp'] ?? [];
if (!empty($smtp['host']) && ($smtp['passwort'] ?? '') !== '') {
    // Versand direkt über das Postfach (zuverlässig, landet nicht im Spam)
    $fehler = smtp_senden($smtp, $config['absender'], $config['empfaenger'], kopf($betreff), $text, $headers);
    $gesendet = $fehler === null;
    if (!$gesendet) error_log('kontakt.php: SMTP-Versand fehlgeschlagen: ' . $fehler);
} else {
    // Fallback: mail() des Webhosters
    $gesendet = mail($config['empfaenger'], kopf($betreff), $text, $headers);
    if (!$gesendet) error_log('kontakt.php: mail() fehlgeschlagen (SMTP in config.local.php eintragen)');
}

if (!$gesendet) {
    antwort(false, 'Die Anfrage konnte leider nicht gesendet werden. Bitte rufen Sie uns an: 07216 / 61 05.', $isAjax, 500);
}

$_SESSION['anfragen'][] = $jetzt;
antwort(true, 'Danke, ' . $name . '! Ihre Anfrage ist bei uns angekommen. Wir melden uns so rasch wie möglich.', $isAjax);
