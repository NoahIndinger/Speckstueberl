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

$gesendet = mail(
    $config['empfaenger'],
    kopf($betreff),
    $text,
    $headers,
    '-f' . $config['absender']
);

if (!$gesendet) {
    error_log('kontakt.php: mail() fehlgeschlagen');
    antwort(false, 'Die Anfrage konnte leider nicht gesendet werden. Bitte rufen Sie uns an: 07216 / 61 05.', $isAjax, 500);
}

$_SESSION['anfragen'][] = $jetzt;
antwort(true, 'Danke, ' . $name . '! Ihre Anfrage ist bei uns angekommen. Wir melden uns so rasch wie möglich.', $isAjax);
