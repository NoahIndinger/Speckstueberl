<?php
/**
 * Nur für die lokale Entwicklung: ersetzt "sendmail".
 * PHP's mail() übergibt die fertige Mail hier über STDIN – wir speichern sie
 * als .eml-Datei im Ordner "mails/", statt sie wirklich zu verschicken.
 * Die .eml-Dateien lassen sich mit jedem Mailprogramm (oder Texteditor) öffnen.
 */
$dir = dirname(__DIR__) . '/mails';
if (!is_dir($dir)) mkdir($dir, 0777, true);
$mail = stream_get_contents(STDIN);
$file = $dir . '/' . date('Y-m-d_H-i-s') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.eml';
exit(file_put_contents($file, $mail) === false ? 1 : 0);
