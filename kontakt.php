<?php
/**
 * Kontaktformular -> E-Mail an schuster@indinger.email
 *
 * Ganz einfach: Die Angaben aus dem Formular werden mit der PHP-Funktion mail()
 * des Webhosters verschickt. Kein Passwort, keine Zusatzdateien.
 * Läuft auf jedem Webhoster mit PHP (ab Version 5.6).
 */

// ---- Einstellungen ----------------------------------------------------------
$empfaenger = 'schuster@indinger.email';
$absender   = 'schuster@indinger.email';
// -----------------------------------------------------------------------------

date_default_timezone_set('Europe/Vienna');
$ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch';

function antwort($ok, $meldung, $ajax)
{
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => $ok, 'message' => $meldung));
    } else {
        // ohne JavaScript: zurück zur Startseite und dort die Meldung anzeigen
        header('Location: index.html?anfrage=' . ($ok ? 'ok' : 'fehler') . '&msg=' . rawurlencode($meldung) . '#anfrage', true, 303);
    }
    exit;
}

// Formularfeld holen: Zeilenumbrüche nur in der Nachricht erlaubt, Länge begrenzt
function feld($name, $max, $mehrzeilig = false)
{
    $v = isset($_POST[$name]) && is_string($_POST[$name]) ? trim($_POST[$name]) : '';
    if (!$mehrzeilig) $v = preg_replace('/[\r\n\t]+/', ' ', $v);
    return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    antwort(false, 'Bitte verwenden Sie das Formular auf der Startseite.', $ajax);
}

// Spam-Schutz: unsichtbares Feld, das nur Bots ausfüllen
if (feld('website', 200) !== '') {
    antwort(true, 'Danke! Ihre Anfrage ist bei uns angekommen.', $ajax);
}

$name     = feld('name', 100);
$email    = feld('email', 150);
$tel      = feld('tel', 40);
$anlass   = feld('anlass', 60);
$datum    = feld('datum', 10);
$zeit     = feld('zeit', 5);
$personen = feld('personen', 3);
$msg      = feld('msg', 3000, true);

if (strlen($name) < 2) {
    antwort(false, 'Bitte geben Sie Ihren Namen an.', $ajax);
}
if ($email === '' && $tel === '') {
    antwort(false, 'Bitte geben Sie eine E-Mail-Adresse oder Telefonnummer an, damit wir Sie erreichen.', $ajax);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    antwort(false, 'Die E-Mail-Adresse scheint nicht korrekt zu sein.', $ajax);
}

if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $datum, $d)) {
    $datum = $d[3] . '.' . $d[2] . '.' . $d[1];
}

// ---- E-Mail -----------------------------------------------------------------
$betreff = 'Anfrage über die Website: ' . ($anlass !== '' ? $anlass : 'Kontakt') . ' – ' . $name;

$text = "Neue Anfrage über das Kontaktformular\n"
      . "=====================================\n\n"
      . "Name:     $name\n"
      . 'E-Mail:   ' . ($email !== '' ? $email : '–') . "\n"
      . 'Telefon:  ' . ($tel !== '' ? $tel : '–') . "\n"
      . 'Anlass:   ' . ($anlass !== '' ? $anlass : '–') . "\n"
      . 'Datum:    ' . ($datum !== '' ? $datum : '–') . "\n"
      . 'Uhrzeit:  ' . ($zeit !== '' ? "$zeit Uhr" : '–') . "\n"
      . 'Personen: ' . ($personen !== '' ? $personen : '–') . "\n\n"
      . "Nachricht:\n" . ($msg !== '' ? $msg : '–') . "\n\n"
      . '-- Gesendet am ' . date('d.m.Y') . ' um ' . date('H:i') . " Uhr\n";

$header = "From: Speckstueberl Website <$absender>\r\n"
        . ($email !== '' ? "Reply-To: $email\r\n" : '')
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit";

$ok = mail($empfaenger, '=?UTF-8?B?' . base64_encode($betreff) . '?=', $text, $header);

if ($ok) {
    antwort(true, 'Danke, ' . $name . '! Ihre Anfrage ist bei uns angekommen. Wir melden uns so rasch wie möglich.', $ajax);
}
antwort(false, 'Die Anfrage konnte leider nicht gesendet werden. Bitte rufen Sie uns an: 07216 / 61 05.', $ajax);
