<?php
/**
 * Einstellungen für das Anfrageformular (kontakt.php).
 * Diese Datei wird nur eingebunden, direkt aufgerufen gibt sie nichts aus.
 *
 * Das PASSWORT gehört NICHT hier hinein (diese Datei liegt auf GitHub),
 * sondern in die Datei config.local.php – siehe config.local.example.php.
 */
$config = [
    // An diese Adresse gehen die Anfragen aus dem Kontaktformular.
    'empfaenger' => 'schuster@indinger.email',

    // Absender der Mail.
    'absender'      => 'schuster@indinger.email',
    'absender_name' => 'Speckstüberl Website',

    // Betreff-Präfix, z. B. "[Website] Anfrage: Feier / Buffet"
    'betreff_prefix' => '[Website]',

    // Spam-Schutz: höchstens so viele Anfragen pro Besucher in 10 Minuten.
    'max_pro_10min' => 5,

    // Versand über das Postfach schuster@indinger.email (SMTP) – empfohlen.
    // Ist hier kein Passwort eingetragen (bzw. in config.local.php), wird stattdessen
    // die PHP-Funktion mail() des Webhosters verwendet.
    'smtp' => [
        'host'         => '',        // z. B. 'smtp.world4you.com' – steht in den Postfach-Einstellungen des Mail-Anbieters
        'port'         => 587,       // 587 = STARTTLS (üblich), 465 = SSL
        'verschluesselung' => 'tls', // 'tls' bei Port 587, 'ssl' bei Port 465
        'benutzer'     => 'schuster@indinger.email',
        'passwort'     => '',        // NICHT hier eintragen, sondern in config.local.php
    ],
];

// Lokale Einstellungen (mit Passwort) überschreiben die Werte oben.
if (is_file(__DIR__ . '/config.local.php')) {
    $lokal = require __DIR__ . '/config.local.php';
    if (is_array($lokal)) {
        $config = array_replace_recursive($config, $lokal);
    }
}

return $config;
